<?php

namespace App\Events;

use App\Models\HelpdeskReply;
use Illuminate\Foundation\Events\Dispatchable;

class DestroyHelpdeskReply
{
    use Dispatchable;

    public function __construct(
        public HelpdeskReply $reply,
    ) {}
}