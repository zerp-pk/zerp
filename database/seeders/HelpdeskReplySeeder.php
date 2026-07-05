<?php

namespace Database\Seeders;

use App\Models\HelpdeskReply;
use App\Models\HelpdeskTicket;
use App\Models\User;
use Illuminate\Database\Seeder;

class HelpdeskReplySeeder extends Seeder
{
    public function run($userId): void
    {
        $tickets = HelpdeskTicket::where('created_by', $userId)->get();
        $superAdmin = User::where('type', 'superadmin')->first();

        if (!$superAdmin || $tickets->isEmpty()) {
            return;
        }

        $replyTemplates = [
            'user' => [
                'I have tried the suggested solution but the issue persists. Could you please provide additional assistance?',
                'Thank you for the quick response. The issue has been resolved successfully.',
                'I need more clarification on the steps provided. Could you please elaborate?',
                'The problem is still occurring. Here are the additional details you requested.',
                'I appreciate your help. Is there anything else I need to do?'
            ],
            'admin' => [
                'Thank you for contacting support. We are looking into your issue and will get back to you shortly.',
                'I have reviewed your case and here is the solution: Please try clearing your browser cache and cookies.',
                'Your issue has been escalated to our technical team. We will update you within 24 hours.',
                'The problem has been identified and fixed. Please try again and let us know if you need further assistance.',
                'We have processed your request. Your account has been updated accordingly.'
            ],
            'internal' => [
                'Customer reported similar issue last week. Check ticket #12340 for reference.',
                'This might be related to the recent system update. Need to investigate further.',
                'Escalating to Level 2 support team for advanced troubleshooting.',
                'Customer verification completed. Safe to proceed with account changes.',
                'Issue resolved. Adding to knowledge base for future reference.'
            ]
        ];

        foreach ($tickets as $ticket) {
            $replyCount = rand(4, 6);
            
            for ($i = 0; $i < $replyCount; $i++) {
                $isFromAdmin = $i % 2 === 1; // Alternate between user and admin
                $author = $isFromAdmin ? $superAdmin : $ticket->creator;
                $isInternal = $isFromAdmin && rand(1, 100) <= 20; // 20% chance for internal notes
                
                $messageType = $isInternal ? 'internal' : ($isFromAdmin ? 'admin' : 'user');
                $message = $replyTemplates[$messageType][array_rand($replyTemplates[$messageType])];

                HelpdeskReply::create([
                    'ticket_id' => $ticket->id,
                    'message' => $message,
                    'attachments' => null,
                    'is_internal' => $isInternal,
                    'created_by' => $author->id,
                    'created_at' => $ticket->created_at->addHours($i * 2 + rand(1, 4)),
                ]);
            }
        }
    }
}