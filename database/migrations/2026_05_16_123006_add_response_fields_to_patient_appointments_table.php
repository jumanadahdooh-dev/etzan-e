<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('patient_appointments', 'doctor_response_message')) {
                $table->text('doctor_response_message')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('patient_appointments', 'suggested_date')) {
                $table->date('suggested_date')->nullable()->after('doctor_response_message');
            }

            if (!Schema::hasColumn('patient_appointments', 'suggested_time')) {
                $table->string('suggested_time', 20)->nullable()->after('suggested_date');
            }

            if (!Schema::hasColumn('patient_appointments', 'patient_response_status')) {
                $table->string('patient_response_status')->nullable()->after('suggested_time');
            }

            if (!Schema::hasColumn('patient_appointments', 'patient_response_message')) {
                $table->text('patient_response_message')->nullable()->after('patient_response_status');
            }

            if (!Schema::hasColumn('patient_appointments', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('patient_appointments', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('confirmed_at');
            }

            if (!Schema::hasColumn('patient_appointments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('rejected_at');
            }

            if (!Schema::hasColumn('patient_appointments', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('cancelled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_appointments', function (Blueprint $table) {
            $columns = [
                'doctor_response_message',
                'suggested_date',
                'suggested_time',
                'patient_response_status',
                'patient_response_message',
                'confirmed_at',
                'rejected_at',
                'cancelled_at',
                'completed_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('patient_appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
