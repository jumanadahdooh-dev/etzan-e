<?php

namespace App\Policies;

use App\Models\AiChatConversation;
use App\Models\User;

class AiChatConversationPolicy
{
    public function view(User $user, AiChatConversation $conversation): bool
    {
        return (int) $conversation->user_id === (int) $user->id;
    }

    public function delete(User $user, AiChatConversation $conversation): bool
    {
        return (int) $conversation->user_id === (int) $user->id;
    }
}
