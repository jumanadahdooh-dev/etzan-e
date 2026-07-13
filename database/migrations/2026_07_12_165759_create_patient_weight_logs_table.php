<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_weight_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_profile_id')->nullable()->constrained('patient_profiles')->nullOnDelete();
            $table->foreignId('doctor_profile_id')->nullable()->constrained('doctor_profiles')->nullOnDelete();
            $table->decimal('weight_kg', 5, 2);
            $table->date('logged_date');
            $table->string('source', 20)->default('doctor'); // doctor | patient
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'logged_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_weight_logs');
    }
};