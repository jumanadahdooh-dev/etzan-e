<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_notifications')) {
            Schema::create('app_notifications', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('recipient_user_id')->nullable()->index();
                $table->string('recipient_role')->nullable()->index();

                $table->unsignedBigInteger('actor_user_id')->nullable()->index();

                $table->string('type')->default('general')->index();
                $table->string('title');
                $table->text('body')->nullable();

                $table->string('url')->nullable();

                $table->unsignedBigInteger('related_id')->nullable()->index();
                $table->string('related_type')->nullable()->index();

                $table->json('data')->nullable();

                $table->boolean('is_read')->default(false)->index();
                $table->timestamp('read_at')->nullable();

                $table->timestamps();
            });

            return;
        }

        Schema::table('app_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('app_notifications', 'recipient_user_id')) {
                $table->unsignedBigInteger('recipient_user_id')->nullable()->index();
            }

            if (! Schema::hasColumn('app_notifications', 'recipient_role')) {
                $table->string('recipient_role')->nullable()->index();
            }

            if (! Schema::hasColumn('app_notifications', 'actor_user_id')) {
                $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            }

            if (! Schema::hasColumn('app_notifications', 'type')) {
                $table->string('type')->default('general')->index();
            }

            if (! Schema::hasColumn('app_notifications', 'title')) {
                $table->string('title')->default('إشعار جديد');
            }

            if (! Schema::hasColumn('app_notifications', 'body')) {
                $table->text('body')->nullable();
            }

            if (! Schema::hasColumn('app_notifications', 'url')) {
                $table->string('url')->nullable();
            }

            if (! Schema::hasColumn('app_notifications', 'related_id')) {
                $table->unsignedBigInteger('related_id')->nullable()->index();
            }

            if (! Schema::hasColumn('app_notifications', 'related_type')) {
                $table->string('related_type')->nullable()->index();
            }

            if (! Schema::hasColumn('app_notifications', 'data')) {
                $table->json('data')->nullable();
            }

            if (! Schema::hasColumn('app_notifications', 'is_read')) {
                $table->boolean('is_read')->default(false)->index();
            }

            if (! Schema::hasColumn('app_notifications', 'read_at')) {
                $table->timestamp('read_at')->nullable();
            }

            if (! Schema::hasColumn('app_notifications', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('app_notifications', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // لا نحذف الجدول حتى لا نخسر الإشعارات الموجودة.
    }
};