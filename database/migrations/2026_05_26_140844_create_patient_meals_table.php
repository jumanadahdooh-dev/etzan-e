<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_meals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('patient_profile_id')->nullable();

            $table->unsignedBigInteger('doctor_profile_id')->nullable();
            $table->unsignedBigInteger('doctor_user_id')->nullable();

            $table->date('meal_date');
            $table->string('meal_type', 30)->default('lunch');

            $table->string('meal_name')->nullable();
            $table->text('description')->nullable();

            $table->string('image_path')->nullable();

            $table->unsignedInteger('calories')->default(0);
            $table->unsignedInteger('protein')->default(0);
            $table->unsignedInteger('carbs')->default(0);
            $table->unsignedInteger('fat')->default(0);

            $table->unsignedTinyInteger('confidence')->default(0);

            $table->text('ai_notes')->nullable();
            $table->text('patient_note')->nullable();

            $table->json('ai_response')->nullable();

            $table->string('source', 30)->default('ai');
            $table->string('status', 30)->default('confirmed');

            $table->timestamps();

            $table->index(['user_id', 'meal_date']);
            $table->index(['patient_profile_id', 'meal_date']);
            $table->index(['doctor_profile_id', 'meal_date']);
            $table->index(['doctor_user_id', 'meal_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_meals');
    }
};
