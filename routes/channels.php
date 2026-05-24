<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    // Проверяем, состоит ли юзер в этой беседе
    return $user->conversations()->where('conversations.id', $conversationId)->exists();
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});