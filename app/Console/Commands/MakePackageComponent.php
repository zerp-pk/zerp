<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class MakePackageComponent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:package {type} {name} {package} {--m}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new component in the specified package';

    /**
     * Execute the console command.
     */
    public $packageName;

    public function handle()
    {
        $type = $this->argument('type');
        $name = $this->argument('name');
        $package = $this->argument('package');
        $createMigration = $this->option('m');

        $this->packageName = $this->camelToKebab($package);

        $baseDir = base_path("packages/local/$package/src");
        $namespace = "Zerp\\$package\\";

        switch ($type) {
            case 'controller':
                $this->createController($name, $baseDir, $namespace);
                break;
            case 'model':
                $this->createModel($name, $baseDir, $namespace, $createMigration,$package);
                break;
            case 'migration':
                $this->createMigration($name, $package);
                break;
            case 'middleware':
                $this->createMiddleware($name, $baseDir, $namespace);
                break;
            case 'event':
                $this->createEvent($name, $baseDir, $namespace);
                break;
            case 'listener':
                $this->createListener($name, $baseDir, $namespace);
                break;
            case 'provider':
                $this->createProvider($name, $baseDir, $namespace);
                break;
            case 'seeder':
                $this->createSeeder($name, $baseDir, $namespace);
                break;
            case 'request':
                $this->createRequest($name, $baseDir, $namespace);
                break;
            case 'trait':
                $this->createTrait($name, $baseDir, $namespace);
                break;
            case 'helper':
                $this->createHelper($name, $baseDir, $namespace);
                break;
            default:
                $this->error("Invalid type provided. Available types: controller, model, migration, middleware, event, listener, provider, seeder, request, trait, helper");
                break;
        }
    }

    function camelToKebab($name)
    {
        $packageName = preg_replace('/([a-z])([A-Z])/', '$1-$2', $name);
        return strtolower($packageName);
    }
    function camelToSnake($name)
    {
        $packageName = preg_replace('/([a-z])([A-Z])/', '$1_$2', $name);
        return strtolower($packageName);
    }

    function pluralize($word)
    {
        $plural = [
            '/(quiz)$/i' => '\1zes',
            '/^(ox)$/i' => '\1en',
            '/([m|l])ouse$/i' => '\1ice',
            '/(matr|vert|ind)ix|ex$/i' => '\1ices',
            '/(x|ch|ss|sh)$/i' => '\1es',
            '/([^aeiouy]|qu)y$/i' => '\1ies',
            '/(hive)$/i' => '\1s',
            '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
            '/sis$/i' => 'ses',
            '/([ti])um$/i' => '\1a',
            '/(buffal|tomat)o$/i' => '\1oes',
            '/(bu)s$/i' => '\1ses',
            '/(alias|status)$/i' => '\1es',
            '/(octop|vir)us$/i' => '\1i',
            '/(ax|test)is$/i' => '\1es',
            '/s$/i' => 's',
            '/$/' => 's',
        ];

        foreach ($plural as $pattern => $replacement) {
            if (preg_match($pattern, $word)) {
                return preg_replace($pattern, $replacement, $word);
            }
        }
        return $word;
    }


    protected function createController($name, $baseDir, $namespace)
    {
        $path = "$baseDir/Http/Controllers/{$name}.php";
        $namespace .= "Http\\Controllers";

        if (File::exists($path)) {
            $this->error("Controller already exists!");
            return;
        }

        $stubPath = base_path('stubs/react-package-stubs/controller.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file does not exist!");
            return;
        }

        $stub = File::get($stubPath);
        $stub = str_replace('$CLASS_NAMESPACE$', $namespace, $stub);
        $stub = str_replace('$CLASS$', $name, $stub);
        $stub = str_replace('$PACKAGE_NAME$', $this->packageName, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);

        $this->info("Controller $name created successfully.");
    }

    protected function createModel($name, $baseDir, $namespace, $createMigration,$package)
    {
        $path = "$baseDir/Models/{$name}.php";
        $namespace .= "Models";

        if (File::exists($path)) {
            $this->error("Model already exists!");
            return;
        }

        $stubPath = base_path('stubs/react-package-stubs/model.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file does not exist!");
            return;
        }

        $stub = File::get($stubPath);
        $stub = str_replace('$NAMESPACE$', $namespace, $stub);
        $stub = str_replace('$CLASS$', $name, $stub);

        File::ensureDirectoryExists(dirname($path));

        File::put($path, $stub);
        $this->info("Model $name Created Successfully!");

        if ($createMigration) {
            $this->createMigration("create_{$this->camelToSnake($this->pluralize($name))}_table", $package);
        }
    }

    protected function createMigration($name, $package)
    {
        $migrationPath = base_path("packages/local/$package/src/Database/Migrations");
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $fullPath = "$migrationPath/$fileName";

        if (File::exists($fullPath)) {
            $this->error("Migration already exists!");
            return;
        }

        $stubPath = base_path('stubs/react-package-stubs/migrations/migration.stub');
        if (!File::exists($stubPath)) {
            $this->error("Migration stub file does not exist!");
            return;
        }

        $stub = File::get($stubPath);

        // Extract table name from migration name
        $tableName = str_replace(['create_', '_table'], '', $name);
        $stub = str_replace('$TABLE_NAME$', $tableName, $stub);

        File::ensureDirectoryExists($migrationPath);
        File::put($fullPath, $stub);

        $this->info("Migration $name created successfully.");
    }

    protected function createMiddleware($name, $baseDir, $namespace)
    {
        $path = "$baseDir/Http/Middleware/{$name}.php";
        $namespace .= "Http\\Middleware";

        if (File::exists($path)) {
            $this->error("Middleware already exists!");
            return;
        }

        $stubPath = base_path('stubs/react-package-stubs/middleware.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file does not exist!");
            return;
        }

        $stub = File::get($stubPath);
        $stub = str_replace('$NAMESPACE$', $namespace, $stub);
        $stub = str_replace('$CLASS$', $name, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);

        $this->info("Middleware $name Created Successfully!");
    }

    protected function createEvent($name, $baseDir, $namespace)
    {
        $path = "$baseDir/Events/{$name}.php";
        $namespace .= "Events";

        if (File::exists($path)) {
            $this->error("Event already exists!");
            return;
        }

        $stubPath = base_path('stubs/react-package-stubs/event.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file does not exist!");
            return;
        }

        $stub = File::get($stubPath);
        $stub = str_replace('$NAMESPACE$', $namespace, $stub);
        $stub = str_replace('$CLASS$', $name, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);

        $this->info("Event $name Created Successfully!");
    }

    protected function createListener($name, $baseDir, $namespace)
    {
        $path = "$baseDir/Listeners/{$name}.php";
        $namespace .= "Listeners";

        if (File::exists($path)) {
            $this->error("Listener already exists!");
            return;
        }

        $stubPath = base_path('stubs/react-package-stubs/listener.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file does not exist!");
            return;
        }

        $stub = File::get($stubPath);
        $stub = str_replace('$NAMESPACE$', $namespace, $stub);
        $stub = str_replace('$CLASS$', $name, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);

        $this->info("Listener $name Created Successfully!");
    }

    protected function createProvider($name, $baseDir, $namespace)
    {
        $path = "$baseDir/Providers/{$name}.php";
        $namespace .= "Providers";

        if (File::exists($path)) {
            $this->error("Provider already exists!");
            return;
        }

        $stubPath = base_path('stubs/react-package-stubs/provider.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file does not exist!");
            return;
        }

        $stub = File::get($stubPath);
        $stub = str_replace('$NAMESPACE$', $namespace, $stub);
        $stub = str_replace('$CLASS$', $name, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);

        $this->info("Provider $name Created Successfully!");
    }

    protected function createSeeder($name, $baseDir, $namespace)
    {
        $path = "$baseDir/Database/Seeders/{$name}DatabaseSeeder.php";
        $namespace .= "Database\\Seeders";

        if (File::exists($path)) {
            $this->error("Seeder already exists!");
            return;
        }

        $stubPath = base_path('stubs/react-package-stubs/seeder.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file does not exist!");
            return;
        }

        $stub = File::get($stubPath);
        $stub = str_replace('$NAMESPACE$', $namespace, $stub);
        $stub = str_replace('$CLASS$', $name, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);

        $this->info("Seeder $name Created Successfully!");
    }

    protected function createRequest($name, $baseDir, $namespace)
    {
        $path = "$baseDir/Http/Requests/{$name}.php";
        $namespace .= "Http\\Requests";

        if (File::exists($path)) {
            $this->error("Request already exists!");
            return;
        }

        $stubPath = base_path('stubs/react-package-stubs/request.stub');
        if (!File::exists($stubPath)) {
            $this->error("Request stub file does not exist!");
            return;
        }

        $stub = File::get($stubPath);
        $stub = str_replace('$NAMESPACE$', $namespace, $stub);
        $stub = str_replace('$CLASS$', $name, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);

        $this->info("Request $name Created Successfully!");
    }

    protected function createTrait($name, $baseDir, $namespace)
    {
        $path = "$baseDir/Traits/{$name}.php";
        $namespace .= "Traits";

        if (File::exists($path)) {
            $this->error("Trait already exists!");
            return;
        }

        $stubPath = base_path('stubs/react-package-stubs/trait.stub');
        if (!File::exists($stubPath)) {
            $this->error("Trait stub file does not exist!");
            return;
        }

        $stub = File::get($stubPath);
        $stub = str_replace('$NAMESPACE$', $namespace, $stub);
        $stub = str_replace('$CLASS$', $name, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);

        $this->info("Trait $name Created Successfully!");
    }

    protected function createHelper($name, $baseDir, $namespace)
    {
        $path = "$baseDir/Helpers/{$name}.php";
        $namespace .= "Helpers";

        if (File::exists($path)) {
            $this->error("Helper already exists!");
            return;
        }

        $stubPath = base_path('stubs/react-package-stubs/helper.stub');
        if (!File::exists($stubPath)) {
            $this->error("Helper stub file does not exist!");
            return;
        }

        $stub = File::get($stubPath);
        $stub = str_replace('$NAMESPACE$', $namespace, $stub);
        $stub = str_replace('$CLASS$', $name, $stub);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $stub);

        $this->info("Helper $name Created Successfully!");
    }
}
