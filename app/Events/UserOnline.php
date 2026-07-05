<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class UserOnline implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
        // Set dynamic Pusher config
        $this->configurePusher();
    }

    private function configurePusher()
    {
        $pusherKey = admin_setting('pusher_app_key');
        $pusherSecret = admin_setting('pusher_app_secret');
        $pusherAppId = admin_setting('pusher_app_id');
        $pusherCluster = admin_setting('pusher_app_cluster');
        
        if ($pusherKey && $pusherSecret && $pusherAppId) {
            Config::set('broadcasting.default', 'pusher');
            Config::set('broadcasting.connections.pusher.key', $pusherKey);
            Config::set('broadcasting.connections.pusher.secret', $pusherSecret);
            Config::set('broadcasting.connections.pusher.app_id', $pusherAppId);
            Config::set('broadcasting.connections.pusher.options.cluster', $pusherCluster);
        }
    }

    public function broadcastOn()
    {
        // Broadcast to all users' messenger channels
        $channels = [];
        $users = \App\Models\User::all();
        foreach ($users as $user) {
            $channels[] = new Channel("messenger.{$user->id}");
        }
        return $channels;
    }

    public function broadcastAs()
    {
        return 'UserOnline';
    }
}