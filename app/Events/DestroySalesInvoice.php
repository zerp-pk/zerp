<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use App\Models\SalesInvoice;

class DestroySalesInvoice
{
    use Dispatchable;

    public function __construct(
        public SalesInvoice $salesInvoice,
    ) {}
}
