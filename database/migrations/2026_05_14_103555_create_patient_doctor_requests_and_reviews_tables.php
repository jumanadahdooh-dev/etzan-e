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

                if (!Schema::hasColumn('patient_profiles', 'doctor_request_status')) {
                    $table->string('doctor_request_status')
                        ->nullable()
                        ->after('has_selected_doctor');
                }
            });
        }

        if (!Schema::hasTable('patient_doctor_requests')) {
            Schema::create('patient_doctor_requests', function (Blueprint $table) {
                $table->id();

                $table->foreignId('patient_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->foreignId('patient_profile_id')
                    ->nullable()
                    ->constrained('patient_profiles')
                    ->nullOnDelete();

                $table->foreignId('doctor_profile_id')
                    ->constrained('doctor_profiles')
                    ->cascadeOnDelete();

                $table->foreignId('doctor_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])
                    ->default('pending');

                $table->text('patient_message')->nullable();
                $table->text('doctor_response')->nullable();

                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('completed_at')->nullable();

                $table->timestamps();

                $table->index(['patient_id', 'status']);
                $table->index(['doctor_profile_id', 'status']);
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

                $table->foreignId('patient_doctor_request_id')
                    ->nullable()
                    ->constrained('patient_doctor_requests')
                    ->nullOnDelete();

                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->boolean('is_recommended')->default(true);

                $table->timestamps();

                $table->unique(['doctor_profile_id', 'patient_id']);
            });
        }

        if (!Schema::hasTable('app_notifications')) {
            Schema::create('app_notifications', function (Blueprint $table) {
                $table->id();

                $table->foreignId('recipient_user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->foreignId('actor_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('type')->nullable();
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('url')->nullable();
                $table->timestamp('read_at')->nullable();

                $table->timestamps();

                $table->index(['recipient_user_id', 'read_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('doctor_reviews');
        Schema::dropIfExists('patient_doctor_requests');

        if (Schema::hasTable('patient_profiles')) {
            Schema::table('patient_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('patient_profiles', 'doctor_request_status')) {
                    $table->dropColumn('doctor_request_status');
                }

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
