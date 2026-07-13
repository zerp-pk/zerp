<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Module controllers authorise mutations with a capability check that every
 * tenant's staff passes, so the tenant boundary lives in App\Models\Concerns\
 * TenantScoped on the models instead.
 *
 * These compile the query rather than hitting a database: what matters is that
 * the boundary reaches the SQL, on every model, always. Add a module's models
 * to $ownedModels as the scope is rolled out to it.
 */
class TenantScopeTest extends TestCase
{
    /** Models that own their rows via a created_by column. */
    private array $ownedModels = [
        \Zerp\Hrm\Models\Employee::class,
        \Zerp\Hrm\Models\Branch::class,
        \Zerp\Hrm\Models\Department::class,
        \Zerp\Hrm\Models\Payroll::class,
        \Zerp\Hrm\Models\LeaveApplication::class,
    ];

    private function actAsCompany(int $id): void
    {
        $user = new User(['type' => 'company']);
        $user->id = $id;
        $user->type = 'company';
        Auth::setUser($user);
    }

    protected function tearDown(): void
    {
        Auth::forgetUser();
        parent::tearDown();
    }

    public function test_owned_models_are_confined_to_the_tenant(): void
    {
        $this->actAsCompany(7);

        foreach ($this->ownedModels as $model) {
            /** @var Model $model */
            $query = $model::query();

            $this->assertStringContainsString('created_by', $query->toSql(), $model);
            $this->assertContains(7, $query->getBindings(), $model);
        }
    }

    public function test_one_tenant_cannot_reach_anothers_rows(): void
    {
        $this->actAsCompany(7);
        $this->assertContains(7, \Zerp\Hrm\Models\Employee::query()->getBindings());

        $this->actAsCompany(99);
        $bindings = \Zerp\Hrm\Models\Employee::query()->getBindings();

        $this->assertContains(99, $bindings);
        $this->assertNotContains(7, $bindings);
    }

    public function test_scope_stands_down_without_an_authenticated_user(): void
    {
        // Console commands, seeders and queued jobs have no tenant to scope to.
        Auth::forgetUser();

        foreach ($this->ownedModels as $model) {
            $this->assertStringNotContainsString('created_by', $model::query()->toSql(), $model);
        }
    }

    public function test_provisioning_can_opt_out(): void
    {
        $this->actAsCompany(7);

        $sql = \Zerp\Hrm\Models\Branch::withoutGlobalScope('tenant')->toSql();

        $this->assertStringNotContainsString('created_by', $sql);
    }
}
