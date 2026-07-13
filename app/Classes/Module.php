<?php

namespace App\Classes;

use App\Models\AddOn;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class Module
{
    protected $addon;
    public $name;
    public $alias;
    public $monthly_price;
    public $yearly_price;
    public $image;
    public $description;
    public $priority;
    public $child_module;
    public $parent_module;
    public $version;
    public $package_name;
    public $display;
    public $for_admin;
    public $is_enable;
    protected $allEnabled = [];

    public function json($name)
    {
        $path = base_path('packages/local/' . $name . '/module.json');
        if (!File::exists($path) && $this->addon && $this->addon->package_name) {
            $path = base_path('vendor/zerp/' . $this->addon->package_name . '/module.json');
        }
        if (!File::exists($path)) {
            return false;
        }

        $contents = File::get($path);
        return json_decode($contents, true);
    }

    public function find($name)
    {

        return Cache::rememberForever(
            $name,
            function () use ($name) {
                if ($name === 'general') {
                    $this->name =  $name;
                    $this->alias =  $name;
                } else {
                    $this->addon = AddOn::where('module', $name)->orWhere('package_name', $name)->first();

                    $addonJson = $this->json($name);
                    if ($addonJson) {
                        $this->name = $addonJson['name'] ?? $name;
                        $this->alias = $addonJson['alias'] ?? $name;
                        $this->monthly_price = $addonJson['monthly_price'] ?? 0;
                        $this->yearly_price = $addonJson['yearly_price'] ?? 0;
                        $this->image = $this->addon->image ?? url('/packages/local/' . $name . '/favicon.png');
                        $this->description = $addonJson['description'] ?? "";
                        $this->priority = $addonJson['priority'] ?? 10;
                        $this->child_module = $addonJson['child_module'] ?? [];
                        $this->parent_module = $addonJson['parent_module'] ?? [];
                        $this->version = $addonJson['version'] ?? 1.0;
                        $this->package_name = $addonJson['package_name'] ?? null;
                        $this->display = $addonJson['display'] ?? true;
                        $this->for_admin = $addonJson['for_admin'] ?? false;
                        $this->is_enable = false;
                    }

                    if ($this->addon) {
                        $this->name = $this->addon->module ?? $name;
                        $this->alias = $this->addon->name ?? $name;
                        $this->monthly_price = $this->addon->monthly_price ?? 0;
                        $this->yearly_price = $this->addon->yearly_price ?? 0;
                        $this->image = $this->addon->image ? getImageUrlPrefix().'/'.$this->addon->image : url('/packages/local/' . $name . '/favicon.png');
                        $this->package_name = $this->addon->package_name ?? null;
                        $this->for_admin = $this->addon->for_admin ?? false;
                        $this->is_enable = $this->addon->is_enable ?? false;
                    }
                }

                return $this;
            }
        );
    }

    public function all()
    {
        $modules = $this->allEnabled();
        return $this->moduleArr($modules);
    }

    public function moduleArr($modules)
    {
        $allModulesArr = [];
        foreach ($modules as $module) {
            $moduleInstance = new self();
            $allModulesArr[] = $moduleInstance->find($module);
        }
        return $allModulesArr;
    }

    public function allEnabled(): array
    {

        return AddOn::where('is_enable', 1)->orderBy('priority')->pluck('module')->toArray() ?? [];

    }

    public function allEnabledAdmin(): array
    {
        return AddOn::where('for_admin', 1)->where('is_enable', 1)->orderBy('priority')->pluck('module')->toArray() ?? [];
    }

    public function getOrdered()
    {
        $modules = $this->all();

        usort($modules, function ($a, $b) {
            return $a->priority - $b->priority;
        });

        return $modules;
    }

    public function has($name)
    {
        return in_array($name, array_column($this->allModules(), 'name'));
    }

    public function isEnabled($module = null)
    {
        static $cache = [];

        if ($module) {
            if (!isset($cache[$module])) {

                $cache[$module] = Addon::where('module', $module)
                    ->where('is_enable', 1)
                    ->exists();
            }

            return $cache[$module];
        }

        return $this->addon && $this->addon->is_enable;
    }

    public function enable()
    {
        if ($this->addon) {
            $this->addon->is_enable = 1;
            $this->addon->save();

            $this->moduleCacheForget();

        }
    }

    public function disable()
    {
        if ($this->addon) {
            $this->addon->is_enable = 0;
            $this->addon->save();

            $this->moduleCacheForget();
        }
    }

    public function getDirectories()
    {
        $path = base_path('packages/local');
        if (!File::isDirectory($path)) {
            return [];
        }
        return File::directories($path);
    }

    public function getPath()
    {
        if (is_null($this->addon)) {
            return $this->getDirectories();
        }
        return $this->resolveModulePath($this->name, $this->addon->package_name);
    }

    public function getDevPackagePath()
    {
        if (is_null($this->addon)) {
            $path = base_path('packages/local');
            return File::directories($path);
        }
        return $this->resolveModulePath($this->name, $this->addon->package_name);
    }

    /**
     * A module's source lives either under packages/local/<name> (legacy,
     * in-repo) or vendor/zerp/<package_name> (installed as a real Composer
     * package). Resolve whichever actually exists.
     */
    private function resolveModulePath(string $name, ?string $packageName): string
    {
        $legacyPath = base_path('packages/local/' . $name);
        if (File::isDirectory($legacyPath)) {
            return $legacyPath;
        }
        if ($packageName) {
            $vendorPath = base_path('vendor/zerp/' . $packageName);
            if (File::isDirectory($vendorPath)) {
                return $vendorPath;
            }
        }
        return $legacyPath;
    }

    public function allModules()
    {
        $fromDisk = array_map(function ($dir) {
            return basename($dir);
        }, $this->getDirectories());

        // Modules migrated to real Composer packages (vendor/zerp/<slug>) no
        // longer have a packages/local/<name> directory to scan, but they
        // still have an AddOn row from when they were installed/enabled.
        $fromDb = AddOn::pluck('module')->filter()->toArray();

        $names = array_values(array_unique(array_merge($fromDisk, $fromDb)));

        return $this->moduleArr($names);
    }

    public function moduleCacheForget($module = null)
    {
        try {
            if(is_null($module)){
                Cache::forget($this->addon->module);
                Cache::forget($this->addon->package_name);
            }
            else{
                Cache::forget($module);
            }
        } catch (\Exception $e) {
            \Log::error($module . $e->getMessage());
        }
    }

    /**
     * Publish a module's public assets (favicon + src/Resources/assets) into
     * public/packages/local/<module>, mirroring the URLs the frontend builds
     * via getImagePath(). Only these two known asset locations are published
     * (never the module's PHP source, migrations, etc.) to avoid exposing
     * backend code over HTTP.
     */
    public function publishAssets(string $moduleName): void
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $moduleName)) {
            return;
        }

        $sourcePath = base_path('packages/local/' . $moduleName);
        if (!File::isDirectory($sourcePath)) {
            // Module has been migrated to a real Composer package (vendor/zerp/<slug>).
            // Resolve its new source so previously-published asset URLs
            // (public/packages/local/<module>/...) keep working unchanged.
            $slug = AddOn::where('module', $moduleName)->value('package_name');
            $vendorPath = $slug ? base_path('vendor/zerp/' . $slug) : null;
            if (!$vendorPath || !File::isDirectory($vendorPath)) {
                return;
            }
            $sourcePath = $vendorPath;
        }

        $publicPath = public_path('packages/local/' . $moduleName);
        File::ensureDirectoryExists($publicPath);

        $this->linkAsset($sourcePath . '/favicon.png', $publicPath . '/favicon.png');
        $this->linkAsset($sourcePath . '/src/Resources/assets', $publicPath . '/src/Resources/assets');
    }

    /**
     * Symlink an asset into public/ using a path relative to the link itself, so the
     * links survive the whole tree being moved or re-cloned elsewhere. Absolute links
     * from an older publish are replaced, as are ones left dangling by a move.
     */
    private function linkAsset(string $target, string $linkPath): void
    {
        if (is_link($linkPath) && (!file_exists($linkPath) || str_starts_with((string) readlink($linkPath), '/'))) {
            @unlink($linkPath);
        }

        if (!file_exists($target) || file_exists($linkPath) || is_link($linkPath)) {
            return;
        }

        File::ensureDirectoryExists(dirname($linkPath));
        @symlink(self::relativeSymlinkTarget($target, $linkPath), $linkPath);
    }

    /**
     * Path from the directory holding $linkPath to $target, e.g.
     * ("/app/vendor/zerp/lead/favicon.png", "/app/public/packages/local/Lead/favicon.png")
     * => "../../../../vendor/zerp/lead/favicon.png". Both paths must be absolute.
     */
    public static function relativeSymlinkTarget(string $target, string $linkPath): string
    {
        $from = explode('/', trim(dirname($linkPath), '/'));
        $to = explode('/', trim($target, '/'));

        while ($from && $to && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }

        return str_repeat('../', count($from)) . implode('/', $to);
    }
}
