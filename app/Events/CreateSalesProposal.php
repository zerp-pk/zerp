<?php

namespace App\Events;

use App\Models\SalesProposal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateSalesProposal
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public SalesProposal $proposal
    ) {}
}
