<?php

namespace App\Events;

use App\Models\Transfer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DestroyTransfer
{
    use Dispatchable;

    public function __construct(
        public Transfer $transfer,
    ) {}
}
