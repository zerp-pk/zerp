<?php

namespace App\Events;

use App\Models\HelpdeskTicket;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class UpdateHelpdeskTicket
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public HelpdeskTicket $ticket
    ) {}
}
