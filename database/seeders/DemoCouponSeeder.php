<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DemoCouponSeeder extends Seeder
{
    public function run($userId): void
    {
        $faker = Faker::create();
        $modules = ['crm', 'hrm', 'pos', 'accounting', 'project'];

        for ($i = 0; $i < 12; $i++) {
            $type = $faker->randomElement(['percentage', 'flat', 'fixed']);
            $discount = match($type) {
                'percentage' => $faker->numberBetween(5, 50),
                'flat' => $faker->randomFloat(2, 5, 100),
                'fixed' => $faker->randomFloat(2, 99, 199)
            };

            Coupon::create([
                'name' => $faker->words(2, true) . ' ' . ucfirst($type),
                'description' => $faker->sentence,
                'code' => strtoupper($faker->lexify('???') . $faker->numerify('##')),
                'discount' => $discount,
                'limit' => $faker->numberBetween(10, 100),
                'type' => $type,
                'minimum_spend' => $faker->optional()->randomFloat(2, 20, 200),
                'maximum_spend' => $faker->optional()->randomFloat(2, 500, 2000),
                'limit_per_user' => $faker->numberBetween(1, 5),
                'expiry_date' => $faker->dateTimeBetween('now', '+1 year'),
                'included_module' => $faker->optional()->randomElements($modules, $faker->numberBetween(1, 3)),
                'excluded_module' => $faker->optional()->randomElements($modules, $faker->numberBetween(1, 2)),
                'status' => $faker->boolean(85),
                'created_by' => $userId,
            ]);
        }
    }
}