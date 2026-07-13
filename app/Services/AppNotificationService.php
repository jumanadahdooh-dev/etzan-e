<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AppNotificationService
{
    private string $table = 'app_notifications';

    public function send(
        ?int $recipientUserId,
        ?string $recipientRole,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        ?int $actorUserId = null,
        ?int $relatedId = null,
        ?string $relatedType = null,
        array $data = []
    ): ?int {
        if (! $recipientUserId || ! Schema::hasTable($this->table)) {
            return null;
        }

        $payload = [];

        $this->putIfColumnExists($payload, 'recipient_user_id', $recipientUserId);
        $this->putIfColumnExists($payload, 'recipient_role', $recipientRole);
        $this->putIfColumnExists($payload, 'actor_user_id', $actorUserId);

        $this->putIfColumnExists($payload, 'type', $type);
        $this->putIfColumnExists($payload, 'title', $title);
        $this->putIfColumnExists($payload, 'body', $body);
        $this->putIfColumnExists($payload, 'url', $url);

        $this->putIfColumnExists($payload, 'related_id', $relatedId);
        $this->putIfColumnExists($payload, 'related_type', $relatedType);

        if ($this->columnExists('data')) {
            $payload['data'] = ! empty($data)
                ? json_encode($data, JSON_UNESCAPED_UNICODE)
                : null;
        }

        if ($this->columnExists('is_read')) {
            $payload['is_read'] = false;
        }

        if ($this->columnExists('read_at')) {
            $payload['read_at'] = null;
        }

        if ($this->columnExists('created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->columnExists('updated_at')) {
            $payload['updated_at'] = now();
        }

        if (empty($payload)) {
            return null;
        }

        return (int) DB::table($this->table)->insertGetId($payload);
    }

    public function unreadCount(?int $recipientUserId, ?string $recipientRole = null): int
    {
        if (! $recipientUserId || ! Schema::hasTable($this->table)) {
            return 0;
        }

        $query = DB::table($this->table)
            ->where('recipient_user_id', $recipientUserId);

        if ($recipientRole && $this->columnExists('recipient_role')) {
            $query->where('recipient_role', $recipientRole);
        }

        $this->applyUnreadCondition($query);

        return (int) $query->count();
    }

    public function latestUnread(?int $recipientUserId, int $limit = 8, ?string $recipientRole = null): array
    {
        if (! $recipientUserId || ! Schema::hasTable($this->table)) {
            return [];
        }

        $query = DB::table($this->table)
            ->where('recipient_user_id', $recipientUserId);

        if ($recipientRole && $this->columnExists('recipient_role')) {
            $query->where('recipient_role', $recipientRole);
        }

        $this->applyUnreadCondition($query);
        $this->applyLatestOrder($query);

        return $query
            ->limit($limit)
            ->get()
            ->map(fn ($notification) => $this->formatNotification($notification))
            ->toArray();
    }

    public function latestForUser(?int $recipientUserId, int $limit = 20, ?string $recipientRole = null): array
    {
        if (! $recipientUserId || ! Schema::hasTable($this->table)) {
            return [];
        }

        $query = DB::table($this->table)
            ->where('recipient_user_id', $recipientUserId);

        if ($recipientRole && $this->columnExists('recipient_role')) {
            $query->where('recipient_role', $recipientRole);
        }

        $this->applyLatestOrder($query);

        return $query
            ->limit($limit)
            ->get()
            ->map(fn ($notification) => $this->formatNotification($notification))
            ->toArray();
    }

    public function markAsRead(int $notificationId, int $recipientUserId): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        $payload = [];

        if ($this->columnExists('read_at')) {
            $payload['read_at'] = now();
        }

        if ($this->columnExists('is_read')) {
            $payload['is_read'] = true;
        }

        if ($this->columnExists('updated_at')) {
            $payload['updated_at'] = now();
        }

        if (empty($payload)) {
            return;
        }

        DB::table($this->table)
            ->where('id', $notificationId)
            ->where('recipient_user_id', $recipientUserId)
            ->update($payload);
    }

    public function markAllAsRead(int $recipientUserId, ?string $recipientRole = null): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        $payload = [];

        if ($this->columnExists('read_at')) {
            $payload['read_at'] = now();
        }

        if ($this->columnExists('is_read')) {
            $payload['is_read'] = true;
        }

        if ($this->columnExists('updated_at')) {
            $payload['updated_at'] = now();
        }

        if (empty($payload)) {
            return;
        }

        $query = DB::table($this->table)
            ->where('recipient_user_id', $recipientUserId);

        if ($recipientRole && $this->columnExists('recipient_role')) {
            $query->where('recipient_role', $recipientRole);
        }

        $this->applyUnreadCondition($query);

        $query->update($payload);
    }

    public function deleteForUser(int $notificationId, int $recipientUserId): void
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        DB::table($this->table)
            ->where('id', $notificationId)
            ->where('recipient_user_id', $recipientUserId)
            ->delete();
    }

    private function applyUnreadCondition($query): void
    {
        if ($this->columnExists('read_at') && $this->columnExists('is_read')) {
            $query->where(function ($inner) {
                $inner->whereNull('read_at')
                    ->orWhere('is_read', false);
            });

            return;
        }

        if ($this->columnExists('read_at')) {
            $query->whereNull('read_at');
            return;
        }

        if ($this->columnExists('is_read')) {
            $query->where('is_read', false);
        }
    }

    private function applyLatestOrder($query): void
    {
        if ($this->columnExists('created_at')) {
            $query->orderByDesc('created_at');
            return;
        }

        $query->orderByDesc('id');
    }

    private function formatNotification(object $notification): array
    {
        $type = $notification->type ?? 'general';
        $createdAt = $notification->created_at ?? null;

        return [
            'id' => $notification->id,
            'recipient_user_id' => $notification->recipient_user_id ?? null,
            'recipient_role' => $notification->recipient_role ?? null,
            'actor_user_id' => $notification->actor_user_id ?? null,

            'type' => $type,
            'title' => $notification->title ?? 'إشعار',
            'body' => $notification->body ?? '',
            'url' => $notification->url ?: '#',

            'related_id' => $notification->related_id ?? null,
            'related_type' => $notification->related_type ?? null,
            'data' => $this->decodeData($notification->data ?? null),

            'is_read' => $this->isRead($notification),
            'is_unread' => ! $this->isRead($notification),

            'icon' => $this->iconForType($type),
            'class' => $this->classForType($type),

            'created_at' => $createdAt,
            'time' => $createdAt ? Carbon::parse($createdAt)->diffForHumans() : '',
        ];
    }

    private function isRead(object $notification): bool
    {
        if (property_exists($notification, 'read_at') && ! empty($notification->read_at)) {
            return true;
        }

        if (property_exists($notification, 'is_read')) {
            return (bool) $notification->is_read;
        }

        return false;
    }

    private function decodeData(mixed $data): array
    {
        if (! $data) {
            return [];
        }

        if (is_array($data)) {
            return $data;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function iconForType(string $type): string
    {
        return match ($type) {
            'appointment_pending' => 'clock-3',
            'appointment_confirmed' => 'badge-check',
            'appointment_rejected' => 'circle-alert',
            'appointment_reschedule_requested' => 'calendar-clock',
            'appointment_reschedule_accepted' => 'badge-check',
            'appointment_reschedule_declined' => 'circle-alert',
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
            'doctor_message' => 'message-circle',
            'system' => 'settings',

            default => 'bell-ring',
        };
    }

    private function classForType(string $type): string
    {
        return match ($type) {
            'appointment_confirmed',
            'appointment_reschedule_accepted',
            'doctor_request_approved',
            'task_completed' => 'is-success',

            'appointment_rejected',
            'appointment_reschedule_declined',
            'doctor_request_rejected',
            'task_late' => 'is-danger',

            'appointment_pending',
            'appointment_reschedule_requested',
            'appointment_reminder_60',
            'appointment_reminder_10',
            'appointment_starting_now',
            'task_reminder',
            'task_due' => 'is-warning',

            'message_received',
            'support_reply',
            'support_message_sent',
            'admin_message',
            'doctor_message' => 'is-message',

            default => 'is-general',
        };
    }

    private function putIfColumnExists(array &$payload, string $column, mixed $value): void
    {
        if ($this->columnExists($column)) {
            $payload[$column] = $value;
        }
    }

    private function columnExists(string $column): bool
    {
        static $cache = [];

        $key = $this->table . '.' . $column;

        if (! array_key_exists($key, $cache)) {
            $cache[$key] = Schema::hasTable($this->table)
                && Schema::hasColumn($this->table, $column);
        }

        return $cache[$key];
    }
}