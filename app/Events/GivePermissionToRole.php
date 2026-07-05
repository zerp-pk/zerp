<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class GivePermissionToRole
{
    use Dispatchable;

    public function __construct(
        public int $role_id,
        public string $rolename,
        public ?string $user_module = null
    ) {}
}
