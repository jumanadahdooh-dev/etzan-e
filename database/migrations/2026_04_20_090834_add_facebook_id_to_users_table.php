<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up(): void
        {
            Schema::table('users', function ($table) {
                $table->string('facebook_id')->nullable()->after('google_id');
            });
        }

    /**
     * Reverse the migrations.
     */
            public function down(): void
        {
            Schema::table('users', function ($table) {
                $table->dropColumn(['facebook_id']);
            });
        }
};
