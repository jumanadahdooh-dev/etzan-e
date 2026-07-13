<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');           // doctor_application, message, system
            $table->string('title');
            $table->string('body')->nullable();
            $table->string('icon')->default('fa-bell');
            $table->string('url')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('related_type')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
