<?php

namespace App\Events;

use App\Models\HelpdeskCategory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateHelpdeskCategory
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public HelpdeskCategory $category
    ) {}
}