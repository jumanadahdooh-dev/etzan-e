<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('doctor_patient_notes')) {
            Schema::create('doctor_patient_notes', function (Blueprint $table) {
                $table->id();

                $table->foreignId('doctor_profile_id')
                    ->constrained('doctor_profiles')
                    ->cascadeOnDelete();

                $table->foreignId('patient_user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->text('note');

                $table->string('type', 50)->default('followup');
                $table->boolean('visible_to_patient')->default(true);

                $table->timestamps();

                $table->index(['doctor_profile_id', 'patient_user_id']);
                $table->index(['visible_to_patient', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_patient_notes');
    }
};
