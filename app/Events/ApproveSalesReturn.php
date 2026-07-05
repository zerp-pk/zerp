<?php

namespace App\Events;

use App\Models\SalesInvoiceReturn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApproveSalesReturn
{

    use Dispatchable;

    public function __construct(
        public SalesInvoiceReturn $salesReturn
    ) {}
}