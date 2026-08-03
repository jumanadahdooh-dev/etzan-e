<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * دعم تحليل الوجبات الموسّع (Meal Analysis redesign): فايبر/سكر/صوديوم تنضاف
     * كأعمدة مستقلة عشان تنجمع بمجاميع اليوم زي calories/protein/carbs/fat،
     * و health_score لعرض سريع بدون فك ai_response. باقي التفاصيل (المكونات،
     * الفيتامينات، توافق الأنظمة الغذائية، التوصيات) تبقى داخل ai_response.
     */
    public function up(): void
    {
        Schema::table('patient_meals', function (Blueprint $table) {
            if (!Schema::hasColumn('patient_meals', 'fiber')) {
                $table->unsignedInteger('fiber')->default(0)->after('fat');
            }

            if (!Schema::hasColumn('patient_meals', 'sugar')) {
                $table->unsignedInteger('sugar')->default(0)->after('fiber');
            }

            if (!Schema::hasColumn('patient_meals', 'sodium')) {
                $table->unsignedInteger('sodium')->default(0)->after('sugar');
            }

            if (!Schema::hasColumn('patient_meals', 'health_score')) {
                $table->unsignedTinyInteger('health_score')->nullable()->after('confidence');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patient_meals', function (Blueprint $table) {
            if (Schema::hasColumn('patient_meals', 'health_score')) {
                $table->dropColumn('health_score');
            }

            if (Schema::hasColumn('patient_meals', 'sodium')) {
                $table->dropColumn('sodium');
            }

            if (Schema::hasColumn('patient_meals', 'sugar')) {
                $table->dropColumn('sugar');
            }

            if (Schema::hasColumn('patient_meals', 'fiber')) {
                $table->dropColumn('fiber');
            }
        });
    }
};
