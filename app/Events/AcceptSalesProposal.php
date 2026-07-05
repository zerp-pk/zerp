<?php

namespace App\Events;

use App\Models\SalesProposal;
use Illuminate\Foundation\Events\Dispatchable;

class AcceptSalesProposal
{
    use Dispatchable;

    public function __construct(
        public SalesProposal $salesProposal
    ) {}
}
