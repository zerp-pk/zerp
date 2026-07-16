<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserCreationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * mobile_no is optional, so the form leaves it out entirely. validated() only
     * returns keys the request actually sent, so reading it raised "Undefined array
     * key" and the whole screen 500'd. Omitting it here is the point of the test.
     */
    public function test_a_company_can_create_a_staff_user_without_a_mobile_number(): void
    {
        $company = new User();
        $company->name = 'acme';
        $company->email = 'acme' . uniqid() . '@x.test';
        $company->password = Hash::make('x');
        $company->type = 'company';
        $company->lang = 'en';
        $company->email_verified_at = now();
        $company->total_user = -1; // unlimited; otherwise canCreateUser() blocks before the real path
        $company->save();

        $role = Role::firstOrCreate(
            ['name' => 'staff-' . uniqid(), 'guard_name' => 'web', 'created_by' => $company->id, 'label' => 'Staff'],
        );

        // The controller gates on can('create-users'); without it the request takes the
        // "Permission denied" branch, which redirects to the same place a success does.
        $perm = Permission::firstOrCreate(
            ['name' => 'create-users', 'guard_name' => 'web'],
            ['add_on' => 'user', 'module' => 'user', 'label' => 'Create Users'],
        );
        $company->givePermissionTo($perm);

        $this->actingAs($company);

        $email = 'staff' . uniqid() . '@x.test';

        $response = $this->post(route('users.store'), [
            'name' => 'New Staff',
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'type' => $role->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('users.index'));

        // Without this the "Permission denied" branch also redirects to users.index,
        // so the assertions above pass while nothing was created.
        $this->assertDatabaseHas('users', ['email' => $email, 'mobile_no' => null]);
    }
}
