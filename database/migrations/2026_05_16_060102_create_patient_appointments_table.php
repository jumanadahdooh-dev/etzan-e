<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('patient_profile_id')->nullable();
            $table->unsignedBigInteger('doctor_profile_id')->nullable();

            $table->date('appointment_date');
            $table->string('appointment_time', 20);
            $table->string('consultation_type')->default('online');

            $table->string('reason')->nullable();
            $table->text('notes')->nullable();

            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_appointments');
    }
};
