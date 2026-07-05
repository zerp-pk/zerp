<?php

namespace App\Events;

use App\Models\PurchaseReturn;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreatePurchaseReturn
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public PurchaseReturn $return
    ) {}
}
