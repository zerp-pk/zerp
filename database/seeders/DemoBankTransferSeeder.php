<?php

namespace Database\Seeders;

use App\Models\BankTransferPayment;
use App\Models\User;
use App\Models\Order;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DemoBankTransferSeeder extends Seeder
{
    public function run($userId): void
    {
        $faker = Faker::create();
        $orders = Order::pluck('id')->toArray();
        $users = User::where('email', '!=', 'superadmin@example.com')->limit(5)->pluck('id')->toArray();

        // Only create if orders exist
        if (empty($orders)) {
            return;
        }

        // Shuffle orders to get random selection and limit to available orders
        $shuffledOrders = $faker->randomElements($orders, min(15, count($orders)));

        foreach ($shuffledOrders as $orderId) {
            BankTransferPayment::create([
                'order_id' => strtoupper(substr(uniqid(), -12)),
                'user_id' => $faker->randomElement($users),
                'request' => json_encode([
                    'plan_id' => $faker->randomElement(Plan::pluck('id')->toArray()),
                    'user_counter_input' => $faker->numberBetween(1, 10),
                    'storage_counter_input' => $faker->numberBetween(0, 5),
                    'time_period' => $faker->randomElement(['Month', 'Year']),
                    'bank_name' => $faker->company . ' Bank',
                    'account_holder' => $faker->name,
                    'account_number' => $faker->bankAccountNumber,
                    'transaction_id' => $faker->uuid
                ]),
                'status' => $faker->randomElement(['pending', 'approved', 'rejected']),
                'type' => $faker->randomElement(['Bank Transfer', 'Stripe', 'Paypal']),
                'price' => $faker->randomFloat(2, 10, 500),
                'price_currency' => $faker->randomElement(['USD', 'EUR', 'GBP']),
                'attachment' => $faker->optional()->imageUrl(640, 480, 'business'),
                'created_by' => $userId,
            ]);
        }
    }
}