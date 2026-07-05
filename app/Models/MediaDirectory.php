<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaDirectory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'created_by',
        'creator_id',
    ];

    public function media(): HasMany
    {
        return $this->hasMany(\Spatie\MediaLibrary\MediaCollections\Models\Media::class, 'directory_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Root-to-leaf breadcrumb chain, including this directory itself.
     */
    public function ancestors(): array
    {
        $chain = [];
        $node = $this;
        while ($node) {
            array_unshift($chain, ['id' => $node->id, 'name' => $node->name]);
            $node = $node->parent;
        }
        return $chain;
    }
}
