<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $fillable = [
        'recipient_user_id',
        'recipient_role',
        'actor_user_id',
        'type',
        'title',
        'body',
        'url',
        'related_id',
        'related_type',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        return $query->where('recipient_user_id', $userId);
    }

    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->where('recipient_role', $role);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('read_at')
                ->orWhere('is_read', false);
        });
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNotNull('read_at')
                ->orWhere('is_read', true);
        });
    }

    public function getIsUnreadAttribute(): bool
    {
        return ! $this->is_read && is_null($this->read_at);
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'appointment_pending' => 'clock-3',
            'appointment_confirmed' => 'badge-check',
            'appointment_rejected' => 'circle-alert',
            'appointment_reschedule_requested' => 'calendar-clock',
            'appointment_reminder_60' => 'alarm-clock',
            'appointment_reminder_10' => 'timer',
            'appointment_starting_now' => 'radio',

            'doctor_request_pending' => 'stethoscope',
            'doctor_request_approved' => 'badge-check',
            'doctor_request_rejected' => 'circle-alert',

            'message_received' => 'message-circle',
            'support_reply' => 'life-buoy',
            'support_message_sent' => 'life-buoy',

            'task_reminder' => 'alarm-clock',
            'task_due' => 'list-checks',
            'task_late' => 'circle-alert',
            'task_completed' => 'check-circle',

            'admin_message' => 'message-circle',
            'system' => 'settings',

            default => 'bell-ring',
        };
    }

    public function getTypeClassAttribute(): string
    {
        return match ($this->type) {
            'appointment_confirmed',
            'doctor_request_approved',
            'task_completed' => 'is-success',

            'appointment_rejected',
            'doctor_request_rejected',
            'task_late' => 'is-danger',

            'appointment_pending',
            'appointment_reschedule_requested',
            'task_reminder',
            'task_due' => 'is-warning',

            'message_received',
            'support_reply',
            'support_message_sent' => 'is-message',

            default => 'is-general',
        };
    }
}