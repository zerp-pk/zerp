<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DemoWarehouseSeeder extends Seeder
{
    public function run($userId): void
    {
        $warehouses = [
            ['name' => 'Central Distribution Center', 'address' => '1250 Industrial Blvd', 'city' => 'Los Angeles', 'zip_code' => '90021', 'phone' => '+12135550101', 'email' => 'central@warehouse.com'],
            ['name' => 'East Coast Logistics Hub', 'address' => '875 Commerce Drive', 'city' => 'Atlanta', 'zip_code' => '30309', 'phone' => '+14045550102', 'email' => 'eastcoast@warehouse.com'],
            ['name' => 'West Coast Storage Facility', 'address' => '2100 Pacific Avenue', 'city' => 'Seattle', 'zip_code' => '98101', 'phone' => '+12065550103', 'email' => 'westcoast@warehouse.com'],
            ['name' => 'Midwest Regional Warehouse', 'address' => '3456 Manufacturing Way', 'city' => 'Chicago', 'zip_code' => '60601', 'phone' => '+13125550104', 'email' => 'midwest@warehouse.com'],
            ['name' => 'Texas Distribution Point', 'address' => '789 Freight Lane', 'city' => 'Dallas', 'zip_code' => '75201', 'phone' => '+12145550105', 'email' => 'texas@warehouse.com'],
            ['name' => 'Florida Fulfillment Center', 'address' => '1567 Logistics Park', 'city' => 'Miami', 'zip_code' => '33101', 'phone' => '+13055550106', 'email' => 'florida@warehouse.com'],
            ['name' => 'Northeast Storage Complex', 'address' => '4321 Supply Chain Blvd', 'city' => 'Boston', 'zip_code' => '02101', 'phone' => '+16175550107', 'email' => 'northeast@warehouse.com'],
            ['name' => 'Southwest Depot', 'address' => '987 Distribution Road', 'city' => 'Phoenix', 'zip_code' => '85001', 'phone' => '+16025550108', 'email' => 'southwest@warehouse.com'],
            ['name' => 'Mountain Region Warehouse', 'address' => '2468 Cargo Street', 'city' => 'Denver', 'zip_code' => '80201', 'phone' => '+13035550109', 'email' => 'mountain@warehouse.com'],
            ['name' => 'Pacific Northwest Hub', 'address' => '1357 Shipping Center', 'city' => 'Portland', 'zip_code' => '97201', 'phone' => '+15035550110', 'email' => 'pacific@warehouse.com'],
            ['name' => 'Great Lakes Storage', 'address' => '8642 Warehouse District', 'city' => 'Detroit', 'zip_code' => '48201', 'phone' => '+13135550111', 'email' => 'greatlakes@warehouse.com'],
            ['name' => 'Southern Distribution Hub', 'address' => '5791 Industrial Complex', 'city' => 'Nashville', 'zip_code' => '37201', 'phone' => '+16155550112', 'email' => 'southern@warehouse.com'],
            ['name' => 'Northern California Facility', 'address' => '3698 Tech Valley Drive', 'city' => 'San Francisco', 'zip_code' => '94101', 'phone' => '+14155550113', 'email' => 'norcal@warehouse.com'],
            ['name' => 'Mid-Atlantic Warehouse', 'address' => '7410 Commerce Plaza', 'city' => 'Philadelphia', 'zip_code' => '19101', 'phone' => '+12155550114', 'email' => 'midatlantic@warehouse.com'],
            ['name' => 'Gulf Coast Distribution', 'address' => '9632 Port Authority Way', 'city' => 'Houston', 'zip_code' => '77001', 'phone' => '+17135550115', 'email' => 'gulfcoast@warehouse.com'],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create(array_merge($warehouse, [
                'is_active' => true,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]));
        }
    }
}