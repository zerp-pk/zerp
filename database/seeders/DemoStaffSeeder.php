<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DemoStaffSeeder extends Seeder
{
    public function run($userId)
    {
        $users = [
            ['name' => 'John Smith', 'email' => 'john.smith@company.com', 'type' => 'staff', 'role' => 'staff'],
            ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@client.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Michael Brown', 'email' => 'michael.brown@company.com', 'type' => 'staff', 'role' => 'staff'],
            ['name' => 'Emily Davis', 'email' => 'emily.davis@client.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'David Wilson', 'email' => 'david.wilson@company.com', 'type' => 'staff', 'role' => 'staff'],
            ['name' => 'Lisa Anderson', 'email' => 'lisa.anderson@client.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Robert Taylor', 'email' => 'robert.taylor@company.com', 'type' => 'staff', 'role' => 'staff'],
            ['name' => 'Jennifer Martinez', 'email' => 'jennifer.martinez@client.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'James Garcia', 'email' => 'james.garcia@company.com', 'type' => 'staff', 'role' => 'staff'],
            ['name' => 'Maria Rodriguez', 'email' => 'maria.rodriguez@client.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Christopher Lee', 'email' => 'christopher.lee@company.com', 'type' => 'staff', 'role' => 'staff'],
            ['name' => 'Amanda White', 'email' => 'amanda.white@client.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Daniel Thompson', 'email' => 'daniel.thompson@company.com', 'type' => 'staff', 'role' => 'staff'],
            ['name' => 'Jessica Harris', 'email' => 'jessica.harris@client.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Matthew Clark', 'email' => 'matthew.clark@company.com', 'type' => 'staff', 'role' => 'staff'],
            ['name' => 'Ashley Lewis', 'email' => 'ashley.lewis@client.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Anthony Walker', 'email' => 'anthony.walker@company.com', 'type' => 'staff', 'role' => 'staff'],
            ['name' => 'Michelle Hall', 'email' => 'michelle.hall@client.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Mark Allen', 'email' => 'mark.allen@company.com', 'type' => 'staff', 'role' => 'staff'],
            ['name' => 'Nicole Young', 'email' => 'nicole.young@client.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Alex Vendor', 'email' => 'alex.vendor@supplier.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Sam Supplier', 'email' => 'sam.supplier@vendor.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Tech Solutions Inc', 'email' => 'contact@techsolutions.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Global Supplies Co', 'email' => 'info@globalsupplies.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Prime Materials Ltd', 'email' => 'sales@primematerials.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Elite Vendors Group', 'email' => 'orders@elitevendors.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Quality Parts Corp', 'email' => 'support@qualityparts.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Swift Logistics', 'email' => 'dispatch@swiftlogistics.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Mega Distributors', 'email' => 'wholesale@megadist.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Pro Equipment Ltd', 'email' => 'rentals@proequipment.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Smart Systems Inc', 'email' => 'tech@smartsystems.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Reliable Resources', 'email' => 'procurement@reliable.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Advanced Materials', 'email' => 'orders@advancedmat.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Express Suppliers', 'email' => 'express@suppliers.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'Industrial Partners', 'email' => 'partners@industrial.com', 'type' => 'vendor', 'role' => 'vendor'],
            ['name' => 'ABC Corporation', 'email' => 'contact@abccorp.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'XYZ Industries', 'email' => 'info@xyzind.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Global Solutions Ltd', 'email' => 'sales@globalsol.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Tech Innovations Inc', 'email' => 'hello@techinno.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Prime Services Co', 'email' => 'support@primeserv.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Elite Enterprises', 'email' => 'admin@eliteent.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Smart Systems Corp', 'email' => 'contact@smartsys.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Dynamic Solutions', 'email' => 'info@dynsol.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Future Tech Ltd', 'email' => 'hello@futuretech.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Innovative Corp', 'email' => 'contact@innovcorp.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Advanced Systems', 'email' => 'support@advsys.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Professional Services', 'email' => 'info@proserv.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Quality Solutions Inc', 'email' => 'sales@qualsol.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Reliable Partners', 'email' => 'contact@relpart.com', 'type' => 'client', 'role' => 'client'],
            ['name' => 'Strategic Consulting', 'email' => 'hello@stratcon.com', 'type' => 'client', 'role' => 'client'],
        ];

        foreach ($users as $index => $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'email_verified_at' => now(),
                'password' => Hash::make('1234'),
                'mobile_no' => '+1' . sprintf('%010d', 2000000000 + $index),
                'type' => $userData['type'],
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);

            $user->assignRole($userData['role']);
        }
    }
}