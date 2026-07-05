<?php

namespace Database\Seeders;

use App\Models\Transfer;
use App\Models\Warehouse;
use Zerp\ProductService\Models\ProductServiceItem;
use Illuminate\Database\Seeder;

class DemoTransferSeeder extends Seeder
{
    public function run($userId): void
    {
        $warehouses = Warehouse::where('created_by', $userId)->pluck('id')->toArray();
        $products = ProductServiceItem::where('created_by', $userId)->pluck('id')->toArray();

        if (empty($warehouses) || count($warehouses) < 2 || empty($products)) {
            return;
        }

        $transfers = [
            ['from_warehouse' => $warehouses[0], 'to_warehouse' => $warehouses[1], 'product_id' => $products[0] ?? 1, 'quantity' => 25, 'date' => '2024-12-01'],
            ['from_warehouse' => $warehouses[1], 'to_warehouse' => $warehouses[2] ?? $warehouses[0], 'product_id' => $products[1] ?? 1, 'quantity' => 15, 'date' => '2024-12-02'],
            ['from_warehouse' => $warehouses[0], 'to_warehouse' => $warehouses[2] ?? $warehouses[1], 'product_id' => $products[2] ?? 1, 'quantity' => 30, 'date' => '2024-12-03'],
            ['from_warehouse' => $warehouses[2] ?? $warehouses[1], 'to_warehouse' => $warehouses[0], 'product_id' => $products[0] ?? 1, 'quantity' => 10, 'date' => '2024-12-04'],
            ['from_warehouse' => $warehouses[1], 'to_warehouse' => $warehouses[0], 'product_id' => $products[3] ?? 1, 'quantity' => 20, 'date' => '2024-12-05'],
            ['from_warehouse' => $warehouses[0], 'to_warehouse' => $warehouses[1], 'product_id' => $products[4] ?? 1, 'quantity' => 35, 'date' => '2024-12-06'],
            ['from_warehouse' => $warehouses[2] ?? $warehouses[1], 'to_warehouse' => $warehouses[1], 'product_id' => $products[1] ?? 1, 'quantity' => 12, 'date' => '2024-12-07'],
            ['from_warehouse' => $warehouses[1], 'to_warehouse' => $warehouses[2] ?? $warehouses[0], 'product_id' => $products[5] ?? 1, 'quantity' => 18, 'date' => '2024-12-08'],
            ['from_warehouse' => $warehouses[0], 'to_warehouse' => $warehouses[2] ?? $warehouses[1], 'product_id' => $products[2] ?? 1, 'quantity' => 22, 'date' => '2024-12-09'],
            ['from_warehouse' => $warehouses[1], 'to_warehouse' => $warehouses[0], 'product_id' => $products[6] ?? 1, 'quantity' => 28, 'date' => '2024-12-10'],
        ];

        foreach ($transfers as $transfer) {
            Transfer::create(array_merge($transfer, [
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}
