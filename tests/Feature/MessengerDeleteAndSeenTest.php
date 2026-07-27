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
 * Follow up to zerp-pk/zerp#90, reported still broken after #92.
 *
 * Two remaining gaps, both on the recipient's side:
 *
 *  - A message that arrives over Pusher into an already open conversation was
 *    never marked seen. Only loading the page or paging the conversation flips
 *    `seen`, so with both chats open the sender's tick never turned blue.
 *  - The conversation query reads `(A) or (B) and (C)`, and SQL binds `and`
 *    tighter than `or`, so the delete filter only ever applied to received
 *    messages. A sender deleting their own message saw it come back on reload.
 *
 * DatabaseTransactions, not RefreshDatabase: this suite runs against a real
 * database and must not wipe it.
 */
class MessengerDeleteAndSeenTest extends TestCase
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

    private function message(User $from, User $to, string $body = 'hello'): ChMessage
    {
        return ChMessage::create([
            'from_id' => $from->id,
            'to_id' => $to->id,
            'body' => $body,
            'seen' => 0,
        ]);
    }

    public function test_a_message_the_sender_deleted_stays_hidden_from_the_sender(): void
    {
        $sender = $this->user(['delete-messages']);
        $recipient = $this->user();
        $message = $this->message($sender, $recipient, 'delete me');

        $this->actingAs($sender)
            ->deleteJson(route('messenger.delete-message', $message->id))
            ->assertOk();

        // The row survives, because the recipient still has it.
        $this->assertSame(1, (int) ChMessage::find($message->id)->deleted_by_sender);

        $ids = collect($this->actingAs($sender)
            ->getJson(route('messenger.messages', $recipient->id))
            ->assertOk()
            ->json('data'))->pluck('id');

        $this->assertFalse($ids->contains($message->id), 'A message the sender deleted came back on reload.');
    }

    public function test_the_recipient_still_sees_a_message_the_sender_deleted(): void
    {
        $sender = $this->user(['delete-messages']);
        $recipient = $this->user();
        $message = $this->message($sender, $recipient, 'one sided delete');

        $this->actingAs($sender)->deleteJson(route('messenger.delete-message', $message->id))->assertOk();

        $ids = collect($this->actingAs($recipient)
            ->getJson(route('messenger.messages', $sender->id))
            ->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($message->id), 'Deleting for yourself must not delete for the other side.');
    }

    public function test_a_message_the_recipient_deleted_stays_hidden_from_the_recipient(): void
    {
        $sender = $this->user();
        $recipient = $this->user(['delete-messages']);
        $message = $this->message($sender, $recipient, 'delete me too');

        $this->actingAs($recipient)->deleteJson(route('messenger.delete-message', $message->id))->assertOk();

        $ids = collect($this->actingAs($recipient)
            ->getJson(route('messenger.messages', $sender->id))
            ->json('data'))->pluck('id');

        $this->assertFalse($ids->contains($message->id));
    }

    public function test_the_conversation_never_leaks_a_third_party_message(): void
    {
        $sender = $this->user();
        $recipient = $this->user();
        $stranger = $this->user();
        $other = $this->message($stranger, $recipient, 'not yours');

        $ids = collect($this->actingAs($sender)
            ->getJson(route('messenger.messages', $recipient->id))
            ->json('data'))->pluck('id');

        $this->assertFalse($ids->contains($other->id));
    }

    public function test_polling_marks_messages_seen_for_the_open_conversation(): void
    {
        Event::fake();
        $sender = $this->user(['send-messages']);
        $recipient = $this->user();
        $message = $this->message($sender, $recipient, 'live message');

        // The recipient has the conversation open, so they never refetch it. Their
        // poll is the only thing that can report the message as read.
        $this->actingAs($recipient)
            ->getJson(route('messenger.check-new-messages', ['user_id' => $sender->id]))
            ->assertOk();

        $this->assertSame(1, (int) ChMessage::find($message->id)->seen);

        $this->actingAs($sender)
            ->getJson(route('messenger.check-new-messages', ['user_id' => $recipient->id]))
            ->assertJsonPath('seen_message_ids', [$message->id]);
    }

    public function test_polling_without_an_open_conversation_marks_nothing_seen(): void
    {
        $sender = $this->user();
        $recipient = $this->user();
        $message = $this->message($sender, $recipient, 'unread');

        $this->actingAs($recipient)
            ->getJson(route('messenger.check-new-messages'))
            ->assertOk();

        $this->assertSame(0, (int) ChMessage::find($message->id)->seen);
    }

    public function test_polling_only_marks_the_open_conversation_seen(): void
    {
        $sender = $this->user();
        $stranger = $this->user();
        $recipient = $this->user();
        $open = $this->message($sender, $recipient, 'open chat');
        $closed = $this->message($stranger, $recipient, 'other chat');

        $this->actingAs($recipient)
            ->getJson(route('messenger.check-new-messages', ['user_id' => $sender->id]))
            ->assertOk();

        $this->assertSame(1, (int) ChMessage::find($open->id)->seen);
        $this->assertSame(0, (int) ChMessage::find($closed->id)->seen);
    }
}
