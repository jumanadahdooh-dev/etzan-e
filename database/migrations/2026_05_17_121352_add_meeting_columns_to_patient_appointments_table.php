<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('patient_appointments', 'meeting_platform')) {
                $table->string('meeting_platform')->nullable()->after('consultation_type');
            }

            if (!Schema::hasColumn('patient_appointments', 'meeting_url')) {
                $table->text('meeting_url')->nullable()->after('meeting_platform');
            }

            if (!Schema::hasColumn('patient_appointments', 'meeting_notes')) {
                $table->text('meeting_notes')->nullable()->after('meeting_url');
            }

            if (!Schema::hasColumn('patient_appointments', 'meeting_added_at')) {
                $table->timestamp('meeting_added_at')->nullable()->after('meeting_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_appointments', function (Blueprint $table) {
            if (Schema::hasColumn('patient_appointments', 'meeting_added_at')) {
                $table->dropColumn('meeting_added_at');
            }

            if (Schema::hasColumn('patient_appointments', 'meeting_notes')) {
                $table->dropColumn('meeting_notes');
            }

            if (Schema::hasColumn('patient_appointments', 'meeting_url')) {
                $table->dropColumn('meeting_url');
            }

            if (Schema::hasColumn('patient_appointments', 'meeting_platform')) {
                $table->dropColumn('meeting_platform');
            }
        });
    }
};
