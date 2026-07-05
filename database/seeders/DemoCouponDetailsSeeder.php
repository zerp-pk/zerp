<?php

namespace Database\Seeders;

use App\Models\UserCoupon;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DemoCouponDetailsSeeder extends Seeder
{
    public function run($userId): void
    {
        $faker = Faker::create();
        $coupons = Coupon::pluck('id')->toArray();
        $users = User::where('email', '!=', 'superadmin@example.com')->limit(10)->pluck('id')->toArray();
        $orders = Order::pluck('order_id')->toArray();

        if (empty($coupons) || empty($users)) {
            return;
        }

        for ($i = 0; $i < 25; $i++) {
            UserCoupon::create([
                'coupon_id' => $faker->randomElement($coupons),
                'user_id' => $faker->randomElement($users),
                'order_id' => $faker->optional(70)->randomElement($orders),
            ]);
        }
    }
}