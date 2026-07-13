<?php

namespace Tests\Feature;

use App\Models\AiChatConversation;
use App\Models\Article;
use App\Models\DoctorProfile;
use App\Models\PatientAppointment;
use App\Models\PatientProfile;
use App\Models\PatientTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_supports_role_states(): void
    {
        $this->assertSame('patient', User::factory()->patient()->create()->role);
        $this->assertSame('doctor', User::factory()->doctor()->create()->role);
        $this->assertSame('admin', User::factory()->admin()->create()->role);
    }

    public function test_patient_profile_factory_persists_health_fields(): void
    {
        $profile = PatientProfile::factory()->create();

        $this->assertNotNull($profile->user_id);
        $this->assertNotNull($profile->health_goal);
        $this->assertTrue($profile->profile_completed);
    }

    public function test_doctor_profile_factory_creates_a_doctor_user(): void
    {
        $profile = DoctorProfile::factory()->create();

        $this->assertSame('doctor', $profile->user->role);
    }

    public function test_article_factory_creates_a_published_article(): void
    {
        $article = Article::factory()->create();

        $this->assertSame('published', $article->status);
        $this->assertNotEmpty($article->slug);
    }

    public function test_patient_appointment_factory_links_patient_and_doctor(): void
    {
        $appointment = PatientAppointment::factory()->confirmed()->create();

        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame('patient', $appointment->patient->role);
        $this->assertInstanceOf(DoctorProfile::class, $appointment->doctorProfile);
    }

    public function test_patient_task_factory_supports_doctor_state(): void
    {
        $task = PatientTask::factory()->fromDoctor()->create();

        $this->assertSame('doctor', $task->source);
        $this->assertSame('doctor', $task->doctor->role);
    }

    public function test_ai_chat_conversation_factory_creates_conversation_for_patient(): void
    {
        $conversation = AiChatConversation::factory()->create();

        $this->assertSame('patient', $conversation->user->role);
    }
}
