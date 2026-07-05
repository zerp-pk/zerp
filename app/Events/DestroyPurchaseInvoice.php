<?php

namespace App\Events;

use App\Models\PurchaseInvoice;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyPurchaseInvoice
{
    use Dispatchable;

    public function __construct(
        public PurchaseInvoice $purchaseInvoice
    ) {}
}
