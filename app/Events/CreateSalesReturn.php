<?php

namespace App\Events;

use App\Models\SalesInvoiceReturn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateSalesReturn
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public SalesInvoiceReturn $return
    ) {}
}