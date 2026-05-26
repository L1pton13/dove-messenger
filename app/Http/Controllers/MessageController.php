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
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        // 1. Валидация входящих данных
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'body' => 'nullable|string', 
            'file' => 'nullable|file|max:51200', // Максимум 50 МБ
        ]);

        // Находим беседу
        $conversation = auth()->user()->conversations()->with('users')->findOrFail($request->conversation_id);
        
        // Очищаем текст от лишних пробелов, чтобы избежать отправки пустых текстовых сообщений
        $fullText = $request->body ? trim($request->body) : '';
        $maxLength = 5000;
        $createdMessages = [];
        $receiver = $conversation->users->where('id', '!=', auth()->id())->first();

        // 2. Обработка загрузки файла
        $filePath = null;
        $fileType = null;
        $originalName = '';

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName(); // Исходное имя файла для UI
            
            // Безопасное имя файла для файловой системы
            $fileName = time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('uploads', $fileName, 'public');
            $fileType = $file->getMimeType(); 
        }

        // ЛОГИКА ДЛЯ ЧИСТОГО ФАЙЛА (без сопутствующего текста)
        if (empty($fullText) && $filePath) {
            $message = $conversation->messages()->create([
                'sender_id' => auth()->id(),
                'body' => $originalName, // Передаем оригинальное имя файла, база данных довольна
                'file_path' => $filePath,
                'file_type' => $fileType,
                'is_read' => false,
            ]);

            // Подгружаем отправителя
            $message->load('sender');

            $this->broadcastMessageActions($conversation, $message, $receiver);
            
            return response()->json($message);
        }

        // 3. ЛОГИКА ДЛЯ ТЕКСТА (или ТЕКСТ + ФАЙЛ)
        $chunks = [];
        if (!empty($fullText)) {
            $length = mb_strlen($fullText, 'UTF-8');
            for ($i = 0; $i < $length; $i += $maxLength) {
                $chunks[] = mb_substr($fullText, $i, $maxLength, 'UTF-8');
            }
        } else {
            $chunks = [''];
        }
        
        foreach ($chunks as $index => $chunk) {
            // Прикрепляем файл ТОЛЬКО К ПЕРВОМУ куску сообщения
            $currentFilePath = ($index === 0) ? $filePath : null;
            $currentFileType = ($index === 0) ? $fileType : null;
            
            // Если к тексту крепится файл, в body первого куска остается текст пользователя, 
            // а имя файла фронтенд возьмет из структуры пути или вшитой логики
            $message = $conversation->messages()->create([
                'sender_id' => auth()->id(),
                'body' => $chunk, 
                'file_path' => $currentFilePath,
                'file_type' => $currentFileType,
                'is_read' => false,
            ]);

            $message->load('sender');

            $this->broadcastMessageActions($conversation, $message, $receiver);
            $createdMessages[] = $message;
        }

        return response()->json(count($createdMessages) === 1 ? $createdMessages[0] : $createdMessages);
    }

    private function broadcastMessageActions($conversation, $message, $receiver)
    {
        $conversation->touch();
        $message->load(['sender']);
        broadcast(new MessageSent($message))->toOthers();

        if ($receiver) {
            $receiver->conversations()->updateExistingPivot($conversation->id, ['is_hidden' => false]);
            $message->load(['conversation.users']);
            broadcast(new ConversationUpdated($message, $receiver->id))->toOthers();
        }
    }

    public function markAsRead($conversationId)
    {
        $conversation = auth()->user()->conversations()->findOrFail($conversationId);
        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        broadcast(new MessagesRead($conversationId, auth()->id()))->toOthers();
        return response()->json(['success' => true]);
    }

    public function destroy(Message $message)
    {
        if ($message->sender_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messageId = $message->id;
        $conversationId = $message->conversation_id;
        $conversation = \App\Models\Conversation::findOrFail($conversationId);
        $receiver = $conversation->users()->where('users.id', '!=', auth()->id())->first();

        $message->delete();

        if ($receiver) {
            broadcast(new MessageDeleted($messageId, $conversationId, $receiver->id));
        }

        return response()->json(['success' => true]);
    }

    public function clearForMe(Request $request, $conversationId)
    {
        $user = auth()->user();
        $updateData = ['cleared_at' => now()];

        if ($request->input('hidden')) {
            $updateData['is_hidden'] = true;
        }

        $user->conversations()->updateExistingPivot($conversationId, $updateData);
        return response()->json(['success' => true]);
    }

    public function clearOwnForEveryone($conversationId)
    {
        $userId = auth()->id();
        $conversation = \App\Models\Conversation::findOrFail($conversationId);
        $receiver = $conversation->users()->where('users.id', '!=', $userId)->first();

        \App\Models\Message::where('conversation_id', $conversationId)
            ->where('sender_id', $userId)
            ->where('cleared_at', null)
            ->delete();

        if ($receiver) {
            broadcast(new ClearOwnMessages($conversationId, $userId, $receiver->id));
        }

        return response()->json(['success' => true]);
    }
}