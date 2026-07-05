<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'code',
        'discount',
        'limit',
        'type',
        'minimum_spend',
        'maximum_spend',
        'limit_per_user',
        'expiry_date',
        'included_module',
        'excluded_module',
        'status',
        'created_by'
    ];

    protected $casts = [
        'expiry_date' => 'datetime',
        'status' => 'boolean',
        'included_module' => 'array',
        'excluded_module' => 'array',
        'discount' => 'decimal:2',
        'minimum_spend' => 'decimal:2',
        'maximum_spend' => 'decimal:2'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeValid($query)
    {
        return $query->where('status', true)
            ->where(function($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', now());
            });
    }
}