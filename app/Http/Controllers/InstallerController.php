<?php

namespace App\Http\Controllers;

use App\Models\AddOn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Inertia\Inertia;

class InstallerController extends Controller
{
    public function welcome()
    {
        return Inertia::render('Installer/Welcome');
    }

    public function requirements()
    {
        $requirements = $this->checkRequirements();
        return Inertia::render('Installer/Requirements', compact('requirements'));
    }

    public function permissions()
    {
        $permissions = $this->checkPermissions();
        return Inertia::render('Installer/Permissions', compact('permissions'));
    }

    public function environment()
    {
        $timezones = \DateTimeZone::listIdentifiers();
        return Inertia::render('Installer/Environment', compact('timezones'));
    }

    public function environmentStore(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'app_name' => 'required|string|max:255',
                'app_url' => 'required|url',
                'app_timezone' => 'required|string|timezone',
                'db_connection' => 'required|string',
                'db_host' => 'required_unless:db_connection,sqlite|string',
                'db_port' => 'required_unless:db_connection,sqlite|numeric',
                'db_database' => 'required|string',
                'db_username' => 'required_unless:db_connection,sqlite|string',
                'db_password' => 'nullable|string',
            ], [
                'app_name.required' => __('Application name is required.'),
                'app_name.string' => __('Application name must be a valid string.'),
                'app_name.max' => __('Application name must not exceed 255 characters.'),
                'app_url.required' => __('Application URL is required.'),
                'app_url.url' => __('Please enter a valid application URL.'),
                'app_timezone.required' => __('Timezone is required.'),
                'app_timezone.timezone' => __('Please select a valid timezone.'),
                'db_connection.required' => __('Database connection type is required.'),
                'db_connection.string' => __('Database connection must be a valid string.'),
                'db_host.required_unless' => __('Database host is required for non-SQLite connections.'),
                'db_host.string' => __('Database host must be a valid string.'),
                'db_port.required_unless' => __('Database port is required for non-SQLite connections.'),
                'db_port.numeric' => __('Database port must be a valid number.'),
                'db_database.required' => __('Database name is required.'),
                'db_database.string' => __('Database name must be a valid string.'),
                'db_username.required_unless' => __('Database username is required for non-SQLite connections.'),
                'db_username.string' => __('Database username must be a valid string.'),
                'db_password.string' => __('Database password must be a valid string.'),
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $data = $validator->validated();

            // Test database connection before saving
            if ($data['db_connection'] !== 'sqlite') {
                $this->testDatabaseConnection($data);
            }

            $this->createEnvFile($data);

            return redirect('/install/database');
        } catch (\Exception $e) {
            return back()->withErrors(['database' => $e->getMessage()])->withInput();
        }
    }

    public function database()
    {
        return Inertia::render('Installer/Database');
    }

    public function databaseStore()
    {
        try {
            // Test database connection first
            DB::connection()->getPdo();

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

            $modules = $this->getAllAvailableModules();
            foreach ($modules as $module) {
                $this->enableModule($module['name']);
            }

            return redirect('/install/final');
        } catch (\Exception $e) {
            return back()->withErrors(['database' => 'Database connection failed. Please check your database credentials.']);
        }
    }

    public function addons()
    {
        $modules = $this->getAllAvailableModules();
        return Inertia::render('Installer/Addons', compact('modules'));
    }

    public function addonsStore(Request $request)
    {
        try {
            $moduleName = $request->input('module');

            if ($moduleName) {
                $this->enableModule($moduleName);

                $nextModule = $this->getNextModule($moduleName);
                if ($nextModule) {
                    return response()->json([
                        'success' => true,
                        'next_module' => $nextModule,
                        'message' => "Module {$moduleName} installed successfully"
                    ]);
                } else {
                    return response()->json([
                        'success' => true,
                        'completed' => true,
                        'message' => 'All modules installed successfully'
                    ]);
                }
            }

            return response()->json(['success' => false, 'message' => 'No module specified']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function final()
    {
        $this->createInstalledFile();

        $credentials = [
            'admin' => [
                'email' => 'superadmin@example.com',
                'password' => '1234'
            ],
            'company' => [
                'email' => 'company@example.com',
                'password' => '1234'
            ]
        ];

        return Inertia::render('Installer/Final', compact('credentials'));
    }

    private function checkRequirements()
    {
        return [
            'php' => [
                'name' => 'PHP Version (>= 8.2)',
                'check' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION
            ],
            'extensions' => [
                'openssl' => [
                    'name' => 'OpenSSL Extension',
                    'check' => extension_loaded('openssl')
                ],
                'pdo' => [
                    'name' => 'PDO Extension',
                    'check' => extension_loaded('pdo')
                ],
                'mbstring' => [
                    'name' => 'Mbstring Extension',
                    'check' => extension_loaded('mbstring')
                ],
                'tokenizer' => [
                    'name' => 'Tokenizer Extension',
                    'check' => extension_loaded('tokenizer')
                ],
                'xml' => [
                    'name' => 'XML Extension',
                    'check' => extension_loaded('xml')
                ],
                'ctype' => [
                    'name' => 'Ctype Extension',
                    'check' => extension_loaded('ctype')
                ],
                'json' => [
                    'name' => 'JSON Extension',
                    'check' => extension_loaded('json')
                ],
                'curl' => [
                    'name' => 'cURL Extension',
                    'check' => extension_loaded('curl')
                ],
                'zip' => [
                    'name' => 'Zip Extension',
                    'check' => extension_loaded('zip')
                ]
            ]
        ];
    }

    private function checkPermissions()
    {
        $paths = [
            'storage/app' => storage_path('app'),
            'storage/framework' => storage_path('framework'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $permissions = [];
        foreach ($paths as $name => $path) {
            $permissions[$name] = [
                'name' => $name,
                'path' => $path,
                'check' => is_writable($path)
            ];
        }

        return $permissions;
    }

    private function createEnvFile($data)
    {
        $envContent = "APP_NAME=\"{$data['app_name']}\"\n";
        $envContent .= "APP_ENV=production\n";
        $envContent .= "APP_KEY=" . config('app.key') . "\n";
        $envContent .= "APP_DEBUG=false\n";
        $envContent .= "APP_TIMEZONE={$data['app_timezone']}\n";
        $envContent .= "APP_URL={$data['app_url']}\n\n";

        $envContent .= "APP_LOCALE=en\n";
        $envContent .= "APP_FALLBACK_LOCALE=en\n";
        $envContent .= "APP_FAKER_LOCALE=en_US\n\n";

        $envContent .= "LOG_CHANNEL=stack\n";
        $envContent .= "LOG_LEVEL=error\n\n";

        $envContent .= "DB_CONNECTION={$data['db_connection']}\n";
        if ($data['db_connection'] !== 'sqlite') {
            $envContent .= "DB_HOST={$data['db_host']}\n";
            $envContent .= "DB_PORT={$data['db_port']}\n";
            $envContent .= "DB_USERNAME={$data['db_username']}\n";
            $envContent .= "DB_PASSWORD={$data['db_password']}\n";
        }
        $envContent .= "DB_DATABASE={$data['db_database']}\n\n";

        $envContent .= "SESSION_DRIVER=file\n";
        $envContent .= "SESSION_LIFETIME=120\n";
        $envContent .= "CACHE_DRIVER=file\n";
        $envContent .= "CACHE_STORE=file\n";
        $envContent .= "QUEUE_CONNECTION=database\n\n";

        $envContent .= "MAIL_MAILER=log\n";
        $envContent .= "MAIL_FROM_ADDRESS=\"noreply@example.com\"\n";
        $envContent .= "MAIL_FROM_NAME=\"\${APP_NAME}\"\n\n";

        $envContent .= "VITE_APP_NAME=\"\${APP_NAME}\"\n";

        File::put(base_path('.env'), $envContent);

        // Clear config cache and reload environment
        try {
            Artisan::call('config:clear');
            Artisan::call('route:clear');
        } catch (\Exception $e) {
            // Ignore cache clear errors during installation
        }
    }

    private function testDatabaseConnection($data)
    {
        try {
            $pdo = new \PDO(
                $data['db_connection'] . ':host=' . $data['db_host'] . ';port=' . $data['db_port'] . ';dbname=' . $data['db_database'],
                $data['db_username'],
                $data['db_password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            $pdo = null;
        } catch (\PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
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

    private function getNextModule($currentModule)
    {
        $modules = $this->getAllAvailableModules();
        $currentIndex = array_search($currentModule, array_column($modules, 'name'));

        if ($currentIndex !== false && isset($modules[$currentIndex + 1])) {
            return $modules[$currentIndex + 1];
        }

        return null;
    }

    private function enableModule($moduleName)
    {
        // Validate module name to prevent path traversal
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $moduleName)) {
            throw new \Exception('Invalid module name');
        }

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

            Artisan::call('migrate --path=/packages/local/' . $moduleName . '/src/Database/Migrations --force');
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
            Artisan::call('migrate --path=/packages/local/' . $moduleName . '/src/Database/Migrations --force');
            Artisan::call('package:seed ' . $moduleName);
            $addon->is_enable = 1;
            $addon->save();
        }
    }

    private function createInstalledFile()
    {
        File::put(storage_path('installed'), 'install ' . date('Y-m-d H:i:s'));
    }
}
