<?php

namespace App\Permissions;

use Spatie\Permission\PermissionRegistrar;

/**
 * Spatie forgets the whole permission cache on every single grant, and the next
 * hasPermissionTo() reloads all of it from the database. Assigning a plan grants
 * hundreds of permissions one at a time across every installed module, so that is
 * hundreds of full reloads of a 1200+ row table inside one request, which is what
 * pushed plan subscription past the request timeout. See zerp-pk/zerp#70.
 *
 * The grants themselves are fine. Only the repeated invalidation is wasteful, so
 * bulk() collapses it into a single flush at the end.
 */
class BulkAwarePermissionRegistrar extends PermissionRegistrar
{
    private bool $deferring = false;

    private bool $missedFlush = false;

    /**
     * Run $callback with cache invalidation deferred, then flush once.
     */
    public function bulk(callable $callback)
    {
        if ($this->deferring) {
            return $callback(); // already inside a bulk block, let the outer one flush
        }

        $this->deferring = true;
        $this->missedFlush = false;

        try {
            return $callback();
        } finally {
            $this->deferring = false;

            if ($this->missedFlush) {
                $this->forgetCachedPermissions();
            }
        }
    }

    public function forgetCachedPermissions()
    {
        if ($this->deferring) {
            // Drop the in-memory copy so reads stay correct, but keep the shared
            // cache entry: rebuilding it on every grant is the expensive part.
            $this->permissions = null;
            $this->missedFlush = true;

            return true;
        }

        return parent::forgetCachedPermissions();
    }
}
