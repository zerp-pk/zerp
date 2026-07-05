<?php

namespace App\Events;

use App\Models\Transfer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateTransfer
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public Transfer $transfer,
    ) {}
}
