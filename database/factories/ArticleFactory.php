<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'user_id' => User::factory()->admin(),
            'source' => 'admin',
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 999999),
            'excerpt' => fake()->sentence(15),
            'content' => fake()->paragraphs(5, true),
            'author_name' => fake()->name(),
            'reading_minutes' => fake()->numberBetween(2, 8),
            'status' => 'published',
            'is_featured' => false,
            'published_at' => now(),
            'audience' => 'patient',
            'article_type' => 'health_article',
            'generated_by_ai' => false,
            'reviewed_by_admin' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
