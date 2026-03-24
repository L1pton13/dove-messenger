<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    public function search(Request $request)
    {
        $tag = $request->input('tag');

        $user = User::where('tag', $tag)
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
}
