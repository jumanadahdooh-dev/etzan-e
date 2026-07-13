<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('doctor_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('doctor_applications', 'experience_years')) {
                $table->unsignedInteger('experience_years')->after('specialty');
            }

            if (!Schema::hasColumn('doctor_applications', 'license_number')) {
                $table->string('license_number')->after('experience_years');
            }

            if (!Schema::hasColumn('doctor_applications', 'bio')) {
                $table->text('bio')->after('license_number');
            }

            if (!Schema::hasColumn('doctor_applications', 'profile_photo_path')) {
                $table->string('profile_photo_path')->after('bio');
            }

            if (!Schema::hasColumn('doctor_applications', 'license_file_path')) {
                $table->string('license_file_path')->after('profile_photo_path');
            }

            if (!Schema::hasColumn('doctor_applications', 'cv_file_path')) {
                $table->string('cv_file_path')->nullable()->after('license_file_path');
            }

            if (!Schema::hasColumn('doctor_applications', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('cv_file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_applications', function (Blueprint $table) {
            $table->dropColumn([
                'experience_years',
                'license_number',
                'bio',
                'profile_photo_path',
                'license_file_path',
                'cv_file_path',
                'status',
            ]);
        });
    }
};
