<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdminNotification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'body',
        'url',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function getIsUnreadAttribute(): bool
    {
        return is_null($this->read_at);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'doctor_application' => 'طلبات الأطباء',
            'message' => 'الرسائل',
            'system' => 'النظام',
            default => 'عام',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'doctor_application' => 'fa-solid fa-user-doctor',
            'message' => 'fa-regular fa-message',
            'system' => 'fa-solid fa-gear',
            default => 'fa-regular fa-bell',
        };
    }

    public function getTypeClassAttribute(): string
    {
        return match ($this->type) {
            'doctor_application' => 'is-doctor',
            'message' => 'is-message',
            'system' => 'is-system',
            default => 'is-general',
        };
    }
}
