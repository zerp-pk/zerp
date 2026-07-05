<?php

namespace Database\Seeders;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DemoLoginHistorySeeder extends Seeder
{
    public function run($userId = null)
    {
        $faker = Faker::create();
        $users = User::where('created_by', $userId)->get();

        $osNames = ['Windows', 'Linux', 'macOS', 'Android', 'iOS'];
        $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'];
        $deviceTypes = ['desktop', 'mobile', 'tablet'];

        foreach ($users as $user) {
            $ip = $faker->ipv4;
            $details = [
                "as" => $faker->company,
                "isp" => $faker->company . ' ISP',
                "lat" => $faker->latitude,
                "lon" => $faker->longitude,
                "org" => $faker->company,
                "zip" => $faker->postcode,
                "city" => $faker->city,
                "query" => $ip,
                "region" => $faker->state,
                "status" => "success",
                "country" => $faker->country,
                "os_name" => $faker->randomElement($osNames),
                "timezone" => $faker->timezone,
                "regionName" => $faker->state,
                "countryCode" => $faker->countryCode,
                "device_type" => $faker->randomElement($deviceTypes),
                "browser_name" => $faker->randomElement($browsers),
                "referrer_host" => $faker->domainName,
                "referrer_path" => "/login",
                "browser_language" => $faker->randomElement(['en', 'es', 'fr', 'de', 'it'])
            ];

            LoginHistory::create([
                'user_id' => $user->id,
                'ip' => $ip,
                'date' => now()->subDays(rand(1, 30)),
                'details' => $details,
                'type' => $user->type,
                'created_by' => $userId
            ]);
        }
    }
}