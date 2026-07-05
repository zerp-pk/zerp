<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChPinned extends Model
{
    protected $table = 'ch_pinned';
    
    protected $fillable = [
        'user_id',
        'pinned_id',
    ];
}