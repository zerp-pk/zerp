<?php

namespace App\Events;

use App\Models\PurchaseReturn;
use Illuminate\Foundation\Events\Dispatchable;

class CompletePurchaseReturn
{
    use Dispatchable;

    public function __construct(
        public PurchaseReturn $return
    ) {}
}
