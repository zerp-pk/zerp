<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The mobile auth API in routes/api.php. Tests use real Sanctum tokens rather
 * than actingAs(): logout and refresh both call currentAccessToken()->delete(),
 * which only exists on a real personal access token, not the transient one
 * actingAs() sets. Hitting /api/login for the token also exercises the flow the
 * app actually uses.
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /** A password that satisfies the change-password policy (min 8, mixed case, numbers). */
    private const STRONG = 'OldPass123';

    private function login(string $email, string $password): string
    {
        return $this->postJson('/api/login', compact('email', 'password'))->json('data.token');
    }

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'bearer')
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The provided credentials are incorrect.');
    }

    public function test_login_is_rejected_for_an_unknown_email(): void
    {
        $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ])->assertStatus(400)->assertJsonPath('success', false);
    }

    public function test_login_validation_fails_without_credentials(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_replaces_any_existing_tokens(): void
    {
        $user = User::factory()->create();

        $first = $this->login($user->email, 'password');
        $second = $this->login($user->email, 'password');

        $this->assertNotSame($first, $second);
        // login() deletes the user's tokens before issuing a new one, so the
        // first token no longer authenticates.
        $this->assertSame(1, $user->tokens()->count());
        $this->withToken($first)->postJson('/api/refresh')->assertStatus(401);
    }

    /** id from a Sanctum "id|plaintext" token string. */
    private function tokenId(string $token): int
    {
        return (int) explode('|', $token)[0];
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $this->login($user->email, 'password');

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        // The row is gone: that is what revocation means. A second request with
        // the same token cannot be used to prove it here, because the auth guard
        // caches the user it already resolved for this token earlier in the test.
        $this->assertSame(0, $user->fresh()->tokens()->count());
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $this->tokenId($token)]);
    }

    public function test_refresh_issues_a_new_token_and_revokes_the_old(): void
    {
        $user = User::factory()->create();
        $old = $this->login($user->email, 'password');

        $new = $this->withToken($old)->postJson('/api/refresh')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('data.token');

        $this->assertNotSame($old, $new);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $this->tokenId($old)]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $this->tokenId($new)]);
        $this->withToken($new)->getJson('/api/user')->assertOk();
    }

    public function test_change_password_updates_the_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::STRONG)]);
        $token = $this->login($user->email, self::STRONG);

        $this->withToken($token)->postJson('/api/change-password', [
            'current_password' => self::STRONG,
            'password' => 'NewPass456',
            'password_confirmation' => 'NewPass456',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('NewPass456', $user->fresh()->password));
    }

    public function test_change_password_rejects_a_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::STRONG)]);
        $token = $this->login($user->email, self::STRONG);

        $this->withToken($token)->postJson('/api/change-password', [
            'current_password' => 'not-the-password',
            'password' => 'NewPass456',
            'password_confirmation' => 'NewPass456',
        ])->assertStatus(400)->assertJsonPath('success', false);

        $this->assertTrue(Hash::check(self::STRONG, $user->fresh()->password));
    }

    public function test_change_password_rejects_reusing_the_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::STRONG)]);
        $token = $this->login($user->email, self::STRONG);

        $this->withToken($token)->postJson('/api/change-password', [
            'current_password' => self::STRONG,
            'password' => self::STRONG,
            'password_confirmation' => self::STRONG,
        ])->assertStatus(400)->assertJsonPath('success', false);
    }

    public function test_change_password_requires_a_confirmation(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::STRONG)]);
        $token = $this->login($user->email, self::STRONG);

        $this->withToken($token)->postJson('/api/change-password', [
            'current_password' => self::STRONG,
            'password' => 'NewPass456',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_protected_routes_reject_unauthenticated_requests(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
        $this->postJson('/api/logout')->assertStatus(401);
        $this->postJson('/api/refresh')->assertStatus(401);
        $this->postJson('/api/change-password')->assertStatus(401);
        $this->deleteJson('/api/delete-account')->assertStatus(401);
    }
}
