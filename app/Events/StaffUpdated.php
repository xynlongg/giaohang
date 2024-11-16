<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StaffUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $action;
    public $message;
    public $postOfficeName;

    public function __construct($userId, $action, $message, $postOfficeName = null)
    {
        $this->userId = $userId;
        $this->action = $action;
        $this->message = $message;
        $this->postOfficeName = $postOfficeName;
    }

    public function broadcastOn()
    {
        return new Channel('staff-updates');
    }
}