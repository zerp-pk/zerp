<?php

namespace App\Events;

use App\Models\SalesInvoice;
use App\Models\SalesProposal;
use Illuminate\Foundation\Events\Dispatchable;

class ConvertSalesProposal
{
    use Dispatchable;

    public function __construct(
        public SalesProposal $salesProposal,
        public SalesInvoice $invoice
    ) {}
}
