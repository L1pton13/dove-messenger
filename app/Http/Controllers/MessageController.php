<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\ConversationUpdated;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\MessageDeleted;
use App\Events\ClearOwnMessages;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'body' => 'required|string', 
        ]);

        $conversation = auth()->user()->conversations()->with('users')->findOrFail($request->conversation_id);
        
        $fullText = $request->body;
        $maxLength = 5000;

        // С помощью функции mb_str_split разбиваем строку на массив кусков по 5000 символов
        // (Используем mb_, чтобы корректно работало с кириллицей и эмодзи)
        $chunks = mb_str_split($fullText, $maxLength, 'UTF-8');
        
        $createdMessages = [];
        $receiver = $conversation->users->where('id', '!=', auth()->id())->first();

        // Перебираем каждый кусок текста и создаем для него отдельное сообщение
        foreach ($chunks as $chunk) {
            $message = $conversation->messages()->create([
                'sender_id' => auth()->id(),
                'body' => $chunk,
                'is_read' => false,
            ]);

            // Обновляем таймстемп чата updated_at
            $conversation->touch();

            // Отправляем сокет тем, кто уже сидит внутри открытого чата
            broadcast(new MessageSent($message))->toOthers();

            if ($receiver) {
                $receiver->conversations()->updateExistingPivot($conversation->id, ['is_hidden' => false]);
                $message->load(['sender', 'conversation.users']);
                // Отправляем сокет в личный канал для обновления списка чатов слева
                broadcast(new ConversationUpdated($message, $receiver->id))->toOthers();
            }

            $createdMessages[] = $message;
        }

        // Возвращаем массив всех созданных сообщений
        return response()->json($createdMessages);
    }

    public function markAsRead($conversationId)
    {
        $conversation = auth()->user()->conversations()->findOrFail($conversationId);

        // Обновляем все чужие сообщения в этом чате на "прочитано"
        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Стреляем сокетом в чат, чтобы у отправителя мгновенно поменялись иконки на 👀
        broadcast(new MessagesRead($conversationId, auth()->id()))->toOthers();

        return response()->json(['success' => true]);
    }

    // Удаление ОДНОГО сообщения для всех из БД
    public function destroy(Message $message)
    {
        // Удалять можно только свои сообщения
        if ($message->sender_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messageId = $message->id;
        $conversationId = $message->conversation_id;

        // Находим чат, чтобы вычислить собеседника, которому нужно отправить сокет
        $conversation = \App\Models\Conversation::findOrFail($conversationId);
        $receiver = $conversation->users()->where('users.id', '!=', auth()->id())->first();

        // Удаляем сообщение из базы данных
        $message->delete();

        // Шлем сокет адресно в личный канал собеседника (если он есть)
        if ($receiver) {
            // Передаем id сообщения, id чата и id получателя. Убираем ->toOthers()
            broadcast(new MessageDeleted($messageId, $conversationId, $receiver->id));
        }

        return response()->json(['success' => true]);
    }

    // Очистить весь чат ТОЛЬКО ДЛЯ СЕБЯ
    public function clearForMe(Request $request, $conversationId)
    {
        $user = auth()->user();

        $updateData = [
            'cleared_at' => now() // Фиксируем время очистки истории
        ];

        // Если фронтенд прислал hidden: true (нажата кнопка Удалить чат)
        if ($request->input('hidden')) {
            $updateData['is_hidden'] = true;
        }

        $user->conversations()->updateExistingPivot($conversationId, $updateData);

        return response()->json(['success' => true]);
    }

    // Удалить все СВОИ сообщения для ОБОИХ (из БД)
    public function clearOwnForEveryone($conversationId)
    {
        $userId = auth()->id();

        $conversation = \App\Models\Conversation::findOrFail($conversationId);
        $receiver = $conversation->users()->where('users.id', '!=', $userId)->first();

        // Удаляем все твои сообщения из этого чата
        \App\Models\Message::where('conversation_id', $conversationId)
            ->where('sender_id', $userId)
            ->delete();

        // Отправляем сокет-событие адресно получателю
        if ($receiver) {
            broadcast(new ClearOwnMessages($conversationId, $userId, $receiver->id));
        }

        return response()->json(['success' => true]);
    }
}