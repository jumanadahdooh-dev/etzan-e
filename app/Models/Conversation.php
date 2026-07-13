<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'guest_name',
        'guest_email',
        'subject',
        'source',
        'status',
        'last_message_at',
        'unread_by_admin',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'unread_by_admin' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id')
            ->orderBy('created_at');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'conversation_id')
            ->latestOfMany();
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name ?? 'مستخدم';
        }

        if ($this->guest_name) {
            return $this->guest_name;
        }

        return 'زائر';
    }

    public function getDisplayEmailAttribute(): ?string
    {
        if ($this->user) {
            return $this->user->email ?? null;
        }

        return $this->guest_email;
    }

    public function getSenderTypeLabelAttribute(): string
    {
        return $this->user_id ? 'مريض / مستخدم' : 'زائر';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'مفتوحة',
            'closed' => 'مغلقة',
            default => 'غير معروف',
        };
    }
}
