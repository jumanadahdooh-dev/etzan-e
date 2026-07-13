<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_notifications')) {
            Schema::create('admin_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('type')->default('general');
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('url')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        if (!Schema::hasColumn('admin_notifications', 'type')) {
            Schema::table('admin_notifications', function (Blueprint $table) {
                $table->string('type')->default('general')->after('id');
            });
        }

        if (!Schema::hasColumn('admin_notifications', 'title')) {
            Schema::table('admin_notifications', function (Blueprint $table) {
                $table->string('title')->default('إشعار جديد')->after('type');
            });
        }

        if (!Schema::hasColumn('admin_notifications', 'body')) {
            Schema::table('admin_notifications', function (Blueprint $table) {
                $table->text('body')->nullable()->after('title');
            });
        }

        if (!Schema::hasColumn('admin_notifications', 'url')) {
            Schema::table('admin_notifications', function (Blueprint $table) {
                $table->string('url')->nullable()->after('body');
            });
        }

        if (!Schema::hasColumn('admin_notifications', 'read_at')) {
            Schema::table('admin_notifications', function (Blueprint $table) {
                $table->timestamp('read_at')->nullable()->after('url');
            });
        }

        if (!Schema::hasColumn('admin_notifications', 'created_at')) {
            Schema::table('admin_notifications', function (Blueprint $table) {
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasColumn('admin_notifications', 'updated_at')) {
            Schema::table('admin_notifications', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // لا تحذفي الجدول عشان ما نخسر الإشعارات
    }
};
