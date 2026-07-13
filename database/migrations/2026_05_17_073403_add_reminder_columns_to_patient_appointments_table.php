<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('patient_appointments', 'reminder_60_sent_at')) {
                $table->timestamp('reminder_60_sent_at')->nullable()->after('completed_at');
            }

            if (!Schema::hasColumn('patient_appointments', 'reminder_10_sent_at')) {
                $table->timestamp('reminder_10_sent_at')->nullable()->after('reminder_60_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('patient_appointments', 'reminder_60_sent_at')) {
                $table->dropColumn('reminder_60_sent_at');
            }

            if (Schema::hasColumn('patient_appointments', 'reminder_10_sent_at')) {
                $table->dropColumn('reminder_10_sent_at');
            }
        });
    }
};
