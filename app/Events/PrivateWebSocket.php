<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrivateWebSocket implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $nameNotification;

    public $targetUserIds;

    public $type;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    // public function __construct($fullname, $targetUserIds)
    // {
    //     $this->fullname = $fullname;
    //     $this->targetUserIds = $targetUserIds;
    // }
    public function __construct($nameNotification, $targetUserIds, $type)
    {
        $this->nameNotification = $nameNotification;
        $this->targetUserIds = $targetUserIds;
        $this->type = $type;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // return new PrivateChannel('shared-private-notification');
        $channels = [];

        // foreach ($this->targetUserIds as $userId) {
        //     $channels[] = new PrivateChannel('buzz.user.' . $userId);
        // }
        foreach ($this->targetUserIds as $userId) {
            if ($this->type === 'user') {
                $channels[] = new PrivateChannel('buzz.user.' . $userId);
            } elseif ($this->type === 'calendar') {
                $channels[] = new PrivateChannel('buzz.calendar.' . $userId);
            }
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'notification';
    }
}
