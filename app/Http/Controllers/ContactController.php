<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function search(Request $request)
    {
        $name = $request->input('name');

        if (empty($name)) {
            return response()->json(null);
        }

        $user = User::where('name', 'like', '%' . $name . '%')
                    ->where('id', '!=', $request->user()->id)
                    ->first();
                
        if ($user) {
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'tag' => $user->tag,
                'avatar_url' => $user->avatar 
                    ? (str_starts_with($user->avatar, 'http') ? $user->avatar : asset('storage/' . $user->avatar)) 
                    : null,
            ]);
        }

        return response()->json(null);
    }

    public function startConversation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $authUserId = $request->user()->id;
        $otherUserId = $request->user_id;

        // 1. Ищем существующий приватный чат
        $existingConversation = Conversation::whereHas('users', function ($query) use ($authUserId) {
                $query->where('users.id', $authUserId);
            })
            ->whereHas('users', function ($query) use ($otherUserId) {
                $query->where('users.id', $otherUserId);
            })
            ->first();

        if ($existingConversation) {
            // Если чат был скрыт (is_hidden = 1) у авторизованного юзера, возвращаем его видимость
            $existingConversation->users()->updateExistingPivot($authUserId, [
                'is_hidden' => 0
            ]);

            // Подгружаем пользователей (исключая себя) и сообщения
            $existingConversation->load([
                'users' => function($query) use ($authUserId) {
                    $query->where('users.id', '!=', $authUserId);
                },
                'messages' => function($query) {
                    $query->orderBy('created_at', 'asc');
                }
            ]);

            // Проверяем, была ли очищена история по cleared_at
            $pivotData = DB::table('conversation_user')
                ->where('conversation_id', $existingConversation->id)
                ->where('user_id', $authUserId)
                ->first();

            if ($pivotData && $pivotData->cleared_at) {
                $clearedAt = $pivotData->cleared_at;
                $filteredMessages = $existingConversation->messages->filter(function ($message) use ($clearedAt) {
                    return $message->created_at > $clearedAt;
                })->values(); 

                $existingConversation->setRelation('messages', $filteredMessages);
            } else {
                // Если сообщений нет или они отфильтрованы, гарантируем коллекцию
                if (!$existingConversation->messages) {
                    $existingConversation->setRelation('messages', collect());
                }
            }

            return response()->json($existingConversation);
        }

        // 2. Если чата никогда не существовало — создаем транзакцию
        return DB::transaction(function () use ($authUserId, $otherUserId) {
            $conversation = Conversation::create();

            // Создаем связь с параметрами по умолчанию
            $conversation->users()->attach([
                $authUserId => ['is_hidden' => 0],
                $otherUserId => ['is_hidden' => 0]
            ]);

            // Подгружаем собеседника, чтобы фронтенд сразу отобразил его имя в списке
            $conversation->load([
                'users' => function($query) use ($authUserId) {
                    $query->where('users.id', '!=', $authUserId);
                }
            ]);

            // Важно для реактивности Vue: принудительно устанавливаем пустой массив сообщений
            $conversation->setRelation('messages', collect());

            return response()->json($conversation);
        });
    }

    public function getUserChats()
    {
        $user = auth()->user();

        $conversations = $user->conversations()
            ->wherePivot('is_hidden', false) 
            ->with([
                'users' => function($query) {
                    $query->where('users.id', '!=', auth()->id());
                },
                'messages' => function($query) {
                    $query->orderBy('created_at', 'asc');
                }
            ])
            ->latest('updated_at')
            ->get();

        $conversations->each(function ($conversation) {
            $clearedAt = $conversation->pivot->cleared_at;

            if ($clearedAt) {
                $filteredMessages = $conversation->messages->filter(function ($message) use ($clearedAt) {
                    return $message->created_at > $clearedAt;
                })->values(); 

                $conversation->setRelation('messages', $filteredMessages);
            }
        });

        return Inertia::render('Dashboard', [
            'conversations' => $conversations
        ]);
    }
}