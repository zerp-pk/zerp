<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Plans seeded before this migration had no storage_limit, so they landed on
     * the column default of 0 and every company subscribed to one got a 0 byte
     * quota: the first media upload failed with "Storage limit exceeded".
     * See zerp-pk/zerp#89.
     */
    public function up(): void
    {
        // 5 GB, stored in KB, matching PlanSeeder's free plan.
        $default = 5 * 1024 * 1024;

        DB::table('plans')->where('storage_limit', '<=', 0)->update(['storage_limit' => $default]);

        // Companies already sitting on a 0 quota inherit their plan's limit.
        DB::table('users')
            ->join('plans', 'plans.id', '=', 'users.active_plan')
            ->where('users.storage_limit', '<=', 0)
            ->update(['users.storage_limit' => DB::raw('plans.storage_limit')]);
    }

    public function down(): void
    {
        // ponytail: one way backfill, the old value was a bug.
    }
};
