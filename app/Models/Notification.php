<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'module',
        'type',
        'action',
        'status',
        'permissions'
    ];
}
