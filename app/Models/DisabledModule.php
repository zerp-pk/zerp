<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A module a company has switched off for itself.
 *
 * Kept apart from UserActiveModule, which records which paid add-ons the company
 * OWNS. Switching a module off is a preference and must be reversible; deleting the
 * entitlement would mean re-purchasing it to turn it back on.
 */
class DisabledModule extends Model
{
    protected $fillable = ['user_id', 'module'];

    /** Modules the given company has switched off. */
    public static function forCompany(?int $companyId): array
    {
        if (!$companyId) {
            return [];
        }

        return static::where('user_id', $companyId)->pluck('module')->all();
    }
}
