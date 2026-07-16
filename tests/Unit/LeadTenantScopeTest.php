<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Zerp\Lead\Models\Deal;
use Zerp\Lead\Models\Lead;
use Zerp\Lead\Models\LeadCall;
use Zerp\Lead\Models\Pipeline;

/**
 * The Lead module's controllers authorise mutations with a capability check that
 * every tenant's staff passes, so the tenant boundary lives in a global scope on
 * the models instead. These compile the query rather than hitting a database:
 * what matters is that the boundary reaches the SQL, on every model, always.
 */
class LeadTenantScopeTest extends TestCase
{
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

        foreach ([Lead::class, Deal::class, Pipeline::class] as $model) {
            $query = $model::query();

            $this->assertStringContainsString('created_by', $query->toSql(), $model);
            $this->assertContains(7, $query->getBindings(), $model);
        }
    }

    public function test_child_rows_inherit_the_boundary_through_their_parent(): void
    {
        $this->actAsCompany(7);

        // lead_calls has no created_by column, so the scope reaches the tenant
        // through the parent lead - the case that made the calls endpoints the
        // hard ones to fix.
        $sql = LeadCall::query()->toSql();

        $this->assertStringContainsString('exists', strtolower($sql));
        $this->assertStringContainsString('leads', $sql);
        $this->assertStringContainsString('created_by', $sql);
        $this->assertContains(7, LeadCall::query()->getBindings());
    }

    public function test_one_tenant_cannot_reach_anothers_rows(): void
    {
        $this->actAsCompany(7);
        $this->assertContains(7, Lead::query()->getBindings());

        $this->actAsCompany(99);
        $bindings = Lead::query()->getBindings();

        $this->assertContains(99, $bindings);
        $this->assertNotContains(7, $bindings);
    }

    public function test_scope_stands_down_without_an_authenticated_user(): void
    {
        // Console commands, seeders and queued jobs have no tenant to scope to.
        Auth::forgetUser();

        $this->assertStringNotContainsString('created_by', Lead::query()->toSql());
    }

    public function test_company_provisioning_can_opt_out(): void
    {
        // LeadUtility::defaultdata() sets up a *different* company's pipelines.
        $this->actAsCompany(7);

        $sql = Pipeline::withoutGlobalScope('tenant')->toSql();

        $this->assertStringNotContainsString('created_by', $sql);
    }
}
