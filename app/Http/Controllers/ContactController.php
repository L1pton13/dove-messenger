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
        $tag = $request->input('tag');

        $user = User::where('tag', 'like', $tag . '%' )
                    ->where('id', '!=', $request->user()->id)
                    ->first();
                
        if ($user) {
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'tag' => $user->tag,
            ]);
        }

        return response()->json(['message' => "Пользователь $tag не найден"], 404);
    }

    public function startConversation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $authUserId = $request->user()->id;
        $otherUserId = $request->user_id;

        $existingConversation = $request->user()->conversations()
            ->whereHas('users', function ($query) use ($otherUserId) {
                $query->where('users.id', $otherUserId);
            })->first();

        if ($existingConversation){
            return response()->json($existingConversation);
        }

        return DB::transaction(function () use ($authUserId, $otherUserId) {
            $conversation = Conversation::create();

            $conversation->users()->attach([$authUserId, $otherUserId]);

            return response()->json($conversation);
        });
    }

    public function getUserChats()
    {
        $user = auth()->user();

        $conversations = $user->conversations()
            // Загружаем чаты, только если пользователь их не удалил
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

        // Фильтруем сообщения внутри чатов по дате очистки
        $conversations->each(function ($conversation) {
            $clearedAt = $conversation->pivot->cleared_at;

            if ($clearedAt) {
                // Добавляем ->values() в самом конце фильтрации сообщений!
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
