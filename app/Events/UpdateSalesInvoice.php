<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;

class UpdateSalesInvoice
{

    use Dispatchable;

    public function __construct(
        public Request $request,
        public SalesInvoice $salesInvoice
    ) {}
}
