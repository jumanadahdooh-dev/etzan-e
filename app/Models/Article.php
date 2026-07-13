<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Article extends Model
{
    protected $fillable = [
        'user_id',
        'specialty_id',
        'source',
        'article_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image',
        'icon',
        'author_name',
        'reading_minutes',
        'status',
        'is_featured',
        'approved_by',
        'approved_at',
        'submitted_at',
        'rejection_reason',
        'published_at',
        'audience',
        'article_type',
        'generated_by_ai',
        'reviewed_by_admin',
        'source_title',
        'source_url',
        'source_year',
        'medical_disclaimer',
        'ai_prompt',
        'ai_generation_metadata',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'published_at' => 'datetime',
        'generated_by_ai' => 'boolean',
        'reviewed_by_admin' => 'boolean',
        'ai_generation_metadata' => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $query) {
                $query
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'pending_review' => 'قيد المراجعة',
            'published' => 'منشور',
            'rejected' => 'مرفوض',
            default => 'غير معروف',
        };
    }

    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'is-draft',
            'pending_review' => 'is-pending',
            'published' => 'is-published',
            'rejected' => 'is-rejected',
            default => 'is-draft',
        };
    }

    public function getSourceLabelAttribute(): string
    {
        if ($this->generated_by_ai ?? false) {
            return 'AI / مسودة ذكية';
        }

        return match ($this->source) {
            'doctor' => 'طبيب',
            'admin' => 'الإدارة',
            default => 'غير محدد',
        };
    }


    public function getArticleTypeLabelAttribute(): string
    {
        return match ($this->article_type ?? 'health_article') {
            'research_summary' => 'ملخص بحثي',
            'quick_tip' => 'نصائح سريعة',
            'general_info' => 'معلومة عامة',
            'wellness_idea' => 'فكرة صحية',
            'health_wisdom' => 'حكمة اليوم',
            'motivational_quote' => 'رسالة اليوم',
            'health_article' => 'مقال صحي',
            default => 'مقال صحي',
        };
    }

    public function getAudienceLabelAttribute(): string
    {
        return match ($this->audience ?? 'patient') {
            'weight_loss' => 'خسارة وزن',
            'weight_gain' => 'زيادة وزن صحية',
            'diabetes' => 'سكري',
            'heart_health' => 'صحة القلب',
            'low_activity' => 'نشاط بسيط',
            'sleep_health' => 'النوم',
            'hydration' => 'شرب الماء',
            'general' => 'عام',
            'patient' => 'مريض',
            default => 'مريض',
        };
    }

    public function getPublicAuthorNameAttribute(): string
    {
        if ($this->author_name) {
            return $this->author_name;
        }

        if ($this->author) {
            return $this->author->name;
        }

        return 'فريق اتزان';
    }

    public function getCoverImageUrlAttribute(): string
    {
        if (!$this->cover_image) {
            return asset('front/image/articles/article-1.jpg');
        }

        if (str_starts_with($this->cover_image, 'http://') || str_starts_with($this->cover_image, 'https://')) {
            return $this->cover_image;
        }

        if (str_starts_with($this->cover_image, '/')) {
            return $this->cover_image;
        }

        if (str_starts_with($this->cover_image, 'front/') || str_starts_with($this->cover_image, 'assets/')) {
            return asset($this->cover_image);
        }

        if (str_starts_with($this->cover_image, 'storage/')) {
            return asset($this->cover_image);
        }

        return Storage::url($this->cover_image);
    }

    public function getReadingTimeLabelAttribute(): string
    {
        if (!$this->reading_minutes) {
            return '5 دقائق قراءة';
        }

        return $this->reading_minutes . ' دقائق قراءة';
    }

    public function getPublishDateLabelAttribute(): string
    {
        $date = $this->published_at ?: $this->created_at;

        return $date ? $date->format('Y-m-d') : '';
    }
}
