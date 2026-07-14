<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'guest_name')) {
                $table->string('guest_name')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('conversations', 'guest_email')) {
                $table->string('guest_email')->nullable()->after('guest_name');
            }

            if (!Schema::hasColumn('conversations', 'subject')) {
                $table->string('subject')->nullable()->after('guest_email');
            }

            if (!Schema::hasColumn('conversations', 'source')) {
                $table->string('source')->default('site')->after('subject');
            }
        });

        // MySQL: بنستخدم نفس أوامر MODIFY الأصلية (زي ما كانت بالإنتاج تماماً).
        if (DB::connection()->getDriverName() === 'mysql') {
            if (Schema::hasColumn('conversations', 'user_id')) {
                DB::statement('ALTER TABLE conversations MODIFY user_id BIGINT UNSIGNED NULL');
            }

            if (Schema::hasColumn('messages', 'user_id')) {
                DB::statement('ALTER TABLE messages MODIFY user_id BIGINT UNSIGNED NULL');
            }

            if (Schema::hasColumn('messages', 'sender_id')) {
                DB::statement('ALTER TABLE messages MODIFY sender_id BIGINT UNSIGNED NULL');
            }

            if (Schema::hasColumn('messages', 'from_user_id')) {
                DB::statement('ALTER TABLE messages MODIFY from_user_id BIGINT UNSIGNED NULL');
            }
        } else {
            // غير MySQL (زي SQLite بالاختبارات): نفس الأثر (تصيير الأعمدة nullable)
            // بس عن طريق Schema Builder القياسي بدل SQL خاص بـ MySQL.
            if (Schema::hasColumn('conversations', 'user_id')) {
                Schema::table('conversations', function (Blueprint $table) {
                    $table->foreignId('user_id')->nullable()->change();
                });
            }

            if (Schema::hasColumn('messages', 'user_id')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                });
            }

            if (Schema::hasColumn('messages', 'sender_id')) {
                Schema::table('messages', function (Blueprint $table) {
                    $table->unsignedBigInteger('sender_id')->nullable()->change();
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
