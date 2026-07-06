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
                            {--force : Force installation even if already installed}
                            {--preset= : Install a named module preset non-interactively (see config/module-presets.php)}
                            {--modules= : Comma-separated list of package_name slugs to enable, non-interactively}';

    protected $description = 'Install the application';

    // Packages other modules rely on without reliably declaring it in
    // module.json — always enabled regardless of selection.
    private const FOUNDATION_PACKAGES = ['account', 'hrm', 'product-service'];

    private bool $interactiveSelectionMade = false;

    public function handle()
    {
        if ($this->isInstalled() && !$this->option('force')) {
            $this->error('Application is already installed. Use --force to reinstall.');
            return 1;
        }

        $this->info('Starting application installation...');

        $selectedPackageNames = $this->resolveModuleSelection();
        $this->printModuleSummary($selectedPackageNames);

        if ($this->interactiveSelectionMade && !$this->confirm('Proceed with this module selection?', true)) {
            $this->info('Installation cancelled.');
            return 0;
        }

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

       // PackageSeeder (fired via DatabaseSeeder) reads this binding to
       // decide which modules to enable/seed; null means "all".
       app()->instance('zerp.selected_modules', $selectedPackageNames);
       Artisan::call('db:seed', ['--force' => true]);

        // Install packages
        $this->installPackages($selectedPackageNames);

        // Create installed file
        $this->createInstalledFile();

        $this->info('Application installed successfully!');
        return 0;
    }

    /**
     * Resolve which modules (package_name slugs) to install, or null for
     * "all". Priority: --modules flag > --preset flag > interactive prompt
     * > non-interactive default (all, preserves prior behavior).
     */
    private function resolveModuleSelection(): ?array
    {
        $presets = config('module-presets', []);

        if ($modulesOpt = $this->option('modules')) {
            return $this->applyDependencySafetyNet(
                array_values(array_filter(array_map('trim', explode(',', $modulesOpt))))
            );
        }

        if ($presetOpt = $this->option('preset')) {
            if (!isset($presets[$presetOpt])) {
                $this->error("Unknown preset '{$presetOpt}'. Available: " . implode(', ', array_keys($presets)));
                $this->warn('Falling back to installing all modules.');
                return null;
            }
            $modulesList = $presets[$presetOpt]['modules'];
            return is_null($modulesList) ? null : $this->applyDependencySafetyNet($modulesList);
        }

        if (!$this->input->isInteractive()) {
            return null;
        }

        $this->interactiveSelectionMade = true;

        $presetKeys = array_keys($presets);
        $choices = array_values(array_map(fn ($p) => $p['label'], $presets));
        $choices[] = 'Custom selection';

        $answer = $this->choice('How would you like to install modules?', $choices, 0);
        $answerIndex = array_search($answer, $choices, true);

        if ($answerIndex !== false && $answerIndex < count($presetKeys)) {
            $modulesList = $presets[$presetKeys[$answerIndex]]['modules'];
            return is_null($modulesList) ? null : $this->applyDependencySafetyNet($modulesList);
        }

        // Custom selection
        $modules = $this->getAllAvailableModules();
        $optionLabels = array_map(fn ($m) => "{$m['alias']} ({$m['package_name']})", $modules);
        $picked = (array) $this->choice(
            'Select modules to enable (comma-separated numbers, e.g. 1,3,4)',
            $optionLabels,
            null,
            null,
            true
        );
        $labelToPackage = array_combine($optionLabels, array_column($modules, 'package_name'));

        return $this->applyDependencySafetyNet(
            array_values(array_map(fn ($label) => $labelToPackage[$label], $picked))
        );
    }

    /**
     * Force-include foundation packages and any declared module.json
     * parent_module dependency that wasn't itself selected. Best-effort:
     * parent_module declarations are known to be incomplete for some
     * packages, this is a safety net, not a guarantee.
     */
    private function applyDependencySafetyNet(array $selected): array
    {
        foreach (self::FOUNDATION_PACKAGES as $package) {
            if (!in_array($package, $selected, true)) {
                $selected[] = $package;
                $this->line("  <fg=yellow>note:</> auto-including '{$package}' (foundation module required by other packages)");
            }
        }

        $modules = $this->getAllAvailableModules();
        $nameToPackage = array_column($modules, 'package_name', 'name');
        $byPackage = array_column($modules, null, 'package_name');

        // Fixed-point iteration: a newly auto-included module might itself
        // declare a parent, so keep walking until nothing changes.
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($selected as $package) {
                $module = $byPackage[$package] ?? null;
                if (!$module) {
                    continue;
                }
                foreach ($module['parent_module'] as $parentName) {
                    $parentPackage = $nameToPackage[$parentName] ?? null;
                    if ($parentPackage && !in_array($parentPackage, $selected, true)) {
                        $selected[] = $parentPackage;
                        $this->line("  <fg=yellow>note:</> auto-including '{$parentPackage}' (required by '{$package}')");
                        $changed = true;
                    }
                }
            }
        }

        return array_values(array_unique($selected));
    }

    private function printModuleSummary(?array $selected): void
    {
        if (is_null($selected)) {
            $this->info('Installing all available modules.');
            return;
        }

        $rows = array_map(fn ($m) => [
            $m['alias'],
            in_array($m['package_name'], $selected, true) ? 'Enabled' : 'Skipped',
        ], $this->getAllAvailableModules());

        $this->table(['Module', 'Status'], $rows);
    }

    private function createInstalledFile()
    {
        File::put(storage_path('installed'), 'install ' . date('Y-m-d H:i:s'));
    }

    private function installPackages(?array $selectedPackageNames)
    {
        $this->info('Installing packages...');

        try {
            $modules = $this->getAllAvailableModules();

            foreach ($modules as $module) {
                if (!is_null($selectedPackageNames) && !in_array($module['package_name'], $selectedPackageNames, true)) {
                    $this->line("Skipping module: {$module['alias']}");
                    continue;
                }

                $this->info("Installing module: {$module['alias']}");
                try {
                    $this->enableModule($module['name'], $module['module_json_path']);
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

        // Modules live either under packages/local/<name> (legacy, in-repo,
        // for active local development) or vendor/zerp/<slug> (installed as
        // a real Composer package) — scan both.
        $directories = array_merge(
            File::isDirectory(base_path('packages/local')) ? File::directories(base_path('packages/local')) : [],
            File::isDirectory(base_path('vendor/zerp')) ? File::directories(base_path('vendor/zerp')) : []
        );

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
                        'package_name' => $moduleData['package_name'] ?? null,
                        'parent_module' => $moduleData['parent_module'] ?? [],
                        'module_json_path' => $moduleJsonPath,
                    ];
                }
            }
        }

        usort($modules, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return $modules;
    }

    private function enableModule($moduleName, ?string $moduleJsonPath = null)
    {
        // Validate module name to prevent path traversal
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $moduleName)) {
            throw new \Exception('Invalid module name');
        }

        $addon = AddOn::where('module', $moduleName)->first();
        if (empty($addon)) {
            $filePath = $moduleJsonPath ?? base_path('packages/local/' . $moduleName . '/module.json');

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

        // Called after the AddOn row exists so publishAssets() can resolve
        // the vendor/zerp/<package_name> fallback path on first bootstrap.
        (new Module())->publishAssets($moduleName);
    }

    private function isInstalled(): bool
    {
        return File::exists(storage_path('installed'));
    }
}
