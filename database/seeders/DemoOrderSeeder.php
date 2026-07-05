<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DemoOrderSeeder extends Seeder
{
    public function run($userId): void
    {
        $faker = Faker::create();
        $plans = Plan::limit(5)->get();
        $currentYear = now()->year;

        // Create orders for each month with varying quantities
        for ($month = 1; $month <= 12; $month++) {
            $ordersCount = $faker->numberBetween(5, 15); // Random orders per month

            for ($i = 0; $i < $ordersCount; $i++) {
                $plan = $faker->randomElement($plans);
                $price = $faker->randomFloat(2, 29, 299);

                // Random date within the month
                $createdAt = $faker->dateTimeBetween(
                    "$currentYear-$month-01",
                    "$currentYear-$month-" . date('t', mktime(0, 0, 0, $month, 1, $currentYear))
                );

                Order::create([
                    'order_id' => strtoupper(substr(uniqid(), -12)),
                    'name' => $faker->name,
                    'email' => $faker->safeEmail,
                    'card_number' => '**** **** **** ' . $faker->numerify('####'),
                    'card_exp_month' => $faker->numberBetween(1, 12),
                    'card_exp_year' => $faker->numberBetween(2024, 2030),
                    'plan_name' => $plan->name,
                    'plan_id' => $plan->id,
                    'price' => $price,
                    'discount_amount' => $faker->randomFloat(2, 0, $price * 0.3),
                    'currency' => $faker->randomElement(['USD', 'EUR', 'GBP']),
                    'txn_id' => $faker->uuid,
                    'payment_status' => $faker->randomElement(['succeeded']),
                    'payment_type' => $faker->randomElement(['Stripe', 'Paypal', 'Bank Transfer']),
                    'receipt' => $faker->optional()->url,
                    'created_by' => $userId,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }
}