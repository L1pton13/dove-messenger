<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClearOwnMessages implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $senderId;
    protected $receiverId; // Добавляем свойство для ID получателя

    // Добавляем третий параметр $receiverId в конструктор
    public function __construct($conversationId, $senderId, $receiverId)
    {
        $this->conversationId = $conversationId;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->receiverId)];
    }

    public function broadcastAs(): string
    {
        return 'messages.cleared_own';
    }
}