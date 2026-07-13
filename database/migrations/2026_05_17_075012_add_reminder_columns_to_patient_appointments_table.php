<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | تنبيه قبل الموعد بساعة
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasColumn('patient_appointments', 'reminder_60_sent_at')) {
            Schema::table('patient_appointments', function (Blueprint $table) {
                $afterColumn = Schema::hasColumn('patient_appointments', 'completed_at')
                    ? 'completed_at'
                    : (
                        Schema::hasColumn('patient_appointments', 'updated_at')
                            ? 'updated_at'
                            : 'status'
                    );

                $table->timestamp('reminder_60_sent_at')
                    ->nullable()
                    ->after($afterColumn);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | تنبيه قبل الموعد بـ 10 دقائق
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasColumn('patient_appointments', 'reminder_10_sent_at')) {
            Schema::table('patient_appointments', function (Blueprint $table) {
                $afterColumn = Schema::hasColumn('patient_appointments', 'reminder_60_sent_at')
                    ? 'reminder_60_sent_at'
                    : (
                        Schema::hasColumn('patient_appointments', 'completed_at')
                            ? 'completed_at'
                            : (
                                Schema::hasColumn('patient_appointments', 'updated_at')
                                    ? 'updated_at'
                                    : 'status'
                            )
                    );

                $table->timestamp('reminder_10_sent_at')
                    ->nullable()
                    ->after($afterColumn);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | تنبيه وقت الموعد بالضبط
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasColumn('patient_appointments', 'reminder_now_sent_at')) {
            Schema::table('patient_appointments', function (Blueprint $table) {
                $afterColumn = Schema::hasColumn('patient_appointments', 'reminder_10_sent_at')
                    ? 'reminder_10_sent_at'
                    : (
                        Schema::hasColumn('patient_appointments', 'reminder_60_sent_at')
                            ? 'reminder_60_sent_at'
                            : (
                                Schema::hasColumn('patient_appointments', 'completed_at')
                                    ? 'completed_at'
                                    : (
                                        Schema::hasColumn('patient_appointments', 'updated_at')
                                            ? 'updated_at'
                                            : 'status'
                                    )
                            )
                    );

                $table->timestamp('reminder_now_sent_at')
                    ->nullable()
                    ->after($afterColumn);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('patient_appointments', 'reminder_now_sent_at')) {
            Schema::table('patient_appointments', function (Blueprint $table) {
                $table->dropColumn('reminder_now_sent_at');
            });
        }

        if (Schema::hasColumn('patient_appointments', 'reminder_10_sent_at')) {
            Schema::table('patient_appointments', function (Blueprint $table) {
                $table->dropColumn('reminder_10_sent_at');
            });
        }

        if (Schema::hasColumn('patient_appointments', 'reminder_60_sent_at')) {
            Schema::table('patient_appointments', function (Blueprint $table) {
                $table->dropColumn('reminder_60_sent_at');
            });
        }
    }
};
