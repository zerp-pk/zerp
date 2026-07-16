<?php

namespace App\Models\Concerns;

/**
 * Controls the tenant boundary applied by the TenantScoped trait.
 *
 * A class rather than static methods on the trait itself: PHP deprecates calling a
 * static trait method directly, and the flag is shared across every scoped model,
 * so it belongs somewhere shared.
 */
class TenantScope
{
    private const FLAG = 'tenant.scope.stood_down';

    /**
     * Lift the tenant boundary for the current request.
     *
     * For public per-company portals only — the support-ticket help centre and the
     * recruitment job board serve one company's data to the world, addressed by a
     * slug in the URL rather than by who is logged in. Their controllers already
     * filter by that company explicitly; lifting the ambient boundary is what lets
     * a visitor signed in to a *different* company see the portal instead of an
     * empty page.
     *
     * Flagged on the container, so it dies with the request and cannot leak into
     * the next one.
     */
    public static function standDownForThisRequest(): void
    {
        app()->instance(self::FLAG, true);
    }

    public static function isStoodDown(): bool
    {
        return app()->bound(self::FLAG);
    }
}
