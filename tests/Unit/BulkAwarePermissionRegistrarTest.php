<?php

namespace Tests\Unit;

use App\Permissions\BulkAwarePermissionRegistrar;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Assigning a plan grants hundreds of permissions one at a time across every module.
 * Spatie flushes its permission cache on each grant and the next check reloads the
 * whole table, which pushed plan subscription past the request timeout.
 * See zerp-pk/zerp#70.
 */
class BulkAwarePermissionRegistrarTest extends TestCase
{
    public function test_the_container_resolves_the_bulk_aware_registrar(): void
    {
        // If Spatie's provider ever wins the binding again, the fix is silently gone.
        $this->assertInstanceOf(
            BulkAwarePermissionRegistrar::class,
            app(PermissionRegistrar::class)
        );
    }

    public function test_it_defers_the_cache_flush_until_the_block_ends(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $key = $registrar->cacheKey;

        Cache::forever($key, 'sentinel');

        $registrar->bulk(function () use ($registrar, $key) {
            $registrar->forgetCachedPermissions();
            $registrar->forgetCachedPermissions();

            $this->assertSame('sentinel', Cache::get($key), 'cache should survive inside the block');
        });

        $this->assertNull(Cache::get($key), 'cache should be flushed once on exit');
    }

    public function test_it_does_not_flush_when_nothing_asked_it_to(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $key = $registrar->cacheKey;

        Cache::forever($key, 'sentinel');
        $registrar->bulk(fn () => null);

        $this->assertSame('sentinel', Cache::get($key));
        Cache::forget($key);
    }

    public function test_it_returns_the_callback_value_and_flushes_after_an_exception(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $key = $registrar->cacheKey;

        $this->assertSame('value', $registrar->bulk(fn () => 'value'));

        Cache::forever($key, 'sentinel');

        try {
            $registrar->bulk(function () use ($registrar) {
                $registrar->forgetCachedPermissions();
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertNull(Cache::get($key), 'a throwing block must still flush');
    }

    public function test_nested_blocks_flush_once_at_the_outermost_exit(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $key = $registrar->cacheKey;

        Cache::forever($key, 'sentinel');

        $registrar->bulk(function () use ($registrar, $key) {
            $registrar->bulk(function () use ($registrar) {
                $registrar->forgetCachedPermissions();
            });

            $this->assertSame('sentinel', Cache::get($key), 'inner block must not flush');
        });

        $this->assertNull(Cache::get($key));
    }
}
