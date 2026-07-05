<?php

namespace App\Events;

use App\Models\PurchaseInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EditPurchaseInvoice
{

    use Dispatchable;

    public function __construct(
        public PurchaseInvoice $invoice
    ) {}
}
