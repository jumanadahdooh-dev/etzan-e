<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_recommendation_rules', function (Blueprint $table) {
            $table->id();

            $table->string('content_type');
            // recommendation / task / article

            $table->string('result_type')->nullable();
            // doctor / nutritionist / self_care

            $table->string('goal')->nullable();
            $table->string('condition')->nullable();
            $table->string('activity')->nullable();
            $table->string('symptoms')->nullable();
            $table->string('medication')->nullable();

            $table->foreignId('article_id')
                ->nullable()
                ->constrained('articles')
                ->nullOnDelete();

            $table->text('text')->nullable();

            $table->integer('priority')->default(1);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['content_type', 'result_type']);
            $table->index(['goal', 'condition', 'activity', 'symptoms', 'medication']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_recommendation_rules');
    }
};
