<?php

namespace App\Http\Controllers;

use App\Classes\Module;
use App\Models\AddOn;
use App\Models\Plan;
use App\Models\UserActiveModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use ZipArchive;

class ModuleController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-add-on')) {
            $modules = $this->getAllModules();

            return Inertia::render('modules/index', [
                'modules' => $modules,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function upload()
    {
        if (Auth::user()->can('manage-add-on')) {
            return Inertia::render('modules/upload');
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function enable(Request $request)
    {
        $module = (new Module())->find($request->name);
        if (!empty($module)) {

            \App::setLocale('en');

            if ($module->isEnabled()) {
                $check_child_module = $this->Check_Child_Module($module);
                if ($check_child_module == true) {
                    $module = (new Module())->find($request->name);
                    $module->disable();
                    return redirect()->back()->with('success', __('Module Disable Successfully!'));
                } else {
                    return redirect()->back()->with('error', __($check_child_module['msg']));
                }
            } else {
                (new Module())->publishAssets($request->name);

                $addon = AddOn::where('module', $request->name)->first();
                if (empty($addon)) {
                    Artisan::call('migrate --path=/packages/local/' . $request->name . '/src/Database/Migrations --force');
                    Artisan::call('package:seed ' . $request->name);

                    $filePath = base_path('packages/local/' . $request->name . '/module.json');
                    $jsonContent = file_get_contents($filePath);
                    $data = json_decode($jsonContent, true);


                    $addon = new AddOn;
                    $addon->module = $data['name'];
                    $addon->name = $data['alias'];
                    $addon->monthly_price = $data['monthly_price'] ?? 0;
                    $addon->yearly_price = $data['yearly_price'] ?? 0;
                    $addon->package_name = $data['package_name'];
                    $addon->for_admin = $data['for_admin'] ?? false;
                    $addon->priority = $data['priority'] ?? 0;
                    $addon->save();
                }
                (new Module())->moduleCacheForget($request->name);
                $module = (new Module())->find($request->name);
                $check_parent_module = $this->Check_Parent_Module($module);
                if ($check_parent_module['status'] == true) {
                    Artisan::call('migrate --path=/packages/local/' . $request->name . '/src/Database/Migrations --force');
                    Artisan::call('package:seed ' . $request->name);
                    $module = (new Module())->find($request->name);
                    $module->enable();
                    return redirect()->back()->with('success', __('Module Enable Successfully!'));
                } else {
                    return redirect()->back()->with('error', __($check_parent_module['msg']));
                }
            }
        } else {
            return redirect()->back()->with('error', __('oops something wren wrong!'));
        }
    }

    public function Check_Parent_Module($module)
    {
        $path = $module->getPath() . '/module.json';
        $json = json_decode(file_get_contents($path), true);
        $data['status'] = true;
        $data['msg'] = '';

        if (isset($json['parent_module']) && !empty($json['parent_module'])) {
            foreach ($json['parent_module'] as $key => $value) {
                $modules = implode(',', $json['parent_module']);
                $parent_module = module_is_active($value);
                if ($parent_module == true) {
                    $module =  (new Module())->find($value);
                    if ($module) {
                        $this->Check_Parent_Module($module);
                    }
                } else {
                    $data['status'] = false;
                    $data['msg'] = 'please activate this module ' . $modules;
                    return $data;
                }
            }
            return $data;
        } else {
            return $data;
        }
    }
    public function Check_Child_Module($module)
    {
        $path = $module->getPath() . '/module.json';
        $json = json_decode(file_get_contents($path), true);
        if (isset($json['child_module']) && !empty($json['child_module'])) {
            foreach ($json['child_module'] as $key => $value) {
                $child_module = module_is_active($value);
                if ($child_module == true) {
                    $module =  (new Module())->find($value);
                    $module->disable();
                    if ($module) {
                        $this->Check_Child_Module($module);
                    }
                }
            }
            return true;
        } else {
            return true;
        }
    }

    private function getAllModules()
    {
        $modules = [];
        $packagesPath = base_path('packages/local');

        if (!File::exists($packagesPath)) {
            return $modules;
        }

        $directories = File::directories($packagesPath);

        foreach ($directories as $directory) {
            $moduleName = basename($directory);
            $moduleJsonPath = "{$directory}/module.json";

            if (File::exists($moduleJsonPath)) {
                $moduleData = json_decode(File::get($moduleJsonPath), true);

                if ($moduleData) {
                    $addon = AddOn::where('module', $moduleData['name'])->first();
                    $modules[] = [
                        'name' => $moduleData['name'] ?? $moduleName,
                        'alias' =>  $addon ? $addon->name :$moduleData['alias'],
                        'description' => $moduleData['description'] ?? '',
                        'version' => $moduleData['version'] ?? '1.0.0',
                        'image' => url('/packages/local/' . $moduleName . '/favicon.png'),
                        'is_enabled' => $addon ? $addon->is_enable : false,
                        'package_name' => $moduleData['package_name'] ?? null,
                        'display' => $moduleData['display'] ?? true,
                        'priority' => $moduleData['priority'] ?? 10,
                    ];
                }
            }
        }

        usort($modules, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return $modules;
    }

    public function install(Request $request)
    {
        if (!Auth::user()->can('manage-add-on')) {
            return back()->with('error', __('Permission denied'));
        }

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:zip|max:51200'
        ], [
            'files.required' => __('Please select at least one file to upload.'),
            'files.array' => __('Files must be provided as an array.'),
            'files.*.required' => __('Each file is required.'),
            'files.*.file' => __('Each item must be a valid file.'),
            'files.*.mimes' => __('Only ZIP files are allowed.'),
            'files.*.max' => __('File size cannot exceed 50MB.'),
        ]);

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($request->file('files') as $file) {
            try {
                $result = $this->installSinglePackage($file);
                $results[] = $result;
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'filename' => $file->getClientOriginalName(),
                    'message' => $e->getMessage()
                ];
                $errorCount++;
            }
        }

        $message = "Installed {$successCount} packages successfully";
        if ($errorCount > 0) {
            $message .= ", {$errorCount} failed";
        }

        return back()->with($errorCount > 0 ? 'warning' : 'success', $message);
    }

    private function installSinglePackage($file)
    {
        $zip = new ZipArchive;
        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $res = $zip->open($file->getPathname());
        if ($res !== TRUE) {
            throw new \Exception("Unable to open ZIP file: {$file->getClientOriginalName()}");
        }

        $tempPath = base_path('packages/local/tmp_' . uniqid());
        $zip->extractTo($tempPath);
        $zip->close();

        $rootFolder = array_diff(scandir($tempPath), ['.', '..']);
        if (empty($rootFolder)) {
            $this->deleteDirectory($tempPath);
            throw new \Exception("Invalid ZIP structure: {$file->getClientOriginalName()}");
        }

        $rootFolderName = array_values($rootFolder)[0];
        $moduleJsonPath = $tempPath . '/' . $rootFolderName . '/module.json';

        if (!file_exists($moduleJsonPath)) {
            $this->deleteDirectory($tempPath);
            throw new \Exception("module.json not found: {$file->getClientOriginalName()}");
        }

        $moduleData = json_decode(file_get_contents($moduleJsonPath), true);
        if (!$moduleData) {
            $this->deleteDirectory($tempPath);
            throw new \Exception("Invalid module.json: {$file->getClientOriginalName()}");
        }

        $extractPath = base_path('packages/local/' . $moduleData['name']);
        $this->createDirectory($extractPath);
        $this->moveExtractedFiles($tempPath . '/' . $rootFolderName, $extractPath);
        $this->deleteDirectory($tempPath);
        $this->setPermissions($extractPath);

        (new Module())->publishAssets($moduleData['name']);

        $addon = AddOn::where('module', $moduleData['name'])->first();
        if (!$addon) {
            $addon = new AddOn;
            $addon->module = $moduleData['name'];
            $addon->name = $moduleData['alias'];
            $addon->monthly_price = $moduleData['monthly_price'] ?? 0;
            $addon->yearly_price = $moduleData['yearly_price'] ?? 0;
            $addon->is_enable = false;
            $addon->package_name = $moduleData['package_name'] ?? null;
            $addon->for_admin = $moduleData['for_admin'] ?? null;
            $addon->priority = $moduleData['priority'] ?? 0;
            $addon->save();
        }

        return [
            'success' => true,
            'filename' => $file->getClientOriginalName(),
            'message' => "Installed successfully"
        ];
    }

    private function createDirectory($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
            $this->setPermissions($path);
        } else {
            $this->setPermissions($path);
        }
    }

    // Set directory permissions
    private function setPermissions($path)
    {
        if (function_exists('chmod')) {
            @chmod($path, 0777); // Set permissions if possible
        }
    }

    /**
     * Move files from one directory to another.
     *
     * @param string $source
     * @param string $destination
     */
    private function moveExtractedFiles($source, $destination, $filename = null)
    {
        // Adjust the source directory if a root folder (e.g., $filename) exists in the zip
        if ($filename) {
            $source = $source . DIRECTORY_SEPARATOR . $filename;
        }

        $files = array_diff(scandir($source), ['.', '..']);
        foreach ($files as $file) {
            $srcPath = $source . DIRECTORY_SEPARATOR . $file;
            $destPath = $destination . DIRECTORY_SEPARATOR . $file;

            if (is_dir($srcPath)) {
                // Recursively move subdirectories
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0777, true);
                }
                // Check if chmod exists
                if (function_exists('chmod')) {
                    @chmod($destPath, 0777); // Set permissions if possible
                }
                $this->moveExtractedFiles($srcPath, $destPath);
            } else {
                // Move file
                rename($srcPath, $destPath);
                // Check if chmod exists
                if (function_exists('chmod')) {
                    @chmod($destPath, 0777); // Set permissions if possible
                }
            }
        }
    }

    /**
     * Delete a directory and its contents.
     *
     * @param string $dirPath
     * @return bool
     */
    private function deleteDirectory($dirPath)
    {
        if (!is_dir($dirPath)) {
            return false;
        }

        $items = array_diff(scandir($dirPath), ['.', '..']);
        foreach ($items as $item) {
            $path = $dirPath . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        return rmdir($dirPath);
    }

    public function getUserActiveModules()
    {
        $user = Auth::user();

        $oldplan = $user && $user->active_plan ? Plan::where('id', $user->active_plan)->first() : null;
        $plan = Plan::find($oldplan->id);
        
        $userActiveModules = UserActiveModule::where('user_id', Auth::id())->get();

        $modules = [];
        foreach ($userActiveModules as $userModule) {
            $addon = AddOn::where('module', $userModule->module)->first();
            if ($addon) {
                $modules[] = [
                    'module' => $userModule->module,
                    'alias' => $addon->name,
                    'image' => url('/packages/local/' . $userModule->module . '/favicon.png'),
                    'monthly_price' => $addon->monthly_price,
                    'yearly_price' => $addon->yearly_price
                ];
            }
        }

        return response()->json([
            'success' => true,
            'modules' => $modules
        ]);
    }

    public function removeUserActiveModule($moduleId)
    {
        $deleted = UserActiveModule::where('user_id', Auth::id())
            ->where('module', $moduleId)
            ->delete();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $deleted > 0 ? 'Module removed successfully' : 'Module not found'
        ]);
    }
}
