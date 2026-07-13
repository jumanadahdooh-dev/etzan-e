<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('article_category_id')
                ->nullable()
                ->constrained('article_categories')
                ->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();

            $table->text('excerpt')->nullable();
            $table->longText('content');

            $table->string('cover_image')->nullable();
            $table->string('icon')->nullable();

            $table->string('author_name')->default('فريق اتزان');
            $table->unsignedInteger('reading_minutes')->default(3);

            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_featured')->default(false);

            $table->timestamp('published_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
