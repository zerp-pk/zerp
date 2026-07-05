<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('messenger.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});