<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('patient_profiles')) {
            Schema::table('patient_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('patient_profiles', 'doctor_profile_id')) {
                    $table->foreignId('doctor_profile_id')
                        ->nullable()
                        ->after('user_id')
                        ->constrained('doctor_profiles')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn('patient_profiles', 'has_selected_doctor')) {
                    $table->boolean('has_selected_doctor')
                        ->default(false)
                        ->after('doctor_profile_id');
                }
            });
        }

        if (!Schema::hasTable('doctor_reviews')) {
            Schema::create('doctor_reviews', function (Blueprint $table) {
                $table->id();

                $table->foreignId('doctor_profile_id')
                    ->constrained('doctor_profiles')
                    ->cascadeOnDelete();

                $table->foreignId('patient_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->boolean('is_recommended')->default(true);

                $table->timestamps();

                $table->unique(['doctor_profile_id', 'patient_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('doctor_reviews')) {
            Schema::dropIfExists('doctor_reviews');
        }

        if (Schema::hasTable('patient_profiles')) {
            Schema::table('patient_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('patient_profiles', 'has_selected_doctor')) {
                    $table->dropColumn('has_selected_doctor');
                }

                if (Schema::hasColumn('patient_profiles', 'doctor_profile_id')) {
                    $table->dropConstrainedForeignId('doctor_profile_id');
                }
            });
        }
    }
};
