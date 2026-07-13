<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL فقط: SQLite (المستخدمة بالاختبارات) ما بتدعم صيغة MODIFY هاي.
        if (DB::connection()->getDriverName() === 'mysql') {
            if (Schema::hasColumn('messages', 'sender_type')) {
                DB::statement("ALTER TABLE messages MODIFY sender_type VARCHAR(30) NULL");
            }

            if (Schema::hasColumn('messages', 'sender_id')) {
                DB::statement("ALTER TABLE messages MODIFY sender_id BIGINT UNSIGNED NULL");
            }

            if (Schema::hasColumn('messages', 'user_id')) {
                DB::statement("ALTER TABLE messages MODIFY user_id BIGINT UNSIGNED NULL");
            }

            if (Schema::hasColumn('messages', 'from_user_id')) {
                DB::statement("ALTER TABLE messages MODIFY from_user_id BIGINT UNSIGNED NULL");
            }
        }
    }

    public function down(): void
    {
        //
    }
};
