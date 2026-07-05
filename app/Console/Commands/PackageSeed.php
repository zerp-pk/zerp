<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class PackageSeed extends Command
{
    protected $signature = 'package:seed {packageName?}';
    protected $description = 'Seed a specific package or all packages';

    public function handle()
    {
        $packageName = $this->argument('packageName');
        if ($packageName) {
            $this->seedPackage($packageName);
        } else {
            $this->seedAllPackages();
        }
    }

    protected function seedPackage($packageName)
    {
        $seederClass = $this->getSeederClass($packageName);

        if ($seederClass) {
            $this->info("Seeding {$packageName}...");
            Artisan::call('db:seed', ['--class' => $seederClass, '--force' => true]);
            $this->info("{$packageName} Seeder Run Successfully!");
        } else {
            $this->error("Seeder for package {$packageName} not found.");
        }
    }

    protected function seedAllPackages()
    {
        $packages = $this->getAllPackages();
        foreach ($packages as $package) {
            $this->seedPackage($package);
        }
    }

    protected function getSeederClass($packageName)
    {
        $seederClass = "Zerp\\{$packageName}\\Database\\Seeders\\{$packageName}DatabaseSeeder";
        if (class_exists($seederClass)) {
            return $seederClass;
        }

        return null;
    }

    protected function getAllPackages()
    {
        $packages = [];
        $vendorDir = base_path('packages/local');

        if (File::exists($vendorDir)) {
            $directories = File::directories($vendorDir);
            foreach ($directories as $directory) {
                $packages[] = basename($directory);
            }
        }

        // Modules migrated to real Composer packages (vendor/zerp/<slug>) no
        // longer have a packages/local/<name> directory to scan.
        $packages = array_merge($packages, \App\Models\AddOn::pluck('module')->filter()->toArray());

        return array_values(array_unique($packages));
    }
}