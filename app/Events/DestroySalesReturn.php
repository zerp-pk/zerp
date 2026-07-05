<?php

namespace App\Events;

use App\Models\SalesInvoiceReturn;
use Illuminate\Foundation\Events\Dispatchable;

class DestroySalesReturn
{
    use Dispatchable;

    public function __construct(
        public SalesInvoiceReturn $salesReturn
    ) {}
}