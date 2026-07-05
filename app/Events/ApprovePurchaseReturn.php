<?php

namespace App\Events;

use App\Models\PurchaseReturn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovePurchaseReturn
{
    use Dispatchable;

    public function __construct(
        public PurchaseReturn $return
    ) {}
}
