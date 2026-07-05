<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DefaultData
{
    use Dispatchable;

    public function __construct(
        public int $company_id,
        public ?string $user_module = null
    ) {}
}
