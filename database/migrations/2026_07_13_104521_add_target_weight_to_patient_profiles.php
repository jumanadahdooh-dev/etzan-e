<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('patient_profiles', 'target_weight_kg')) {
                $table->decimal('target_weight_kg', 5, 2)->nullable()->after('weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->dropColumn('target_weight_kg');
        });
    }
};
