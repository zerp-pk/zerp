<?php

namespace Database\Seeders;

use App\Models\HelpdeskCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class HelpdeskCategorySeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::where('email', 'superadmin@example.com')->first()->id;

        $categories = [
            ['name' => 'Technical Support', 'description' => 'Technical issues and troubleshooting', 'color' => '#3B82F6'],
            ['name' => 'Billing', 'description' => 'Billing and payment related queries', 'color' => '#10b77f'],
            ['name' => 'Feature Request', 'description' => 'New feature suggestions and requests', 'color' => '#F59E0B'],
            ['name' => 'Bug Report', 'description' => 'Software bugs and issues', 'color' => '#EF4444'],
            ['name' => 'General Inquiry', 'description' => 'General questions and information', 'color' => '#8B5CF6'],
        ];

        foreach ($categories as $category) {
            HelpdeskCategory::create([
                'name' => $category['name'],
                'description' => $category['description'],
                'color' => $category['color'],
                'is_active' => true,
                'creator_id' => $userId,
                'created_by' => $userId,
            ]);
        }
    }
}