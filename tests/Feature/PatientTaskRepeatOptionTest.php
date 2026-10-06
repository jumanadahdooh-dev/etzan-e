<?php

namespace Tests\Feature;

use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * خيار التكرار كان ينحفظ بس ما في شي يستخدمه، فانشال من الفورم وكل مهمة تنحفظ لمرة واحدة.
 */
class PatientTaskRepeatOptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_journey_form_has_no_repeat_option_and_tasks_are_saved_as_once(): void
    {
        $user = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('patient.journey'))
            ->assertOk()
            ->assertDontSee('name="repeat_type"', false);

        $this->actingAs($user)->post(route('patient.journey.tasks.store'), [
            'title' => 'مشي 20 دقيقة',
            'task_date' => now()->toDateString(),
            'reminder_minutes' => 0,
            'repeat_type' => 'daily',
        ])->assertRedirect();

        $this->assertDatabaseHas('patient_tasks', [
            'patient_user_id' => $user->id,
            'title' => 'مشي 20 دقيقة',
            'repeat_type' => 'once',
        ]);
    }
}
