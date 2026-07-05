<?php

namespace App\Events;

use App\Models\SalesInvoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EditSalesInvoice
{
    use Dispatchable;

    public function __construct(
        public SalesInvoice $invoice
    ) {}
}
