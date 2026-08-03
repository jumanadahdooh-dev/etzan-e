<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * دوال فحص جداول/أعمدة الرسائل (conversations/messages) بشكل ديناميكي،
 * مشتركة بين كونترولرات المريض والدكتور عشان ما نكررها بالمكانين.
 * منقولة من PatientContextHelpers (كانت خاصة فيها بس صار في حاجة لها
 * بجهة الدكتور كمان لبناء صفحة الرسائل الحقيقية).
 */
trait ConversationHelpers
{
    /**
     * القيمة الوحيدة يلي بتربط محادثة بـ"مراسلة الطبيب" حالياً (عمود subject)،
     * لأنه ما في عمود type/doctor_user_id فعلي بجدول conversations لحد الآن.
     * نفس القيمة المستخدمة أصلاً بـ PatientContextHelpers::ensureConversation().
     */
    public const DOCTOR_CONVERSATION_SUBJECT = 'رسائل الطبيب';

    /**
     * يقيّد أي query (Eloquent أو Query Builder) على محادثات الطبيب فقط،
     * بشكل ديناميكي حسب الأعمدة الموجودة فعلياً بالجدول.
     */
    private function scopeDoctorConversations(mixed $query): mixed
    {
        if ($this->columnExists('conversations', 'type')) {
            return $query->where('type', 'doctor');
        }

        if ($this->columnExists('conversations', 'subject')) {
            return $query->where('subject', self::DOCTOR_CONVERSATION_SUBJECT);
        }

        return $query;
    }

    private function tableExists(string $table): bool
    {
        static $cache = [];

        if (!array_key_exists($table, $cache)) {
            $cache[$table] = Schema::hasTable($table);
        }

        return $cache[$table];
    }

    private function columnExists(string $table, string $column): bool
    {
        static $cache = [];

        $key = $table . '.' . $column;

        if (!array_key_exists($key, $cache)) {
            $cache[$key] = Schema::hasTable($table) && Schema::hasColumn($table, $column);
        }

        return $cache[$key];
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->columnExists($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function conversationStatusValue(): ?string
    {
        if (! Schema::hasTable('conversations') || ! Schema::hasColumn('conversations', 'status')) {
            return null;
        }

        try {
            $column = DB::selectOne("SHOW COLUMNS FROM conversations WHERE Field = 'status'");

            $type = $column->Type ?? '';

            if (str_contains($type, "enum")) {
                preg_match_all("/'([^']+)'/", $type, $matches);

                $allowed = $matches[1] ?? [];

                foreach (['open', 'active', 'pending', 'new'] as $status) {
                    if (in_array($status, $allowed, true)) {
                        return $status;
                    }
                }

                return $allowed[0] ?? null;
            }

            return 'open';
        } catch (\Throwable $e) {
            return 'open';
        }
    }
}
