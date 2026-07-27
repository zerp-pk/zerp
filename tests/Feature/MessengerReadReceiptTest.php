<?php

namespace Tests\Feature;

use App\Models\ChMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * zerp-pk/zerp#90: a sent message stayed on a single tick forever. The recipient
 * marks it seen when they open the conversation, but the sender was never told,
 * and the sender's own message kept a temporary client side id with no way to
 * match it back to the stored row.
 *
 * DatabaseTransactions, not RefreshDatabase: this suite runs against a real
 * database and must not wipe it.
 */
class MessengerReadReceiptTest extends TestCase
{
    use DatabaseTransactions;

    private function user(array $permissions = []): User
    {
        $user = new User();
        $user->name = 'chatter';
        $user->email = 'chat' . uniqid() . '@x.test';
        $user->password = Hash::make('x');
        $user->type = 'company';
        $user->lang = 'en';
        $user->email_verified_at = now();
        $user->save();

        foreach ($permissions as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['add_on' => 'user', 'module' => 'messenger', 'label' => $name],
            ));
        }

        return $user->fresh();
    }

    public function test_sending_a_message_returns_the_stored_id(): void
    {
        Event::fake();
        $sender = $this->user(['send-messages']);
        $recipient = $this->user();

        $response = $this->actingAs($sender)->postJson(route('messenger.send'), [
            'receiver_id' => $recipient->id,
            'message' => 'hello',
        ]);

        $response->assertOk();
        $this->assertSame(
            ChMessage::where('from_id', $sender->id)->where('to_id', $recipient->id)->latest('id')->first()->id,
            $response->json('data.id'),
        );
    }

    public function test_the_sender_is_told_once_the_recipient_reads(): void
    {
        Event::fake();
        $sender = $this->user(['send-messages']);
        $recipient = $this->user();

        $this->actingAs($sender)->postJson(route('messenger.send'), [
            'receiver_id' => $recipient->id,
            'message' => 'hello',
        ]);
        $messageId = ChMessage::where('from_id', $sender->id)->where('to_id', $recipient->id)->latest('id')->first()->id;

        // Nobody has read it yet, so the sender stays on a single tick.
        $this->actingAs($sender)
            ->getJson(route('messenger.check-new-messages', ['user_id' => $recipient->id]))
            ->assertJsonPath('seen_message_ids', []);

        // The recipient opening the conversation is what marks it seen.
        $this->actingAs($recipient)->getJson(route('messenger.messages', $sender->id))->assertOk();

        $this->actingAs($sender)
            ->getJson(route('messenger.check-new-messages', ['user_id' => $recipient->id]))
            ->assertJsonPath('seen_message_ids', [$messageId]);
    }

    public function test_read_status_of_other_conversations_does_not_leak(): void
    {
        Event::fake();
        $sender = $this->user(['send-messages']);
        $recipient = $this->user();
        $stranger = $this->user();

        $this->actingAs($sender)->postJson(route('messenger.send'), [
            'receiver_id' => $recipient->id,
            'message' => 'hello',
        ]);
        $this->actingAs($recipient)->getJson(route('messenger.messages', $sender->id));

        $this->actingAs($sender)
            ->getJson(route('messenger.check-new-messages', ['user_id' => $stranger->id]))
            ->assertJsonPath('seen_message_ids', []);
    }
}
