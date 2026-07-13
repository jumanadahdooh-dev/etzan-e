<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_meals', function (Blueprint $table) {
            if (! Schema::hasColumn('patient_meals', 'confidence')) {
                $table->unsignedTinyInteger('confidence')->default(0)->after('fat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_meals', function (Blueprint $table) {
            if (Schema::hasColumn('patient_meals', 'confidence')) {
                $table->dropColumn('confidence');
            }
        });
    }
};
