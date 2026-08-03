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

    /**
     * تسمية عربية لكل نوع إشعار حقيقي مستخدم فعلياً بالمشروع (مريض/دكتور/أدمن).
     * كانت موجودة بس بموديل AdminNotification القديم؛ نقلناها وكمّلناها هون
     * حتى تخدم كل الأدوار بعد توحيد نظام الإشعارات على app_notifications.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'appointment_pending', 'appointment_request_received', 'appointment_request_updated' => 'المواعيد',
            'appointment_confirmed', 'appointment_reschedule_accepted' => 'تأكيد موعد',
            'appointment_rejected', 'appointment_reschedule_declined' => 'إلغاء موعد',
            'appointment_reschedule_requested' => 'طلب تعديل موعد',
            'appointment_reminder_60', 'appointment_reminder_10', 'appointment_starting_now' => 'تذكير بموعد',

            'doctor_request_pending', 'doctor_followup_request_received' => 'طلبات المتابعة',
            'doctor_request_approved' => 'موافقة متابعة',
            'doctor_request_rejected' => 'رفض متابعة',
            'doctor_followup_completed' => 'انتهاء متابعة',

            'message_received' => 'الرسائل',
            'support_reply', 'support_message_sent' => 'الدعم الفني',

            'task_reminder', 'task_due' => 'المهام',
            'task_late' => 'مهمة متأخرة',
            'task_completed' => 'إنجاز مهمة',

            'meal_reviewed' => 'مراجعة وجبة',
            'calorie_goal_set' => 'هدف سعرات',
            'weight_logged' => 'قياس وزن',
            'article_approved' => 'اعتماد مقال',
            'article_rejected' => 'رفض مقال',
            'doctor_application' => 'طلبات الأطباء',

            'admin_message', 'doctor_message', 'message' => 'الرسائل',
            'system' => 'النظام',

            default => 'عام',
        };
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

            'meal_reviewed' => 'utensils',
            'calorie_goal_set' => 'flame',
            'weight_logged' => 'scale',
            'article_approved' => 'badge-check',
            'article_rejected' => 'circle-alert',
            'doctor_application' => 'user-plus',
            'doctor_followup_request_received', 'appointment_request_received' => 'bell-plus',
            'appointment_request_updated' => 'calendar-clock',
            'doctor_followup_completed' => 'star',

            'admin_message', 'doctor_message', 'message' => 'message-circle',
            'system' => 'settings',

            default => 'bell-ring',
        };
    }

    public function getTypeClassAttribute(): string
    {
        return match ($this->type) {
            'appointment_confirmed',
            'appointment_reschedule_accepted',
            'doctor_request_approved',
            'article_approved',
            'task_completed' => 'is-success',

            'appointment_rejected',
            'appointment_reschedule_declined',
            'doctor_request_rejected',
            'article_rejected',
            'task_late' => 'is-danger',

            'appointment_pending',
            'appointment_reschedule_requested',
            'appointment_request_received',
            'appointment_request_updated',
            'doctor_followup_request_received',
            'task_reminder',
            'task_due' => 'is-warning',

            'message_received',
            'support_reply',
            'support_message_sent',
            'meal_reviewed',
            'calorie_goal_set',
            'weight_logged' => 'is-message',

            default => 'is-general',
        };
    }
}