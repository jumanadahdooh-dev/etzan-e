<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_condition', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('condition_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['patient_profile_id', 'condition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_condition');
    }
};
