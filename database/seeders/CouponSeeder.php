<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'name' => 'Welcome Discount',
                'description' => 'Welcome discount for new customers',
                'code' => 'WELCOME10',
                'discount' => 10.00,
                'limit' => 100,
                'type' => 'percentage',
                'minimum_spend' => 50.00,
                'maximum_spend' => null,
                'limit_per_user' => 1,
                'expiry_date' => now()->addMonths(3),
                'included_module' => null,
                'excluded_module' => null,
                'status' => true,
                'created_by' => 1
            ],
            [
                'name' => 'Flat Discount',
                'description' => 'Flat $20 off on orders',
                'code' => 'FLAT20',
                'discount' => 20.00,
                'limit' => 50,
                'type' => 'flat',
                'minimum_spend' => 100.00,
                'maximum_spend' => null,
                'limit_per_user' => 2,
                'expiry_date' => now()->addMonths(6),
                'included_module' => null,
                'excluded_module' => null,
                'status' => true,
                'created_by' => 1
            ],
            [
                'name' => 'Premium Plan Fixed',
                'description' => 'Fixed price for premium plan',
                'code' => 'PREMIUM99',
                'discount' => 99.00,
                'limit' => 25,
                'type' => 'fixed',
                'minimum_spend' => null,
                'maximum_spend' => null,
                'limit_per_user' => 1,
                'expiry_date' => now()->addYear(),
                'included_module' => ['premium'],
                'excluded_module' => null,
                'status' => true,
                'created_by' => 1
            ]
        ];

        foreach ($coupons as $coupon) {
            Coupon::firstOrCreate(
                ['code' => $coupon['code']],
                $coupon
            );
        }
    }
}