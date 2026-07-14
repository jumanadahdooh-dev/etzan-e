<?php

namespace Tests\Feature;

use App\Models\AiChatConversation;
use App\Models\PatientMeal;
use App\Models\PatientTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_cannot_view_another_patients_ai_conversation(): void
    {
        $owner = User::factory()->patient()->create();
        $intruder = User::factory()->patient()->create();
        $conversation = AiChatConversation::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($intruder)->get(route('patient.ai-chat.show', $conversation));

        $response->assertForbidden();
    }

    public function test_patient_can_view_their_own_ai_conversation(): void
    {
        $owner = User::factory()->patient()->create();
        $conversation = AiChatConversation::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('patient.ai-chat.show', $conversation));

        $response->assertOk();
    }

    public function test_patient_cannot_delete_another_patients_ai_conversation(): void
    {
        $owner = User::factory()->patient()->create();
        $intruder = User::factory()->patient()->create();
        $conversation = AiChatConversation::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($intruder)->delete(route('patient.ai-chat.destroy', $conversation));

        $response->assertForbidden();
        $this->assertDatabaseHas('ai_chat_conversations', ['id' => $conversation->id]);
    }

    public function test_patient_cannot_complete_another_patients_task(): void
    {
        $owner = User::factory()->patient()->create();
        $intruder = User::factory()->patient()->create();
        $task = PatientTask::factory()->create(['patient_user_id' => $owner->id, 'patient_id' => $owner->id]);

        $response = $this->actingAs($intruder)->patch(route('patient.journey.tasks.complete', $task));

        $response->assertForbidden();
        $this->assertDatabaseHas('patient_tasks', ['id' => $task->id, 'status' => 'pending']);
    }

    public function test_patient_can_complete_their_own_task(): void
    {
        $owner = User::factory()->patient()->create();
        $task = PatientTask::factory()->create(['patient_user_id' => $owner->id, 'patient_id' => $owner->id]);

        $response = $this->actingAs($owner)->patch(route('patient.journey.tasks.complete', $task));

        $response->assertRedirect();
        $this->assertDatabaseHas('patient_tasks', ['id' => $task->id, 'status' => 'completed']);
    }

    public function test_patient_cannot_delete_another_patients_task(): void
    {
        $owner = User::factory()->patient()->create();
        $intruder = User::factory()->patient()->create();
        $task = PatientTask::factory()->create(['patient_user_id' => $owner->id, 'patient_id' => $owner->id]);

        $response = $this->actingAs($intruder)->delete(route('patient.journey.tasks.destroy', $task));

        $response->assertForbidden();
        $this->assertDatabaseHas('patient_tasks', ['id' => $task->id]);
    }

    public function test_patient_cannot_delete_another_patients_meal(): void
    {
        $owner = User::factory()->patient()->create();
        $intruder = User::factory()->patient()->create();
        $meal = PatientMeal::create([
            'user_id' => $owner->id,
            'meal_date' => now()->toDateString(),
            'meal_type' => 'lunch',
            'meal_name' => 'صحن رز',
            'calories' => 500,
            'protein' => 20,
            'carbs' => 60,
            'fat' => 15,
            'confidence' => 80,
            'source' => 'ai',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($intruder)->delete(route('patient.calories.destroy', $meal));

        $response->assertForbidden();
        $this->assertDatabaseHas('patient_meals', ['id' => $meal->id]);
    }
}
