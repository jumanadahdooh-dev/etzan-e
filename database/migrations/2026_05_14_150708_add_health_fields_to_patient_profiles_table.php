<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('patient_profiles', 'health_goal')) {
                $table->string('health_goal')->nullable()->after('gender');
            }

            if (!Schema::hasColumn('patient_profiles', 'activity_level')) {
                $table->string('activity_level')->nullable()->after('health_goal');
            }

            if (!Schema::hasColumn('patient_profiles', 'medical_conditions')) {
                $table->json('medical_conditions')->nullable()->after('activity_level');
            }

            if (!Schema::hasColumn('patient_profiles', 'medications')) {
                $table->text('medications')->nullable()->after('medical_conditions');
            }

            if (!Schema::hasColumn('patient_profiles', 'allergies')) {
                $table->text('allergies')->nullable()->after('medications');
            }

            if (!Schema::hasColumn('patient_profiles', 'meals_per_day')) {
                $table->unsignedTinyInteger('meals_per_day')->nullable()->after('allergies');
            }

            if (!Schema::hasColumn('patient_profiles', 'sleep_hours')) {
                $table->decimal('sleep_hours', 4, 1)->nullable()->after('meals_per_day');
            }

            if (!Schema::hasColumn('patient_profiles', 'water_cups')) {
                $table->unsignedTinyInteger('water_cups')->nullable()->after('sleep_hours');
            }

            if (!Schema::hasColumn('patient_profiles', 'preferred_doctor_gender')) {
                $table->string('preferred_doctor_gender')->nullable()->default('any')->after('water_cups');
            }

            if (!Schema::hasColumn('patient_profiles', 'preferred_consultation_type')) {
                $table->string('preferred_consultation_type')->nullable()->default('any')->after('preferred_doctor_gender');
            }

            if (!Schema::hasColumn('patient_profiles', 'avatar')) {
                $table->string('avatar')->nullable()->after('preferred_consultation_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_profiles', function (Blueprint $table) {
            $columns = [
                'health_goal',
                'activity_level',
                'medical_conditions',
                'medications',
                'allergies',
                'meals_per_day',
                'sleep_hours',
                'water_cups',
                'preferred_doctor_gender',
                'preferred_consultation_type',
                'avatar',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('patient_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
