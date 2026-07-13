<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_specialty', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['doctor_profile_id', 'specialty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_specialty');
    }
};
