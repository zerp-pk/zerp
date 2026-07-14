<?php

namespace Tests\Feature;

use App\Models\AddOn;
use App\Models\DisabledModule;
use App\Models\MenuPreference;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * DatabaseTransactions, not RefreshDatabase: this suite runs against the developer's
 * own database, and wiping it to test a preference screen would be a poor trade.
 */
class ModulePreferenceTest extends TestCase
{
    use DatabaseTransactions;

    private function registerModule(string $module): void
    {
        // ActivatedModule() intersects with Module::allEnabled(), which reads add_ons.
        // Register explicitly so the test does not depend on what is installed locally.
        AddOn::firstOrCreate(
            ['module' => $module],
            ['name' => $module, 'package_name' => 'zerp/' . strtolower($module), 'monthly_price' => 0, 'yearly_price' => 0, 'is_enable' => 1, 'for_admin' => 0, 'priority' => 1],
        );
    }

    private function company(array $planModules = [], array $ownedAddons = []): User
    {
        foreach (array_unique([...$planModules, ...$ownedAddons]) as $module) {
            $this->registerModule($module);
        }

        $plan = Plan::create([
            'name' => 'Test ' . uniqid(),
            'price' => 0,
            'duration' => 'Monthly',
            'max_users' => 5,
            'modules' => $planModules,
        ]);

        $user = new User();
        $user->name = 'acme';
        $user->email = 'acme' . uniqid() . '@x.test';
        $user->password = Hash::make('x');
        $user->type = 'company';
        $user->lang = 'en';
        $user->active_plan = $plan->id;
        $user->email_verified_at = now();
        $user->save();

        foreach ($ownedAddons as $module) {
            UserActiveModule::create(['user_id' => $user->id, 'module' => $module]);
        }

        return $user;
    }

    public function test_a_disabled_module_drops_out_of_the_active_set(): void
    {
        $company = $this->company(['Lead'], ['Lead']);
        $this->actingAs($company);

        $this->assertContains('Lead', ActivatedModule());

        DisabledModule::create(['user_id' => $company->id, 'module' => 'Lead']);

        // ActivatedModule is the single source for both the sidebar and PlanModuleCheck,
        // so dropping out here means the menu hides it AND its routes stop resolving.
        $this->assertNotContains('Lead', ActivatedModule());
    }

    public function test_disabling_a_module_does_not_destroy_a_paid_entitlement(): void
    {
        // user_active_modules doubles as the record of which add-ons a company bought.
        // If switching a module off deleted that row, switching it back on would mean
        // buying it again — so this is the test that matters most on this screen.
        $company = $this->company([], ['Lead']);
        $this->actingAs($company);

        $this->put(route('settings.modules.update'), ['module' => 'Lead', 'enabled' => false]);

        $this->assertDatabaseHas('user_active_modules', [
            'user_id' => $company->id,
            'module' => 'Lead',
        ]);

        // ...and it comes straight back.
        $this->put(route('settings.modules.update'), ['module' => 'Lead', 'enabled' => true]);

        $this->assertDatabaseMissing('disabled_modules', [
            'user_id' => $company->id,
            'module' => 'Lead',
        ]);
    }

    public function test_a_company_cannot_enable_a_module_its_plan_does_not_include(): void
    {
        // Otherwise the toggle is a way to unlock paid modules for free.
        $company = $this->company(['Lead']);
        $this->actingAs($company);

        $response = $this->put(route('settings.modules.update'), [
            'module' => 'Hrm',
            'enabled' => true,
        ]);

        $response->assertSessionHasErrors('module');
        $this->assertNotContains('Hrm', ActivatedModule());
    }

    public function test_staff_may_not_change_the_companys_modules(): void
    {
        $company = $this->company(['Lead']);

        $staff = new User();
        $staff->name = 'staff';
        $staff->email = 'staff' . uniqid() . '@x.test';
        $staff->password = Hash::make('x');
        $staff->type = 'staff';
        $staff->lang = 'en';
        $staff->created_by = $company->id;
        $staff->email_verified_at = now();
        $staff->save();

        $this->actingAs($staff)
            ->put(route('settings.modules.update'), ['module' => 'Lead', 'enabled' => false])
            ->assertForbidden();
    }

    public function test_a_personal_menu_layout_overrides_the_company_default(): void
    {
        $company = $this->company(['Lead']);

        $staff = new User();
        $staff->name = 'staff';
        $staff->email = 'staff' . uniqid() . '@x.test';
        $staff->password = Hash::make('x');
        $staff->type = 'staff';
        $staff->lang = 'en';
        $staff->created_by = $company->id;
        $staff->email_verified_at = now();
        $staff->save();

        MenuPreference::create([
            'user_id' => $company->id,
            'scope' => 'company',
            'order' => ['manage-hrm', 'manage-leads'],
            'hidden_items' => [],
        ]);

        $this->actingAs($staff);

        // With no personal layout, staff inherit the company's.
        $inherited = MenuPreference::resolveFor($staff);
        $this->assertSame(['manage-hrm', 'manage-leads'], $inherited['order']);
        $this->assertSame('company', $inherited['source']);

        MenuPreference::create([
            'user_id' => $staff->id,
            'scope' => 'user',
            'order' => ['manage-leads', 'manage-hrm'],
            'hidden_items' => ['manage-hrm'],
        ]);

        $own = MenuPreference::resolveFor($staff->fresh());
        $this->assertSame(['manage-leads', 'manage-hrm'], $own['order']);
        $this->assertSame(['manage-hrm'], $own['hidden']);
        $this->assertSame('user', $own['source']);
    }
}
