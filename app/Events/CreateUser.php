<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;

class CreateUser
{
    use Dispatchable;

    public function __construct(
        public Request $request,
        public User $user
    ) {}
}
