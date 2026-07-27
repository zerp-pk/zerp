<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * DatabaseTransactions, not RefreshDatabase: this runs against the developer's own
 * database and wiping it to test a form would be a poor trade. Same reasoning as
 * UserCreationTest, and the same reason these live outside ProfileTest.
 */
class ProfileAvatarTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $avatar): User
    {
        $user = new User();
        $user->name = 'avatar tester';
        $user->email = 'avatar' . uniqid() . '@x.test';
        $user->password = Hash::make('x');
        $user->type = 'company';
        $user->lang = 'en';
        $user->email_verified_at = now();
        $user->avatar = $avatar;
        $user->save();

        // ProfileUpdateRequest::authorize() gates on this; without it the request
        // is a 403 and never reaches the code under test.
        $user->givePermissionTo(Permission::firstOrCreate(
            ['name' => 'edit-profile', 'guard_name' => 'web'],
            ['add_on' => 'user', 'module' => 'user', 'label' => 'Edit Profile'],
        ));

        return $user;
    }

    /**
     * Clearing the picker submits an empty avatar, which ConvertEmptyStringsToNull
     * hands to the controller as null. `users.avatar` is NOT NULL, so saving that
     * null raised an integrity violation and the page 500'd instead of removing
     * the picture. Clearing has to fall back to the default placeholder.
     */
    public function test_avatar_can_be_removed_without_a_server_error(): void
    {
        $user = $this->user('uploaded-picture.png');

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => '',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame(User::DEFAULT_AVATAR, $user->avatar);
        $this->assertNull($user->avatar_media_id);
        $this->assertFalse($user->hasCustomAvatar());
    }

    /**
     * A cleared picture must also drop the media link, or avatar_media_id keeps
     * pointing at media that no longer represents the user.
     */
    public function test_removing_the_avatar_clears_the_media_link(): void
    {
        $user = $this->user('uploaded-picture.png');
        $user->forceFill(['avatar_media_id' => null])->save();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($user->refresh()->avatar_media_id);
    }

    /**
     * The avatar field is optional, and validated() only returns keys the request
     * actually sent. An update that leaves it out must not wipe the picture.
     */
    public function test_an_omitted_avatar_leaves_the_existing_one_alone(): void
    {
        $user = $this->user('uploaded-picture.png');

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Renamed User',
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('uploaded-picture.png', $user->refresh()->avatar);
    }

    public function test_the_default_placeholder_does_not_count_as_a_custom_avatar(): void
    {
        $this->assertFalse($this->user(User::DEFAULT_AVATAR)->hasCustomAvatar());
        $this->assertTrue($this->user('uploaded-picture.png')->hasCustomAvatar());
    }
}
