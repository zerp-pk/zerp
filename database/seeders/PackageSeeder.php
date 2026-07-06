<?php

namespace Database\Seeders;

use App\Classes\Module;
use App\Events\DefaultData;
use App\Events\GivePermissionToRole;
use App\Models\AddOn;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Artisan;

class PackageSeeder extends Seeder
{
    public function run($userId = null): void
    {
        if(empty($userId)){
          $userId = User::where('email', 'company@example.com')->first()->id;
        }

        // Set by app:install to a package_name slug list; null means
        // "select all" (e.g. a standalone `php artisan db:seed`).
        $selected = app()->bound('zerp.selected_modules') ? app('zerp.selected_modules') : null;

        // Modules live either under packages/local/<name> (legacy, in-repo,
        // for active local development) or vendor/zerp/<slug> (installed as
        // a real Composer package) — scan both.
        $packagePaths = array_merge(
            \Illuminate\Support\Facades\File::isDirectory(base_path('packages/local'))
                ? \Illuminate\Support\Facades\File::directories(base_path('packages/local'))
                : [],
            \Illuminate\Support\Facades\File::isDirectory(base_path('vendor/zerp'))
                ? \Illuminate\Support\Facades\File::directories(base_path('vendor/zerp'))
                : []
        );

        foreach ($packagePaths as $package) {
            $filePath = $package.'/module.json';
            if (!file_exists($filePath)) {
                continue;
            }
            $jsonContent = file_get_contents($filePath);
            $data = json_decode($jsonContent, true);

            $isSelected = is_null($selected) || in_array($data['package_name'], $selected, true);

            $addon = AddOn::where('module', $data['name'])->first();
            if (empty($addon)) {
                $addon = new AddOn();
                $addon->module = $data['name'];
                $addon->name = $data['alias'];
                $addon->monthly_price = $data['monthly_price'] ?? 0;
                $addon->yearly_price = $data['yearly_price'] ?? 0;
                $addon->package_name = $data['package_name'];
                $addon->is_enable = $isSelected;
                $addon->for_admin = $data['for_admin'] ?? false;
                $addon->priority = $data['priority'] ?? 0;
                $addon->save();
            }

            if (!$isSelected) {
                continue;
            }

            $activePackage = UserActiveModule::where('module', $data['name'])->where('user_id', $userId)->first();
            if(empty($activePackage)){
                $activePackage = new UserActiveModule();
                $activePackage->user_id = $userId;
                $activePackage->module = $data['name'];
                $activePackage->save();
            }
        }

        $allEnabled = (new Module())->allEnabled();
        foreach ($allEnabled as $key => $value) {
            try {
                Artisan::call('package:seed', ['packageName' => $value]);
                $this->command?->info("{$value} Seeder Run Successfully!");
            } catch (\Throwable $th) {
                $this->command?->error("Failed to seed package '{$value}': " . $th->getMessage());
                \Log::error("Failed to seed package '{$value}': " . $th->getMessage());
            }
        }

        // static assignPlan
        $plan = Plan::first();
        $user = User::where('email', 'company@example.com')->first();
        $user->active_plan = $plan->id;
        $user->plan_expire_date = date('Y-m-d', strtotime('+10 month'));
        $user->total_user = -1;
        $user->storage_limit = 50000000;
        $user->save();

        $modules = UserActiveModule::where('user_id', $user->id)->pluck('module')->toArray();
        $modules =  implode(',',$modules);
        DefaultData::dispatch($user->id, $modules);
        $client_role = Role::where('name', 'client')->where('created_by', $user->id)->first();
        $staff_role = Role::where('name', 'staff')->where('created_by', $user->id)->first();

        if (!empty($client_role)) {
            GivePermissionToRole::dispatch($client_role->id, 'client', $modules);
        }
        if (!empty($staff_role)) {
            GivePermissionToRole::dispatch($staff_role->id, 'staff', $modules);
        }
    }
}
