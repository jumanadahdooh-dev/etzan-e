<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patient_tasks')) {
            return;
        }

        Schema::table('patient_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('patient_tasks', 'due_notification_sent_at')) {
                $table->timestamp('due_notification_sent_at')->nullable()->after('reminder_sent_at');
            }

            if (! Schema::hasColumn('patient_tasks', 'late_notification_sent_at')) {
                $table->timestamp('late_notification_sent_at')->nullable()->after('due_notification_sent_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('patient_tasks')) {
            return;
        }

        Schema::table('patient_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('patient_tasks', 'late_notification_sent_at')) {
                $table->dropColumn('late_notification_sent_at');
            }

            if (Schema::hasColumn('patient_tasks', 'due_notification_sent_at')) {
                $table->dropColumn('due_notification_sent_at');
            }
        });
    }
};