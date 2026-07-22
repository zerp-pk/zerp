<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;

/**
 * Index scopes used to fall back to whereRaw('1 = 0') when a user held none of the
 * granular manage-any/manage-own permissions, so a misconfigured role read as an
 * empty page instead of a denial. denyAccess() throws instead. See zerp-pk/zerp#47.
 */
class DenyAccessTest extends TestCase
{
    public function test_it_throws_an_authorization_exception(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Permission denied');

        denyAccess();
    }

    /**
     * The call sites live inside a `->where(function ($q) { ... })` closure. Eloquent
     * runs that closure while building the query, so the denial surfaces on the
     * request rather than being swallowed into a query that returns nothing.
     */
    public function test_it_denies_from_inside_a_nested_where_closure(): void
    {
        $this->expectException(AuthorizationException::class);

        User::query()->where(function ($q) {
            denyAccess();
        })->toSql();
    }
}
