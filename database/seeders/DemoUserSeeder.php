<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Global Tech Solutions Ltd', 'email' => 'info@globaltech.com'],
            ['name' => 'Main Street Retailers', 'email' => 'sales@mainstreet.com'],
            ['name' => 'Pinnacle Manufacturing', 'email' => 'admin@pinnacle-mfg.com'],
            ['name' => 'Elite Consulting Group', 'email' => 'contact@eliteconsulting.com'],
            ['name' => 'Sunrise Hospitality', 'email' => 'support@sunrisehospitality.com']
        ];

        foreach ($users as $index => $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'mobile_no' => '+1' . sprintf('%010d', 3000000000 + $index),
                'password' => Hash::make('1234'),
                'type' => 'company',
                'lang' => 'en',
                'email_verified_at' => now(),
                'creator_id' => 1,
                'created_by' => 1
            ]);

            $user->assignRole('company');
            User::CompanySetting($user->id);
            // Make Company's role
            User::MakeRole($user->id);
        }
    }
}