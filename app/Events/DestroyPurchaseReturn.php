<?php

namespace App\Events;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyPurchaseReturn
{
    use Dispatchable;

    public function __construct(
        public PurchaseReturn $return
    ) {}
}
