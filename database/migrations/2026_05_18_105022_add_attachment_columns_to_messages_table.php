<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'attachment_path')) {
                $table->string('attachment_path')->nullable();
            }

            if (!Schema::hasColumn('messages', 'attachment_name')) {
                $table->string('attachment_name')->nullable();
            }

            if (!Schema::hasColumn('messages', 'attachment_mime')) {
                $table->string('attachment_mime')->nullable();
            }

            if (!Schema::hasColumn('messages', 'attachment_type')) {
                $table->string('attachment_type')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            foreach (['attachment_path', 'attachment_name', 'attachment_mime', 'attachment_type'] as $column) {
                if (Schema::hasColumn('messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
