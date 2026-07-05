<?php

namespace App\Events;

use App\Models\HelpdeskCategory;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyHelpdeskCategory
{
    use Dispatchable;

    public function __construct(
        public HelpdeskCategory $category,
    ) {}
}