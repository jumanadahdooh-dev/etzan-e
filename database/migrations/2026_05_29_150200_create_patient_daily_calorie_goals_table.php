<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_daily_calorie_goals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('patient_profile_id')->nullable();

            $table->unsignedBigInteger('doctor_profile_id')->nullable();
            $table->unsignedBigInteger('doctor_user_id')->nullable();

            $table->date('goal_date');

            $table->unsignedInteger('calories_goal')->nullable();
            $table->unsignedInteger('protein_goal')->nullable();
            $table->unsignedInteger('carbs_goal')->nullable();
            $table->unsignedInteger('fat_goal')->nullable();

            $table->string('status', 30)->default('approved');
            $table->text('doctor_note')->nullable();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'goal_date']);
            $table->index(['patient_profile_id', 'goal_date']);
            $table->index(['doctor_profile_id', 'goal_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_daily_calorie_goals');
    }
};
