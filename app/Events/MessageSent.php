<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $receiverId;

    public function __construct($message, $receiverId)
    {
        $this->message = $message;
        $this->receiverId = $receiverId;
        
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

        return new Channel('messenger.' . $this->receiverId);
    }

    public function broadcastWith()
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'from_id' => $this->message->from_id,
                'to_id' => $this->message->to_id,
                'body' => $this->message->body,
                'attachment' => $this->message->attachment,
                'seen' => $this->message->seen,
                'created_at' => $this->message->created_at,
                'updated_at' => $this->message->updated_at,
            ],
            'sender' => $this->message->fromUser
        ];
    }
}