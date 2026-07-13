<?php

namespace App\Services;

use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Throwable;

class AiChatService
{
    public function createConversation(User $user, string $firstMessage): AiChatConversation
    {
        return AiChatConversation::create([
            'user_id' => $user->id,
            'title' => $this->makeConversationTitle($firstMessage),
            'status' => 'active',
            'last_message_at' => now(),
        ]);
    }

    public function sendMessage(AiChatConversation $conversation, User $user, string $content): array
    {
        abort_if((int) $conversation->user_id !== (int) $user->id, 403);

        $userMessage = AiChatMessage::create([
            'ai_chat_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $content,
            'metadata' => [
                'source' => 'patient',
            ],
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'title' => $conversation->title ?: $this->makeConversationTitle($content),
        ]);

        try {
            $assistantText = $this->askAi($conversation, $user);
            $metadata = [
                'provider' => 'openrouter',
                'model' => $this->model(),
                'status' => 'success',
            ];
        } catch (PrismException $e) {
            Log::warning('AI chat Prism error', [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $assistantText = 'عذرًا، مساعد اتزان الذكي غير متاح مؤقتًا. جرّب مرة أخرى بعد قليل.';
            $metadata = [
                'provider' => 'openrouter',
                'model' => $this->model(),
                'status' => 'prism_error',
                'error' => $e->getMessage(),
            ];
        } catch (Throwable $e) {
            Log::error('AI chat generic error', [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $assistantText = 'حدث خطأ أثناء تجهيز الرد. جرّب مرة أخرى بعد قليل.';
            $metadata = [
                'provider' => 'openrouter',
                'model' => $this->model(),
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }

        $assistantMessage = AiChatMessage::create([
            'ai_chat_conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $assistantText,
            'metadata' => $metadata,
        ]);

        $conversation->update([
            'last_message_at' => now(),
        ]);

        return [
            'conversation' => $conversation->fresh(),
            'user_message' => $userMessage,
            'assistant_message' => $assistantMessage,
        ];
    }

    protected function askAi(AiChatConversation $conversation, User $user): string
    {
        if (! config('prism.providers.openrouter.api_key')) {
            return 'مساعد اتزان الذكي غير مفعل حاليًا. أضف مفتاح OpenRouter حتى يعمل الشات.';
        }

        $systemPrompt = $this->buildSystemPrompt($user);

        $historyMessages = $conversation
            ->messages()
            ->latest('id')
            ->limit(14)
            ->get()
            ->reverse()
            ->map(function (AiChatMessage $message) {
                return $message->role === 'assistant'
                    ? new AssistantMessage($message->content)
                    : new UserMessage($message->content);
            })
            ->all();

        $messages = array_merge([
            new SystemMessage($systemPrompt),
        ], $historyMessages);

        $response = Prism::text()
            ->using(Provider::OpenRouter, $this->model())
            ->withMessages($messages)
            ->withMaxTokens(700)
            ->usingTemperature(0.35)
            ->withClientOptions([
                'timeout' => (int) config('prism.request_timeout', 120),
            ])
            ->generate();

        return trim($response->text ?: 'لم أتمكن من توليد رد مناسب الآن.');
    }

    protected function buildSystemPrompt(User $user): string
    {
        $profileText = $this->buildPatientProfileContext($user);
        $mealsText = $this->buildRecentMealsContext($user);

        return <<<PROMPT
أنت "مساعد اتزان الذكي" داخل منصة اتزان الصحية الغذائية.

مهمتك:
- تقديم إرشادات غذائية وتوعوية عامة للمريض.
- شرح مفاهيم مثل السعرات، البروتين، الكربوهيدرات، الدهون، العادات اليومية، وتنظيم الوجبات.
- مساعدة المريض على فهم نتائج تحليل الوجبات داخل النظام.
- الرد بالعربية الفصحى البسيطة، وبأسلوب دافئ ومطمئن.
- اجعل الرد مختصرًا ومنظمًا ومفيدًا.

قواعد السلامة:
- لا تقدم تشخيصًا طبيًا.
- لا تصف أدوية أو جرعات.
- لا تطلب من المريض إيقاف علاج.
- إذا ذكر المريض ألمًا شديدًا، دوخة شديدة، إغماء، ضيق تنفس، نزيف، حمل، مرض مزمن غير مستقر، أو أعراض خطيرة، اطلب منه التواصل مع الطبيب أو الطوارئ.
- ذكّر المريض أن المساعد لا يغني عن الطبيب.

سياق المريض:
{$profileText}

آخر الوجبات المحفوظة إن وجدت:
{$mealsText}

طريقة الرد:
- ابدأ بالإجابة المباشرة.
- استخدم نقاط قليلة عند الحاجة.
- لا تطل الرد إلا إذا طلب المستخدم تفصيلًا.
PROMPT;
    }

    protected function buildPatientProfileContext(User $user): string
    {
        if (! Schema::hasTable('patient_profiles')) {
            return 'لا يوجد جدول ملف صحي متاح.';
        }

        $profile = DB::table('patient_profiles')
            ->where('user_id', $user->id)
            ->first();

        if (! $profile) {
            return 'لم يكمل المريض ملفه الصحي بعد.';
        }

        $items = [];

        foreach ([
            'age' => 'العمر',
            'gender' => 'الجنس',
            'height' => 'الطول',
            'weight' => 'الوزن',
            'activity_level' => 'مستوى النشاط',
            'health_goal' => 'الهدف الصحي',
            'daily_calorie_goal' => 'هدف السعرات المعتمد',
            'suggested_calorie_goal' => 'هدف السعرات المقترح',
            'medical_conditions' => 'حالات صحية',
            'food_allergies' => 'حساسيات غذائية',
        ] as $column => $label) {
            if (property_exists($profile, $column) && filled($profile->{$column})) {
                $items[] = "{$label}: {$profile->{$column}}";
            }
        }

        return $items ? implode("\n", $items) : 'يوجد ملف صحي، لكن لا توجد بيانات كافية.';
    }

    protected function buildRecentMealsContext(User $user): string
    {
        if (! Schema::hasTable('patient_meals')) {
            return 'لا يوجد سجل وجبات متاح بعد.';
        }

        $meals = DB::table('patient_meals')
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(5)
            ->get();

        if ($meals->isEmpty()) {
            return 'لا توجد وجبات محفوظة حتى الآن.';
        }

        return $meals->map(function ($meal) {
            $name = $meal->meal_name ?? 'وجبة';
            $calories = $meal->calories ?? 0;
            $protein = $meal->protein ?? 0;
            $carbs = $meal->carbs ?? 0;
            $fat = $meal->fat ?? 0;
            $date = $meal->meal_date ?? 'غير محدد';

            return "- {$date}: {$name} | {$calories} سعرة | بروتين {$protein}g | كارب {$carbs}g | دهون {$fat}g";
        })->implode("\n");
    }

    protected function makeConversationTitle(string $message): string
    {
        $clean = trim(strip_tags($message));

        return Str::limit($clean ?: 'محادثة جديدة', 45);
    }

    protected function model(): string
    {
        return config('prism.providers.openrouter.model') ?: 'tngtech/deepseek-r1t2-chimera:free';
    }
}
