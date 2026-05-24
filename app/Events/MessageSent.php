<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// ОБЯЗАТЕЛЬНО добавляем implements ShouldBroadcast
class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        // Подгружаем связь sender, чтобы Vue знал, кто отправитель
        $this->message = $message->load('sender');
    }

    // Определяем канал связи. Делаем его приватным, чтобы чужие не читали
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->conversation_id),
        ];
    }

    // Имя события, которое полетит на фронтенд
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}