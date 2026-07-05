<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ChMessage;
use App\Models\ChFavorite;
use App\Models\ChPinned;
use Carbon\Carbon;

class MessengerSeeder extends Seeder
{
    public function run()
    {
        // Get company user and created users
        $companyUser = User::where('type', 'company')->first();
        if (!$companyUser) return;

        $createdUsers = User::whereIn('type', ['staff', 'client', 'vendor'])
            ->where('created_by', $companyUser->id)
            ->limit(10)
            ->get();

        if ($createdUsers->count() < 2) return;

        $conversations = [
            [
                "Hi there! How's your day going?",
                "Hey! Pretty good, thanks for asking. How about you?",
                "Not bad at all. Just working on the quarterly report.",
                "Oh nice! Need any help with that?",
                "Actually, yes. Could you review the financial section?",
                "Of course! Send it over and I'll take a look.",
                "Perfect, sending it now. Thanks!",
                "No problem, happy to help 😊"
            ],
            [
                "Good morning! Ready for today's meeting?",
                "Morning! Yes, I've prepared all the materials.",
                "Great! What time should we start?",
                "How about 10 AM? That works for everyone.",
                "Perfect. I'll send the calendar invite.",
                "Sounds good. See you then!"
            ],
            [
                "The client loved the presentation!",
                "Really? That's fantastic news!",
                "Yes, they want to move forward with the project.",
                "Excellent work! This calls for a celebration 🎉",
                "Definitely! Drinks after work?",
                "Count me in!"
            ],
            [
                "Quick question about the budget",
                "Sure, what's up?",
                "Do we have approval for the additional resources?",
                "Let me check with management and get back to you.",
                "Thanks, no rush but would be good to know soon.",
                "I'll follow up by end of day.",
                "Perfect, appreciate it!"
            ],
            [
                "Hey, are you free for a quick call?",
                "Sure! What's it about?",
                "Need to discuss the timeline for the new feature.",
                "Ah yes, I was thinking about that too.",
                "Great minds think alike! 😄",
                "Haha exactly! Give me 5 minutes?",
                "Perfect, I'll call you then."
            ]
        ];

        // Create realistic conversations between company and created users
        foreach ($createdUsers as $index => $user) {
            $conversation = $conversations[$index % count($conversations)];
            $baseTime = Carbon::now()->subDays(rand(0, 7));
            
            foreach ($conversation as $msgIndex => $messageText) {
                $isFromCompany = $msgIndex % 2 === 0;
                $fromId = $isFromCompany ? $companyUser->id : $user->id;
                $toId = $isFromCompany ? $user->id : $companyUser->id;
                
                ChMessage::create([
                    'from_id' => $fromId,
                    'to_id' => $toId,
                    'body' => $messageText,
                    'seen' => $msgIndex < count($conversation) - 2 ? 1 : rand(0, 1),
                    'created_at' => $baseTime->copy()->addMinutes($msgIndex * rand(2, 15)),
                ]);
            }
        }

        // Create conversations between created users
        $quickChats = [
            ["Hey, got a minute?", "Sure! What's up?", "Can you help me with this task?", "Of course! What do you need?"],
            ["Thanks for yesterday's help!", "No problem at all!", "Really saved me a lot of time.", "Glad I could help! 😊"],
            ["Are you joining the team lunch?", "Definitely! What time?", "12:30 at the usual place", "Perfect, see you there!"],
        ];
        
        for ($i = 0; $i < min(3, $createdUsers->count() - 1); $i++) {
            $user1 = $createdUsers->skip($i)->first();
            $user2 = $createdUsers->skip($i + 1)->first();
            $chat = $quickChats[$i % count($quickChats)];
            $baseTime = Carbon::now()->subDays(rand(1, 5));
            
            foreach ($chat as $msgIndex => $messageText) {
                $isFromUser1 = $msgIndex % 2 === 0;
                $fromId = $isFromUser1 ? $user1->id : $user2->id;
                $toId = $isFromUser1 ? $user2->id : $user1->id;
                
                ChMessage::create([
                    'from_id' => $fromId,
                    'to_id' => $toId,
                    'body' => $messageText,
                    'seen' => $msgIndex < count($chat) - 1 ? 1 : rand(0, 1),
                    'created_at' => $baseTime->copy()->addMinutes($msgIndex * rand(3, 10)),
                ]);
            }
        }
        // Add favorites and pinned contacts for some users
        foreach ($createdUsers->take(3) as $user) {
            // Favorites
            $userFavorites = $createdUsers->where('id', '!=', $user->id)->random(rand(1, 3));
            foreach ($userFavorites as $favUser) {
                ChFavorite::firstOrCreate(
                    [
                        'user_id'   => $user->id,
                        'favorite_id'=> $favUser->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            // Pins
            $userPinned = $createdUsers->where('id', '!=', $user->id)->random(rand(1, 2));
            foreach ($userPinned as $pinnedUser) {
                ChPinned::firstOrCreate(
                    [
                        'user_id'   => $user->id,
                        'pinned_id' => $pinnedUser->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}