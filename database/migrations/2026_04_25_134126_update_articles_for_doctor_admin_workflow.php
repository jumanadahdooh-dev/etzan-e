<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (!Schema::hasColumn('articles', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('articles', 'specialty_id')) {
                $table->foreignId('specialty_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('specialties')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('articles', 'source')) {
                $table->string('source')->default('admin')->after('specialty_id');
            }

            if (!Schema::hasColumn('articles', 'approved_by')) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->after('is_featured')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('articles', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (!Schema::hasColumn('articles', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('approved_at');
            }

            if (!Schema::hasColumn('articles', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('submitted_at');
            }
        });

        // MySQL فقط: SQLite (المستخدمة بالاختبارات) ما بتدعم صيغة MODIFY هاي أصلاً،
        // وما في داعي لها لأنه العمود أصلاً string مش enum صارم على SQLite.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE articles MODIFY status ENUM('draft', 'pending_review', 'published', 'rejected') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (Schema::hasColumn('articles', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }

            if (Schema::hasColumn('articles', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }

            if (Schema::hasColumn('articles', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('articles', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }

            if (Schema::hasColumn('articles', 'source')) {
                $table->dropColumn('source');
            }

            if (Schema::hasColumn('articles', 'specialty_id')) {
                $table->dropConstrainedForeignId('specialty_id');
            }

            if (Schema::hasColumn('articles', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE articles MODIFY status ENUM('draft', 'published') NOT NULL DEFAULT 'draft'");
        }
    }
};
