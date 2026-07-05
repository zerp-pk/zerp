<?php

namespace App\Events;

use App\Models\Warehouse;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class UpdateWarehouse
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Warehouse $warehouse
    ) {}
}
