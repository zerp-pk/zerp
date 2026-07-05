<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Filesystem\Filesystem;

class CreateReactPackage extends Command
{
    protected $signature = 'package:make {name}';
    protected $description = 'Create a new React package with TypeScript support';

    protected $files;
    public $packageName;
    public $packageLower;
    public $packageKebab;
    public $namespace;
    public $tableName;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = $this->argument('name');
        $this->packageName = $name;
        $this->packageLower = strtolower($name);
        $this->packageKebab = $this->camelToKebab($name);
        $this->namespace = "Zerp\\{$name}";
        $this->tableName = $this->packageLower . '_items';

        $packagePath = base_path("packages/local/{$name}");

        if (File::exists($packagePath)) {
            $this->error("Package {$name} already exists!");
            return;
        }

        $this->createDirectoryStructure($packagePath);
        $this->createStubFiles($packagePath);
        // $this->copyPackageComponents($packagePath);

        $this->info("Package {$name} created successfully!");
        $this->info("Location: {$packagePath}");
    }

    private function createDirectoryStructure($packagePath)
    {
        $folders = [
            'src/Database/Migrations',
            'src/Database/Seeders',
            'src/Http/Controllers',
            'src/Http/Requests',
            'src/Models',
            'src/Providers',
            'src/Listeners',
            'src/Events',
            'src/Resources/js/Pages',
            'src/Resources/js/Pages/Items',
            'src/Resources/js/menus',
            'src/Routes',
            'src/marketplace'
        ];

        foreach ($folders as $folder) {
            File::makeDirectory("{$packagePath}/{$folder}", 0755, true);
        }
    }

    private function createStubFiles($packagePath)
    {
        $files = [
            'composer.json.stub' => 'composer.json',
            'module.json.stub' => 'module.json',
            'providers/ServiceProvider.stub' => "src/Providers/{$this->packageName}ServiceProvider.php",
            'providers/EventServiceProvider.stub' => "src/Providers/EventServiceProvider.php",

            'seeders/DatabaseSeeder.stub' => "src/Database/Seeders/{$this->packageName}DatabaseSeeder.php",
            'seeders/PermissionTableSeeder.stub' => 'src/Database/Seeders/PermissionTableSeeder.php',
            'seeders/MarketplaceSettingSeeder.stub' => 'src/Database/Seeders/MarketplaceSettingSeeder.php',
            'seeders/DemoSeeder.stub' => "src/Database/Seeders/Demo{$this->packageName}ItemSeeder.php",
            'menus/company-menu.stub' => 'src/Resources/js/menus/company-menu.ts',
            'menus/superadmin-menu.stub' => 'src/Resources/js/menus/superadmin-menu.ts',
            'pages/Index.stub' => 'src/Resources/js/Pages/Index.tsx',
            'pages/crud/types.stub' => 'src/Resources/js/Pages/Items/types.ts',
            'pages/crud/Index.stub' => 'src/Resources/js/Pages/Items/Index.tsx',
            'pages/crud/Create.stub' => 'src/Resources/js/Pages/Items/Create.tsx',
            'pages/crud/Edit.stub' => 'src/Resources/js/Pages/Items/Edit.tsx',
            'controllers/DashboardController.stub' => "src/Http/Controllers/DashboardController.php",
            'controllers/CrudController.stub' => "src/Http/Controllers/{$this->packageName}ItemController.php",
            'requests/StoreRequest.stub' => "src/Http/Requests/Store{$this->packageName}ItemRequest.php",
            'requests/UpdateRequest.stub' => "src/Http/Requests/Update{$this->packageName}ItemRequest.php",
            'models/Model.stub' => "src/Models/{$this->packageName}Item.php",
            'routes/web-crud.stub' => 'src/Routes/web.php',
            'migrations/create_table.stub' => "src/Database/Migrations/" . date('Y_m_d_His') . "_create_{$this->tableName}_table.php"
        ];

        foreach ($files as $stubFile => $targetFile) {
            $this->createFileFromStub($stubFile, $packagePath . '/' . $targetFile);
        }
    }

    private function createFileFromStub($stubFile, $targetPath)
    {
        $stubPath = base_path('stubs/react-package-stubs/' . $stubFile);

        if (!File::exists($stubPath)) {
            $this->warn("Stub file not found: {$stubPath}");
            return;
        }

        $stub = File::get($stubPath);
        $stub = $this->replaceVariables($stub);

        if (!File::exists(dirname($targetPath))) {
            File::makeDirectory(dirname($targetPath), 0755, true);
        }

        $this->files->put($targetPath, $stub);
    }

    private function replaceVariables($stub)
    {
        $replacements = [
            '$PACKAGE_NAME$' => $this->packageName,
            '$PACKAGE_LOWER$' => $this->packageLower,
            '$PACKAGE_KEBAB$' => $this->packageKebab,
            '$NAMESPACE$' => $this->namespace,
            '$TABLE_NAME$' => $this->tableName,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    private function camelToKebab($name)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $name));
    }
}