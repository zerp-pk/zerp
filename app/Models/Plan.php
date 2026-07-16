<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Classes\Module;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'number_of_users',
        'status',
        'free_plan',
        'modules',
        'package_price_yearly',
        'package_price_monthly',
        'storage_limit',
        'trial',
        'trial_days',
        'created_by',
    ];

    protected $casts = [
        'status' => 'boolean',
        'free_plan' => 'boolean',
        'trial' => 'boolean',
        'modules' => 'array',
        'package_price_yearly' => 'decimal:2',
        'package_price_monthly' => 'decimal:2',
        'storage_limit' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function getAvailableModulesForUser($userId)
    {
        return self::getUserSubscriptionModules($userId);
    }

    /**
     * The modules a company is entitled to: its plan's, and nothing else.
     *
     * user_active_modules records what a company picked and paid for at subscribe
     * time, but it is not the boundary. It used to be merged in here, which let it
     * widen the entitlement past the plan: PackageSeeder writes a row per installed
     * module, so a seeded company saw every module regardless of the plan it was on,
     * and editing a plan to drop a module left subscribers with the old rows.
     *
     * The plan is the boundary. A module has to be in the plan to be entitled, and
     * has to be installed and enabled to be usable.
     */
    public static function getUserSubscriptionModules($userId = null)
    {
        $user = $userId ? User::find($userId) : auth()->user();

        if (!$user) {
            return [];
        }

        // Super admin has access to all modules
        if ($user->hasRole('superadmin')) {
            return (new Module())->allEnabled();
        }

        $planModules = [];

        if ($user->active_plan) {
            $plan = self::find($user->active_plan);
            if ($plan && $plan->modules) {
                $planModules = is_array($plan->modules) ? $plan->modules : [];
            }
        }

        // Ensure the modules are actually installed and enabled.
        $enabled = (new Module())->allEnabled();

        return array_values(array_unique(array_intersect($planModules, $enabled)));
    }
}