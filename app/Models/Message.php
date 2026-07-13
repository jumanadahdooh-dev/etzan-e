<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'sender_id',
        'from_user_id',
        'sender_type',
        'sender_role',
        'direction',
        'type',
        'guest_name',
        'guest_email',
        'from_name',
        'from_email',
        'message',
        'body',
        'content',
        'text',
        'metadata',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getBodyTextAttribute(): string
    {
        return $this->body
            ?? $this->message
            ?? $this->content
            ?? $this->text
            ?? '';
    }

    public function getMetadataArrayAttribute(): array
    {
        if (!$this->metadata) {
            return [];
        }

        if (is_array($this->metadata)) {
            return $this->metadata;
        }

        $decoded = json_decode($this->metadata, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getDisplaySenderNameAttribute(): string
    {
        if (!empty($this->guest_name)) {
            return $this->guest_name;
        }

        if (!empty($this->from_name)) {
            return $this->from_name;
        }

        if ($this->sender) {
            return $this->sender->name ?? 'مستخدم';
        }

        return 'زائر';
    }

    public function getIsAdminMessageAttribute(): bool
    {
        return in_array($this->sender_role, ['admin', 'support'], true)
            || $this->sender_type === 'admin'
            || $this->direction === 'outgoing';
    }
}
