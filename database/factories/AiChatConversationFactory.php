<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiChatConversation>
 */
class AiChatConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->patient(),
            'title' => fake()->sentence(4),
            'status' => 'active',
            'last_message_at' => now(),
        ];
    }
}
