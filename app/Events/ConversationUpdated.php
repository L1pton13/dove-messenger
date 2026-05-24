<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $receiverId;

    public function __construct(Message $message, $receiverId)
    {
        // Подгружаем отправителя
        $this->message = $message->load('sender');
        $this->receiverId = $receiverId;
    }

    // Вещаем на личный канал получателя сообщения
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->receiverId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }
}