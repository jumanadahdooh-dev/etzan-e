<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة إمكانية مراجعة حقيقية من الطبيب لكل وجبة سجّلها المريض
     * (صفحة "مراجعة الوجبات AI" كانت لحد هلق بيانات وهمية بالكامل).
     */
    public function up(): void
    {
        Schema::table('patient_meals', function (Blueprint $table) {
            if (!Schema::hasColumn('patient_meals', 'doctor_note')) {
                $table->text('doctor_note')->nullable()->after('ai_notes');
            }

            if (!Schema::hasColumn('patient_meals', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('doctor_note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_meals', function (Blueprint $table) {
            if (Schema::hasColumn('patient_meals', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }

            if (Schema::hasColumn('patient_meals', 'doctor_note')) {
                $table->dropColumn('doctor_note');
            }
        });
    }
};
