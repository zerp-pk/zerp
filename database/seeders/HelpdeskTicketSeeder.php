<?php

namespace Database\Seeders;

use App\Models\HelpdeskTicket;
use App\Models\HelpdeskCategory;
use Illuminate\Database\Seeder;

class HelpdeskTicketSeeder extends Seeder
{
    public function run($userId): void
    {
        $categories = HelpdeskCategory::get()->pluck('id')->toArray();

        if (empty($categories)) {
            return;
        }

        $tickets = [
            ['ticket_id' => 12345001, 'title' => 'Login Issues with Two-Factor Authentication', 'description' => 'I am unable to log into my account. The two-factor authentication code is not working properly. I have tried multiple times but keep getting an error message.', 'status' => 'open', 'priority' => 'high', 'category_id' => $categories[0] ?? 1],
            ['ticket_id' => 12345002, 'title' => 'Payment Processing Error', 'description' => 'My payment was declined but the amount was charged to my card. Please help resolve this billing issue as soon as possible.', 'status' => 'in_progress', 'priority' => 'urgent', 'category_id' => $categories[0] ?? 1],
            ['ticket_id' => 12345003, 'title' => 'Feature Request: Dark Mode', 'description' => 'Would it be possible to add a dark mode theme to the application? This would greatly improve usability during night hours.', 'status' => 'open', 'priority' => 'low', 'category_id' => $categories[0] ?? 1],
            ['ticket_id' => 12345004, 'title' => 'Data Export Not Working', 'description' => 'The export functionality is not generating the CSV file. I need to export my data for reporting purposes.', 'status' => 'resolved', 'priority' => 'medium', 'category_id' => $categories[0] ?? 1, 'resolved_at' => now()->subDays(2)],
            ['ticket_id' => 12345005, 'title' => 'Account Suspension Appeal', 'description' => 'My account was suspended without clear reason. I believe this was done in error and would like to appeal this decision.', 'status' => 'closed', 'priority' => 'high', 'category_id' => $categories[0] ?? 1],
            ['ticket_id' => 12345006, 'title' => 'Password Reset Not Working', 'description' => 'I clicked on forgot password but never received the reset email. I have checked spam folder as well.', 'status' => 'open', 'priority' => 'medium', 'category_id' => $categories[0] ?? 1],
            ['ticket_id' => 12345007, 'title' => 'Mobile App Crashes on Startup', 'description' => 'The mobile application crashes immediately after opening. This started happening after the latest update.', 'status' => 'in_progress', 'priority' => 'high', 'category_id' => $categories[0] ?? 1],
            ['ticket_id' => 12345008, 'title' => 'Subscription Upgrade Request', 'description' => 'I would like to upgrade my current plan to the premium version. Please guide me through the process.', 'status' => 'resolved', 'priority' => 'low', 'category_id' => $categories[0] ?? 1, 'resolved_at' => now()->subDays(1)],
            ['ticket_id' => 12345009, 'title' => 'API Integration Issues', 'description' => 'Having trouble integrating with your API. Getting 401 unauthorized errors despite using correct credentials.', 'status' => 'open', 'priority' => 'high', 'category_id' => $categories[0] ?? 1],
            ['ticket_id' => 12345010, 'title' => 'File Upload Size Limit', 'description' => 'Cannot upload files larger than 5MB. Need to increase the upload limit for my business requirements.', 'status' => 'in_progress', 'priority' => 'medium', 'category_id' => $categories[0] ?? 1],
            ['ticket_id' => 12345011, 'title' => 'Email Notifications Not Received', 'description' => 'I am not receiving any email notifications from the system. Please check my notification settings.', 'status' => 'open', 'priority' => 'medium', 'category_id' => $categories[0] ?? 1],
            ['ticket_id' => 12345012, 'title' => 'Dashboard Loading Slowly', 'description' => 'The main dashboard takes more than 30 seconds to load. This is affecting my daily workflow significantly.', 'status' => 'resolved', 'priority' => 'medium', 'category_id' => $categories[0] ?? 1, 'resolved_at' => now()->subDays(3)],
            ['ticket_id' => 12345013, 'title' => 'Bulk Data Import Failed', 'description' => 'Attempted to import 1000 records via CSV but the process failed at 50%. No error message was displayed.', 'status' => 'open', 'priority' => 'urgent', 'category_id' => $categories[0] ?? 1],
            ['ticket_id' => 12345014, 'title' => 'User Permission Management', 'description' => 'Need help setting up role-based permissions for my team members. The current setup is confusing.', 'status' => 'closed', 'priority' => 'low', 'category_id' => $categories[0] ?? 1],
            ['ticket_id' => 12345015, 'title' => 'Security Vulnerability Report', 'description' => 'Found a potential security issue in the user profile section. Please contact me for detailed information.', 'status' => 'in_progress', 'priority' => 'urgent', 'category_id' => $categories[0] ?? 1],
        ];

        foreach ($tickets as $ticket) {
            HelpdeskTicket::firstOrCreate(
                ['ticket_id' => $ticket['ticket_id']],
                array_merge($ticket, [
                    'created_by' => $userId,
                    'created_at' => now()->subDays(rand(1, 30)),
                ])
            );
        }
    }
}