<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The password policy is registered once via Password::defaults() in
 * AppServiceProvider and referenced by every request that sets a password.
 * These lock the policy in place: min 8, mixed case, and a number. The
 * uncompromised() breach check only runs in production, so it is not asserted
 * here (APP_ENV is testing, which keeps the suite offline).
 */
class PasswordPolicyTest extends TestCase
{
    private function passes(string $password): bool
    {
        return Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::defaults()]],
        )->passes();
    }

    #[DataProvider('weakPasswords')]
    public function test_weak_passwords_are_rejected(string $password): void
    {
        $this->assertFalse($this->passes($password), "expected '$password' to be rejected");
    }

    public static function weakPasswords(): array
    {
        return [
            'old six-char minimum' => ['abc123'],
            'no number or upper'   => ['password'],
            'no number'            => ['Password'],
            'no uppercase'         => ['password1'],
            'too short'            => ['Pass1'],
        ];
    }

    public function test_a_strong_password_is_accepted(): void
    {
        $this->assertTrue($this->passes('Password1'));
    }
}
