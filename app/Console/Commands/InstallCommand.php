<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use App\Models\AddOn;
use App\Classes\Module;
use Illuminate\Support\Facades\DB;

class InstallCommand extends Command
{
    protected $signature = 'app:install
                            {--force : Force installation even if already installed}';

    protected $description = 'Install the application';

    public function handle()
    {
        if ($this->isInstalled() && !$this->option('force')) {
            $this->error('Application is already installed. Use --force to reinstall.');
            return 1;
        }

        $this->info('Starting application installation...');

        // Generate app key if not exists
        if (empty(config('app.key'))) {
            $this->info('Generating application key...');
            Artisan::call('key:generate', ['--force' => true]);
        }

       // Handle foreign key constraints based on database type
       $dbType = config('database.default');
       if ($dbType === 'mysql') {
           DB::statement('SET FOREIGN_KEY_CHECKS=0');
       }

       // Drop all tables if they exist
       Artisan::call('migrate:fresh', ['--force' => true]);

       if ($dbType === 'mysql') {
           DB::statement('SET FOREIGN_KEY_CHECKS=1');
       }

       Artisan::call('db:seed', ['--force' => true]);

        // Install packages
        $this->installPackages();

        // Create installed file
        $this->createInstalledFile();

        $this->info('Application installed successfully!');
        return 0;
    }



    private function createInstalledFile()
    {
        File::put(storage_path('installed'), 'install ' . date('Y-m-d H:i:s'));
    }

    private function installPackages()
    {
        $this->info('Installing packages...');

        try {
            $modules = $this->getAllAvailableModules();

            foreach ($modules as $module) {
                $this->info("Installing module: {$module['alias']}");
                try {
                    $this->enableModule($module['name']);
                    $this->info("✓ Module {$module['alias']} installed successfully");
                } catch (\Exception $e) {
                    $this->error("✗ Failed to install {$module['alias']}: " . $e->getMessage());
                }
            }

            $this->info('All packages installed successfully.');
        } catch (\Exception $e) {
            $this->error('Package installation failed: ' . $e->getMessage());
        }
    }

    private function getAllAvailableModules()
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
                    $modules[] = [
                        'name' => $moduleData['name'],
                        'alias' => $moduleData['alias'],
                        'description' => $moduleData['description'] ?? '',
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

    private function enableModule($moduleName)
    {
        // Validate module name to prevent path traversal
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $moduleName)) {
            throw new \Exception('Invalid module name');
        }

        (new Module())->publishAssets($moduleName);

        $addon = AddOn::where('module', $moduleName)->first();
        if (empty($addon)) {
            $filePath = base_path('packages/local/' . $moduleName . '/module.json');

            if (!file_exists($filePath)) {
                throw new \Exception('Module configuration not found');
            }

            $jsonContent = file_get_contents($filePath);
            $data = json_decode($jsonContent, true);

            if (!$data) {
                throw new \Exception('Invalid module configuration');
            }

            Artisan::call('package:seed ' . $moduleName);

            $addon = new AddOn;
            $addon->module = $data['name'];
            $addon->name = $data['alias'];
            $addon->monthly_price = $data['monthly_price'] ?? 0;
            $addon->yearly_price = $data['yearly_price'] ?? 0;
            $addon->package_name = $data['package_name'] ?? null;
            $addon->for_admin = $data['for_admin'] ?? false;
            $addon->priority = $data['priority'] ?? 0;
            $addon->is_enable = 1;
            $addon->save();
        } else {

            Artisan::call('package:seed ' . $moduleName);

            $addon->is_enable = 1;
            $addon->save();
        }
    }

    private function isInstalled(): bool
    {
        return File::exists(storage_path('installed'));
    }
}