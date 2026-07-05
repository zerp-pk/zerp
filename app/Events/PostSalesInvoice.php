<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\SalesInvoice;

class PostSalesInvoice
{

    use Dispatchable;

    public function __construct(
        public SalesInvoice $salesInvoice
    ) {}
}