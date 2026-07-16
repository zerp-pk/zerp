<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Confines every query on the model to the authenticated user's tenant.
 *
 * Module controllers across this codebase authorise mutations with a capability
 * check alone (`can('edit-employee')`), which every tenant's staff passes - so an
 * id belonging to another company resolved fine and was then read, edited or
 * deleted. Guarding each action individually is how the next new action ends up
 * unguarded, so the boundary lives on the model instead: a foreign id resolves to
 * null and route-model binding 404s before a controller runs.
 *
 * Rows that own themselves carry `created_by` (the tenant id, per creatorId()).
 * Child rows that have no such column declare the relation that does:
 *
 *     public string $tenantParent = 'employee';
 *
 * and inherit the boundary through it.
 *
 * With no authenticated user (console commands, seeders, queued jobs) there is no
 * tenant to scope to and the scope stands down; those paths are not attacker-
 * reachable. Code that legitimately acts on another tenant - provisioning a new
 * company's default records - must opt out explicitly:
 *
 *     Model::withoutGlobalScope('tenant')
 *
 * Note that `exists:` validation rules go through the query builder, not Eloquent,
 * and so are NOT covered by this scope. They must be scoped by hand:
 *
 *     'employee_id' => 'required|exists:employees,id,created_by,' . creatorId()
 *
 * Public per-company portals (the support-ticket help centre, the recruitment job
 * board) serve one company's data to the world, addressed by a slug in the URL,
 * a visitor logged in to a different company must still see it. Their middleware
 * calls TenantScope::standDownForThisRequest().
 */
trait TenantScoped
{
    public static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            if (!Auth::check() || TenantScope::isStoodDown()) {
                return;
            }

            $model = $query->getModel();

            if (property_exists($model, 'tenantParent')) {
                $query->whereHas($model->tenantParent);

                return;
            }

            $query->where($model->getTable() . '.created_by', creatorId());
        });
    }
}
