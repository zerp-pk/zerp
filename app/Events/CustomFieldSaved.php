<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomFieldSaved
{
    use Dispatchable, SerializesModels;

    public $model;
    public $customFields;
    public $action;

    public function __construct($model, $customFields = [], $action = 'created')
    {
        $this->model = $model;
        $this->customFields = $customFields;
        $this->action = $action;
    }
}