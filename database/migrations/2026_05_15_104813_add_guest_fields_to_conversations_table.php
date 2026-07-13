<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'source')) {
                $table->dropColumn('source');
            }

            if (Schema::hasColumn('conversations', 'subject')) {
                $table->dropColumn('subject');
            }

            if (Schema::hasColumn('conversations', 'guest_email')) {
                $table->dropColumn('guest_email');
            }

            if (Schema::hasColumn('conversations', 'guest_name')) {
                $table->dropColumn('guest_name');
            }
        });
    }
};
