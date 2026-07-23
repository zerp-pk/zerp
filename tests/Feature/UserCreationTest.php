<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * DatabaseTransactions, not RefreshDatabase: this runs against the developer's own
 * database and wiping it to test a form would be a poor trade.
 */
class UserCreationTest extends TestCase
{
    use DatabaseTransactions;

    private function company(): User
    {
        $company = new User();
        $company->name = 'acme';
        $company->email = 'acme' . uniqid() . '@x.test';
        $company->password = Hash::make('x');
        $company->type = 'company';
        $company->lang = 'en';
        $company->email_verified_at = now();
        $company->total_user = -1; // unlimited, else canCreateUser() blocks before the real path
        $company->save();

        // The controller gates on can('create-users'). Without it the request takes the
        // "Permission denied" branch, which redirects exactly where a success does.
        $company->givePermissionTo(Permission::firstOrCreate(
            ['name' => 'create-users', 'guard_name' => 'web'],
            ['add_on' => 'user', 'module' => 'user', 'label' => 'Create Users'],
        ));

        return $company;
    }

    private function staffRole(User $company): Role
    {
        return Role::firstOrCreate(
            ['name' => 'staff-' . uniqid(), 'guard_name' => 'web', 'created_by' => $company->id, 'label' => 'Staff'],
        );
    }

    /**
     * mobile_no is optional, so the form leaves it out entirely. validated() only
     * returns keys the request actually sent, so reading it raised "Undefined array
     * key" and the screen 500'd. Omitting it here is the point of the test.
     */
    public function test_a_company_can_create_a_staff_user_without_a_mobile_number(): void
    {
        $company = $this->company();
        $role = $this->staffRole($company);
        $this->actingAs($company);

        $email = 'staff' . uniqid() . '@x.test';

        $response = $this->post(route('users.store'), [
            'name' => 'New Staff',
            'email' => $email,
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'type' => $role->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('users.index'));

        // Asserting the row exists is what makes this test real: the "Permission denied"
        // and plan-limit branches both redirect to users.index just like a success.
        $this->assertDatabaseHas('users', ['email' => $email, 'mobile_no' => null]);
    }

    public function test_a_malformed_email_is_rejected_and_no_user_is_created(): void
    {
        $company = $this->company();
        $role = $this->staffRole($company);
        $this->actingAs($company);

        $response = $this->post(route('users.store'), [
            'name' => 'Bad Address',
            'email' => 'not-an-email',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'type' => $role->id,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['name' => 'Bad Address']);
    }

    /**
     * The dns check resolves the domain over the network, so it is production-only
     * and this asserts the switch rather than the lookup. Flipping the condition
     * would either let fake domains through in production or break every offline
     * test run, and neither shows up in the tests above. See zerp-pk/zerp#78.
     */
    public function test_the_email_rule_only_checks_dns_in_production(): void
    {
        $this->assertSame('email', emailRule());

        $this->app['env'] = 'production';

        $this->assertSame('email:rfc,dns', emailRule());
    }

    /**
     * A company with no SMTP configured got a 500 on a user that had in fact been
     * created: SetConfigEmail() throws when email_host is unset, and the send sits
     * after $user->save(). The user should still be created and the admin told.
     */
    public function test_creating_a_user_survives_an_unconfigured_mail_server(): void
    {
        $superadminId = User::where('type', 'superadmin')->value('id');
        $this->assertNotNull($superadminId, 'needs a superadmin; run app:install');

        Setting::updateOrCreate(
            ['key' => 'enableEmailVerification', 'created_by' => $superadminId],
            ['value' => 'on', 'is_public' => 0],
        );
        Cache::flush();

        $company = $this->company();
        $role = $this->staffRole($company);
        $this->actingAs($company);

        $email = 'staff' . uniqid() . '@x.test';

        $response = $this->post(route('users.store'), [
            'name' => 'No Mail Server',
            'email' => $email,
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'type' => $role->id,
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('warning');
        $this->assertDatabaseHas('users', ['email' => $email]);
    }
}
