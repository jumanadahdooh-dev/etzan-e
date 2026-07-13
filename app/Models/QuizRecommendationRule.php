<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizRecommendationRule extends Model
{
    protected $fillable = [
        'content_type',
        'result_type',
        'goal',
        'condition',
        'activity',
        'symptoms',
        'medication',
        'article_id',
        'text',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
