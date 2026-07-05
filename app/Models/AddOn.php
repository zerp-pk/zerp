<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddOn extends Model
{
    protected $fillable = [
        'module',
        'name',
        'monthly_price',
        'yearly_price',
        'image',
        'is_enable',
        'for_admin',
        'package_name',
        'priority',
        'media_id',
    ];

    protected $casts = [
        'is_enable' => 'boolean',
        'for_admin' => 'boolean',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2'
    ];

    public function media()
    {
        return $this->belongsTo(\Spatie\MediaLibrary\MediaCollections\Models\Media::class, 'media_id');
    }
}
