<?php

namespace App\Events;

use App\Models\HelpdeskReply;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateHelpdeskReply
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public HelpdeskReply $reply
    ) {}
}
