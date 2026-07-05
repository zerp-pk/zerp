<?php

namespace App\Events;

use App\Models\Warehouse;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyWarehouse
{
    use Dispatchable;

    public function __construct(
        public Warehouse $warehouse,
    ) {}
}