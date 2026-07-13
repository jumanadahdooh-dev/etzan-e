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
    }

    public function down(): void
    {
        //
    }
};
