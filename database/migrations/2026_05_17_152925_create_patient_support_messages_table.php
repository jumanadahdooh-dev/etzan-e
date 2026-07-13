<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('patient_support_messages')) {
            Schema::create('patient_support_messages', function (Blueprint $table) {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string('sender_type')->default('patient');
                $table->string('category')->nullable();
                $table->string('subject')->nullable();
                $table->text('message');
                $table->timestamp('read_at')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_support_messages');
    }
};
