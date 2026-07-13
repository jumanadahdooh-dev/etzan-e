<?php

namespace Database\Seeders;

use App\Models\AiChatConversation;
use App\Models\Article;
use App\Models\DoctorProfile;
use App\Models\PatientAppointment;
use App\Models\PatientProfile;
use App\Models\PatientTask;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * تحذير: هاد السيدر بينشئ بيانات تجريبية مترابطة (أطباء، مرضى، مواعيد،
     * مقالات...). لا تشغّله على قاعدة بيانات فيها بيانات حقيقية — استخدمه
     * بس على بيئة تطوير محلية فاضية أو نسخة قاعدة بيانات مؤقتة للاختبار.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        if (! $this->command?->confirm('إنشاء بيانات تجريبية إضافية (أطباء، مرضى، مواعيد، مقالات)؟', false)) {
            return;
        }

        $doctors = DoctorProfile::factory(5)->create();

        $patients = PatientProfile::factory(15)->create();

        foreach ($patients as $index => $patient) {
            $doctor = $doctors[$index % $doctors->count()];

            PatientAppointment::factory()
                ->for($patient->user, 'patient')
                ->create([
                    'doctor_profile_id' => $doctor->id,
                    'patient_profile_id' => $patient->id,
                ]);

            PatientTask::factory()->create([
                'patient_id' => $patient->user_id,
                'patient_user_id' => $patient->user_id,
            ]);

            AiChatConversation::factory()->create([
                'user_id' => $patient->user_id,
            ]);
        }

        Article::factory(10)->create();
    }
}
