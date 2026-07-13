<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'article_category_id');
    }

    public function publishedArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'article_category_id')
            ->where('status', 'published');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
