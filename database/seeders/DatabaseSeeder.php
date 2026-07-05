<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        (new PermissionRoleSeeder())->run();
        (new DefultSetting())->run();
        (new PlanSeeder())->run();
        (new EmailTemplatesSeeder())->run();
        (new NotificationsTableSeeder())->run();

        $userId = User::where('email', 'company@example.com')->first()->id;
        User::CompanySetting($userId);

        // Always register/enable available modules into add_ons — this must
        // not be gated behind demo data, or a non-demo install ends up with
        // an empty module list and a feature-less /plans page.
        (new PackageSeeder())->run($userId);

        if(config('app.run_demo_seeder'))
        {
            // // Pass $userId to your custom seeder


            (new CouponSeeder())->run();
            (new DemoUserSeeder())->run();

            (new DemoStaffSeeder())->run($userId);
            (new DemoLoginHistorySeeder())->run($userId);
            (new DemoWarehouseSeeder())->run($userId);
            (new HelpdeskCategorySeeder())->run();
            (new HelpdeskTicketSeeder())->run($userId);
            (new HelpdeskReplySeeder())->run($userId);
            (new DemoOrderSeeder())->run($userId);
            (new DemoCouponSeeder())->run($userId);
            (new DemoBankTransferSeeder())->run($userId);
            (new DemoCouponDetailsSeeder())->run($userId);
            (new MessengerSeeder())->run();

            // in this seeder product
            (new DemoTransferSeeder())->run($userId);
        }
    }
}
