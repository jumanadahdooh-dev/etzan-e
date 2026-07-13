<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('doctor_reviews')) {
            Schema::create('doctor_reviews', function (Blueprint $table) {
                $table->id();

                $table->foreignId('doctor_profile_id')
                    ->constrained('doctor_profiles')
                    ->cascadeOnDelete();

                $table->foreignId('patient_user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->string('status', 30)->default('published');

                $table->timestamps();

                $table->unique(['doctor_profile_id', 'patient_user_id']);
                $table->index(['doctor_profile_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_reviews');
    }
};
