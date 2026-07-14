<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تصليح تناقض بالتصميم: doctor_reviews و patient_doctor_requests و
 * patient_weight_logs كانت مربوطة فعلياً بمفاتيح خارجية حقيقية
 * (foreign key)، بس patient_appointments و patient_meals و
 * patient_daily_calorie_goals كانت أعمدتها (patient_profile_id،
 * doctor_profile_id، doctor_user_id) مجرد أرقام عادية بدون أي ربط
 * حقيقي بقاعدة البيانات — يعني نظرياً ممكن يصير فيها بيانات "يتيمة"
 * (تشاور على مريض أو دكتور محذوف) بدون ما تنكشف.
 *
 * قبل كتابة هاي الهجرة اتفحصت قاعدة البيانات الحقيقية بالكامل (قراءة فقط)
 * وتأكدنا إنه صفر صفوف يتيمة موجودة حالياً بأي من هاي الأعمدة، فإضافة
 * الربط الحقيقي هون آمنة 100% ولن تفشل بسبب بيانات قديمة غير متطابقة.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('patient_appointments')) {
            Schema::table('patient_appointments', function (Blueprint $table) {
                if (Schema::hasColumn('patient_appointments', 'patient_profile_id')) {
                    $table->foreign('patient_profile_id')
                        ->references('id')->on('patient_profiles')
                        ->nullOnDelete();
                }

                if (Schema::hasColumn('patient_appointments', 'doctor_profile_id')) {
                    $table->foreign('doctor_profile_id')
                        ->references('id')->on('doctor_profiles')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('patient_meals')) {
            Schema::table('patient_meals', function (Blueprint $table) {
                if (Schema::hasColumn('patient_meals', 'patient_profile_id')) {
                    $table->foreign('patient_profile_id')
                        ->references('id')->on('patient_profiles')
                        ->nullOnDelete();
                }

                if (Schema::hasColumn('patient_meals', 'doctor_profile_id')) {
                    $table->foreign('doctor_profile_id')
                        ->references('id')->on('doctor_profiles')
                        ->nullOnDelete();
                }

                if (Schema::hasColumn('patient_meals', 'doctor_user_id')) {
                    $table->foreign('doctor_user_id')
                        ->references('id')->on('users')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('patient_daily_calorie_goals')) {
            Schema::table('patient_daily_calorie_goals', function (Blueprint $table) {
                if (Schema::hasColumn('patient_daily_calorie_goals', 'patient_profile_id')) {
                    $table->foreign('patient_profile_id')
                        ->references('id')->on('patient_profiles')
                        ->nullOnDelete();
                }

                if (Schema::hasColumn('patient_daily_calorie_goals', 'doctor_profile_id')) {
                    $table->foreign('doctor_profile_id')
                        ->references('id')->on('doctor_profiles')
                        ->nullOnDelete();
                }

                if (Schema::hasColumn('patient_daily_calorie_goals', 'doctor_user_id')) {
                    $table->foreign('doctor_user_id')
                        ->references('id')->on('users')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('patient_appointments')) {
            Schema::table('patient_appointments', function (Blueprint $table) {
                $table->dropForeign(['patient_profile_id']);
                $table->dropForeign(['doctor_profile_id']);
            });
        }

        if (Schema::hasTable('patient_meals')) {
            Schema::table('patient_meals', function (Blueprint $table) {
                $table->dropForeign(['patient_profile_id']);
                $table->dropForeign(['doctor_profile_id']);
                $table->dropForeign(['doctor_user_id']);
            });
        }

        if (Schema::hasTable('patient_daily_calorie_goals')) {
            Schema::table('patient_daily_calorie_goals', function (Blueprint $table) {
                $table->dropForeign(['patient_profile_id']);
                $table->dropForeign(['doctor_profile_id']);
                $table->dropForeign(['doctor_user_id']);
            });
        }
    }
};
