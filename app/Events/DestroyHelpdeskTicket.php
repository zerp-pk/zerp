<?php

namespace App\Events;

use App\Models\HelpdeskTicket;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyHelpdeskTicket
{
    use Dispatchable;

    public function __construct(
        public HelpdeskTicket $ticket,
    ) {}
}