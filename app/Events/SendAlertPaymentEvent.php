<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendAlertPaymentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $userId;

    protected $totalAmount;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($userId, $totalAmount)
    {
        $this->userId = $userId;
        $this->totalAmount = $totalAmount;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('noti-payment.user.' . $this->userId);
    }

    public function broadcastAs() : string
    {
        return 'notification-payment';
    }

    public function broadcastWith() : array
    {
        return [
            'user_id'=>$this->userId,
            'total_amount'=>$this->totalAmount,
        ];
    }
}
