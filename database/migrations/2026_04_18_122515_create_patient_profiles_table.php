<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('city')->nullable();

            $table->decimal('height', 5, 2)->nullable(); // cm
            $table->decimal('weight', 5, 2)->nullable(); // kg

            $table->text('notes')->nullable();
            $table->boolean('profile_completed')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_profiles');
    }
};
