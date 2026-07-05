<?php

namespace App\Events;

use App\Models\PurchaseInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class UpdatePurchaseInvoice
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public PurchaseInvoice $purchaseInvoice
    ) {}
}