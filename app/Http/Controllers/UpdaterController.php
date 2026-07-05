<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UpdaterController extends Controller
{
    public function index()
    {
        if (!Auth::user()->hasRole('superadmin')) {
            abort(403, 'Unauthorized access');
        }

        $pendingMigrations = $this->getPendingMigrations();
        $hasUpdates = count($pendingMigrations) > 0;

        return Inertia::render('Updater/Index', [
            'hasUpdates' => $hasUpdates,
            'pendingMigrations' => $pendingMigrations
        ]);
    }

    public function update(Request $request)
    {
        if (!Auth::user()->hasRole('superadmin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        try {
            // Run pending migrations
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

            // Clear caches
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            // Update installed file with update action
            $this->updateInstalledFile();

            return response()->json([
                'success' => true,
                'message' => 'System updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ]);
        }
    }

    private function getPendingMigrations()
    {
        try {
            // Get all migration files
            $allMigrations = [];

            // Core migrations
            $files = glob(database_path('migrations') . '/*.php');
            foreach ($files as $file) {
                $allMigrations[] = basename($file, '.php');
            }

            // Package migrations
            $packageDirs = glob(base_path('packages/local/*/src/Database/Migrations'), GLOB_ONLYDIR);
            foreach ($packageDirs as $dir) {
                $files = glob($dir . '/*.php');
                foreach ($files as $file) {
                    $allMigrations[] = basename($file, '.php');
                }
            }

            // Get ran migrations from database
            $ranMigrations = DB::table('migrations')->pluck('migration')->toArray();

            // Find pending migrations
            $pendingMigrations = array_diff($allMigrations, $ranMigrations);

            return array_values($pendingMigrations);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function updateInstalledFile()
    {
        try {
            $installedPath = storage_path('installed');
            $existingContent = '';

            if (File::exists($installedPath)) {
                $existingContent = File::get($installedPath) . "\n";
            }

            $newContent = $existingContent . 'update ' . date('Y-m-d H:i:s');
            File::put($installedPath, $newContent);
        } catch (\Exception $e) {
            // Ignore errors in updating installed file
        }
    }
}