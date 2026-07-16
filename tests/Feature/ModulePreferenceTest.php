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
use Spatie\Permission\Models\Permission;
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

        // user_active_modules records what a company picked and paid for. It is not the
        // entitlement: the plan is. Tests pass rows here to prove they cannot widen it.
        foreach ($ownedAddons as $module) {
            UserActiveModule::create(['user_id' => $user->id, 'module' => $module]);
        }

        // Modules is a section of Settings now, so reaching it needs manage-settings,
        // which a real company owner holds.
        $user->givePermissionTo(Permission::firstOrCreate(
            ['name' => 'manage-settings', 'guard_name' => 'web'],
            ['add_on' => 'user', 'module' => 'user', 'label' => 'Manage Settings'],
        ));

        return $user->fresh();
    }

    public function test_the_settings_screen_loads_with_the_modules_section(): void
    {
        // Nothing here ever rendered the screen (every other test is a PUT), and the
        // catalogue is a list of Module objects which it read as arrays and 500'd on.
        // A plain GET is the whole regression test. Modules is a section of Settings
        // now, so this renders the page that carries it.
        $company = $this->company(['Lead'], ['Lead']);

        $response = $this->actingAs($company)->get(route('settings.index'));

        $response->assertOk();
        $this->assertNotEmpty($response->viewData('page')['props']['modules']);
    }

    public function test_the_old_modules_url_redirects_to_the_section(): void
    {
        // The screen moved into Settings; old links and bookmarks still work.
        $company = $this->company(['Lead'], ['Lead']);

        $this->actingAs($company)
            ->get(route('settings.modules'))
            ->assertRedirect(route('settings.index') . '#modules-settings');
    }

    public function test_the_old_menu_url_redirects_to_the_section(): void
    {
        $company = $this->company(['Lead'], ['Lead']);

        $this->actingAs($company)
            ->get(route('settings.menu'))
            ->assertRedirect(route('settings.index') . '#menu-settings');
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
        // user_active_modules is the record of what a company picked and paid for.
        // If switching a module off deleted that row, switching it back on would mean
        // buying it again - so this is the test that matters most on this screen.
        // Lead has to be in the plan: entitlement is plan-bounded, so without it the
        // update is rejected and this test would pass while doing nothing.
        $company = $this->company(['Lead'], ['Lead']);
        $this->actingAs($company);

        $this->put(route('settings.modules.update'), ['module' => 'Lead', 'enabled' => false])
            ->assertSessionHasNoErrors();

        // Proves the disable actually happened rather than being rejected.
        $this->assertDatabaseHas('disabled_modules', [
            'user_id' => $company->id,
            'module' => 'Lead',
        ]);

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

    /**
     * PackageSeeder writes a user_active_modules row per installed module, and those
     * rows used to be merged into the entitlement, so a company saw every installed
     * module regardless of its plan. The plan is the boundary.
     */
    public function test_a_module_outside_the_plan_is_not_shown_even_with_an_active_module_row(): void
    {
        // Plan has Lead only, but the company carries a row for Hrm as well.
        $company = $this->company(['Lead'], ['Lead', 'Hrm']);
        $this->actingAs($company);

        $this->assertContains('Lead', ActivatedModule());
        $this->assertNotContains('Hrm', ActivatedModule());

        $modules = collect($this->get(route('settings.index'))
            ->viewData('page')['props']['modules'])->pluck('module');

        $this->assertTrue($modules->contains('Lead'));
        $this->assertFalse($modules->contains('Hrm'), 'a module outside the plan must not reach the Modules screen');
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
