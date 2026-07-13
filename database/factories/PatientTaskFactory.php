<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PatientTask>
 */
class PatientTaskFactory extends Factory
{
    public function definition(): array
    {
        // ملاحظة: عمود patient_id قديم من أول تصميم للجدول وما عاد الكود
        // الحقيقي يستخدمه (صار يعتمد على patient_user_id بدله)، بس ضل
        // NOT NULL بالجدول فلازم نعبيه. هاد من مخلفات الـ migrations
        // المتضاربة المذكورة بتقرير التدقيق — سيتم تنظيفه بمرحلة قاعدة البيانات.
        $patientId = User::factory()->patient()->create()->id;

        return [
            'patient_id' => $patientId,
            'patient_user_id' => $patientId,
            'created_by_type' => 'patient',
            'source' => 'patient',
            'title' => fake()->sentence(5),
            'description' => fake()->sentence(12),
            'task_date' => fake()->dateTimeBetween('now', '+1 week')->format('Y-m-d'),
            'status' => 'pending',
            'repeat_type' => 'none',
            'reminder_minutes' => 15,
            'requires_attachment' => false,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function fromDoctor(): static
    {
        return $this->state(fn (array $attributes) => [
            'doctor_user_id' => User::factory()->doctor(),
            'created_by_type' => 'doctor',
            'source' => 'doctor',
        ]);
    }
}
