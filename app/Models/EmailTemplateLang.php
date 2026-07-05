<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateLang extends Model
{
    protected $fillable = [
        'parent_id',
        'lang',
        'subject',
        'variables',
        'content'
    ];

    protected $casts = [
        'variables' => 'array',
    ];
}
