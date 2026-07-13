<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('patient_profiles', 'suggested_calorie_goal')) {
                $table->unsignedInteger('suggested_calorie_goal')->nullable()->after('water_cups');
            }

            if (! Schema::hasColumn('patient_profiles', 'doctor_calorie_goal')) {
                $table->unsignedInteger('doctor_calorie_goal')->nullable()->after('suggested_calorie_goal');
            }

            if (! Schema::hasColumn('patient_profiles', 'calorie_goal_status')) {
                $table->string('calorie_goal_status', 30)->default('pending')->after('doctor_calorie_goal');
            }

            if (! Schema::hasColumn('patient_profiles', 'calorie_goal_note')) {
                $table->text('calorie_goal_note')->nullable()->after('calorie_goal_status');
            }

            if (! Schema::hasColumn('patient_profiles', 'calorie_goal_updated_at')) {
                $table->timestamp('calorie_goal_updated_at')->nullable()->after('calorie_goal_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_profiles', function (Blueprint $table) {
            foreach ([
                'suggested_calorie_goal',
                'doctor_calorie_goal',
                'calorie_goal_status',
                'calorie_goal_note',
                'calorie_goal_updated_at',
            ] as $column) {
                if (Schema::hasColumn('patient_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
