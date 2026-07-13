<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ai_chat_conversation_id')
                ->constrained('ai_chat_conversations')
                ->cascadeOnDelete();

            $table->enum('role', ['user', 'assistant', 'system']);

            $table->longText('content');

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['ai_chat_conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
