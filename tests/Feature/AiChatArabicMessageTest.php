<?php

namespace Tests\Feature;

use App\Models\PatientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Tests\TestCase;

/**
 * يغطي هذا الاختبار سيناريو Bug 1: إرسال رسالة عربية لمساعد اتزان الذكي
 * (POST /patient/ai-chat/send) كان يرجع 500 بسبب موديل OpenRouter غير
 * موجود يخلي Prism/Guzzle يفشل بـ "Malformed UTF-8 characters" أثناء بناء
 * رد الـ JSON النهائي. هاد السيناريو ما كان مغطى قبل هيك بمجموعة الاختبارات.
 */
class AiChatArabicMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_send_arabic_message_and_gets_200_with_arabic_reply(): void
    {
        Prism::fake([
            TextResponseFake::make()->withText('أهلاً بك! يسعدني مساعدتك في تنظيم وجباتك.'),
        ]);

        $patient = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $patient->id]);

        $response = $this->actingAs($patient)->postJson(route('patient.ai-chat.send'), [
            'message' => 'مرحباً، شو أفضل غذاء صحي لمريض سكري؟',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $this->assertSame(
            'مرحباً، شو أفضل غذاء صحي لمريض سكري؟',
            $response->json('user_message.content')
        );

        $this->assertSame(
            'أهلاً بك! يسعدني مساعدتك في تنظيم وجباتك.',
            $response->json('assistant_message.content')
        );

        $this->assertDatabaseHas('ai_chat_messages', [
            'role' => 'user',
            'content' => 'مرحباً، شو أفضل غذاء صحي لمريض سكري؟',
        ]);
    }

    public function test_ai_chat_send_falls_back_gracefully_instead_of_crashing_when_the_model_fails(): void
    {
        $failingFake = new class([]) extends \Prism\Prism\Testing\PrismFake
        {
            #[\Override]
            public function text(\Prism\Prism\Text\Request $request): \Prism\Prism\Text\Response
            {
                throw new \Prism\Prism\Exceptions\PrismException(
                    'Sending to model (some/invalid-model) failed: HTTP request returned status code 404'
                );
            }
        };

        $this->app->instance(\Prism\Prism\PrismManager::class, new class($failingFake) extends \Prism\Prism\PrismManager
        {
            public function __construct(private readonly \Prism\Prism\Testing\PrismFake $fake)
            {
            }

            public function resolve(\Prism\Prism\Enums\Provider|string $name, array $providerConfig = []): \Prism\Prism\Testing\PrismFake
            {
                return $this->fake;
            }
        });

        $patient = User::factory()->patient()->create();
        PatientProfile::factory()->create(['user_id' => $patient->id]);

        $response = $this->actingAs($patient)->postJson(route('patient.ai-chat.send'), [
            'message' => 'مرحباً، عندي سؤال عن وجبة الفطور.',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
        $this->assertNotEmpty($response->json('assistant_message.content'));
    }
}
