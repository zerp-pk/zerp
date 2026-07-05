<?php

namespace App\Events;

use App\Models\SalesProposal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class UpdateSalesProposal
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public SalesProposal $salesProposal
    ) {}
}
