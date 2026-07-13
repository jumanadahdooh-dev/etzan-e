<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patient_tasks')) {
            Schema::create('patient_tasks', function (Blueprint $table) {
                $table->id();

                $table->foreignId('patient_user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->foreignId('doctor_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('created_by_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('created_by_type')->default('patient'); // patient / doctor
                $table->string('source')->default('patient'); // patient / doctor

                $table->string('title');
                $table->text('description')->nullable();

                $table->date('task_date')->index();
                $table->time('task_time')->nullable();

                $table->string('status')->default('pending')->index(); // pending / completed / missed / cancelled
                $table->timestamp('completed_at')->nullable();

                $table->string('repeat_type')->default('once'); // once / daily / weekly
                $table->unsignedInteger('reminder_minutes')->default(15);
                $table->timestamp('reminder_sent_at')->nullable();

                $table->boolean('requires_attachment')->default(false);
                $table->string('attachment_path')->nullable();
                $table->text('patient_note')->nullable();

                $table->timestamps();
            });

            return;
        }

        Schema::table('patient_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('patient_tasks', 'patient_user_id')) {
                $table->unsignedBigInteger('patient_user_id')->nullable()->after('id')->index();
            }

            if (! Schema::hasColumn('patient_tasks', 'doctor_user_id')) {
                $table->unsignedBigInteger('doctor_user_id')->nullable()->after('patient_user_id')->index();
            }

            if (! Schema::hasColumn('patient_tasks', 'created_by_id')) {
                $table->unsignedBigInteger('created_by_id')->nullable()->after('doctor_user_id')->index();
            }

            if (! Schema::hasColumn('patient_tasks', 'created_by_type')) {
                $table->string('created_by_type')->default('patient')->after('created_by_id');
            }

            if (! Schema::hasColumn('patient_tasks', 'source')) {
                $table->string('source')->default('patient')->after('created_by_type');
            }

            if (! Schema::hasColumn('patient_tasks', 'title')) {
                $table->string('title')->after('source');
            }

            if (! Schema::hasColumn('patient_tasks', 'description')) {
                $table->text('description')->nullable()->after('title');
            }

            if (! Schema::hasColumn('patient_tasks', 'task_date')) {
                $table->date('task_date')->nullable()->after('description')->index();
            }

            if (! Schema::hasColumn('patient_tasks', 'task_time')) {
                $table->time('task_time')->nullable()->after('task_date');
            }

            if (! Schema::hasColumn('patient_tasks', 'status')) {
                $table->string('status')->default('pending')->after('task_time')->index();
            }

            if (! Schema::hasColumn('patient_tasks', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('patient_tasks', 'repeat_type')) {
                $table->string('repeat_type')->default('once')->after('completed_at');
            }

            if (! Schema::hasColumn('patient_tasks', 'reminder_minutes')) {
                $table->unsignedInteger('reminder_minutes')->default(15)->after('repeat_type');
            }

            if (! Schema::hasColumn('patient_tasks', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('reminder_minutes');
            }

            if (! Schema::hasColumn('patient_tasks', 'requires_attachment')) {
                $table->boolean('requires_attachment')->default(false)->after('reminder_sent_at');
            }

            if (! Schema::hasColumn('patient_tasks', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('requires_attachment');
            }

            if (! Schema::hasColumn('patient_tasks', 'patient_note')) {
                $table->text('patient_note')->nullable()->after('attachment_path');
            }
        });
    }

    public function down(): void
    {
        // لا نحذف الجدول حتى لا تضيع بيانات المهام.
    }
};
