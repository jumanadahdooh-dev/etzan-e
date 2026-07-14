<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: بنستخدم نفس أوامر MODIFY الأصلية (زي ما كانت بالإنتاج تماماً).
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
        } else {
            // غير MySQL (زي SQLite بالاختبارات): نفس الأثر عن طريق Schema Builder القياسي.
            if (Schema::hasColumn('messages', 'sender_type')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->string('sender_type', 30)->nullable()->change();
                });
            }

            if (Schema::hasColumn('messages', 'sender_id')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->unsignedBigInteger('sender_id')->nullable()->change();
                });
            }

            if (Schema::hasColumn('messages', 'user_id')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                });
            }

            if (Schema::hasColumn('messages', 'from_user_id')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->unsignedBigInteger('from_user_id')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        //
    }
};
