<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doctor_applications', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->string('workplace');
            $table->string('specialty');
            $table->unsignedInteger('experience_years');
            $table->string('license_number');
            $table->text('bio');
            $table->string('profile_photo_path');
            $table->string('license_file_path');
            $table->string('cv_file_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_applications');
    }
};
