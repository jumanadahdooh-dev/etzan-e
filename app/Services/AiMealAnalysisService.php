<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Throwable;

class AiMealAnalysisService
{
    public function analyzeTextMeal(string $description, string $mealType = 'lunch'): array
    {
        $description = trim($description);

        if ($description === '') {
            return $this->fallbackResult(
                mealType: $mealType,
                description: $description,
                note: 'اكتب وصفًا مختصرًا للوجبة حتى أقدر أحللها.'
            );
        }

        if (! env('OPENROUTER_API_KEY')) {
            return $this->fallbackResult(
                mealType: $mealType,
                description: $description,
                note: 'خدمة الذكاء الاصطناعي غير مفعلة حاليًا. أضف مفتاح OpenRouter.'
            );
        }

        try {
            $response = Prism::text()
                ->using(Provider::OpenRouter, $this->model())
                ->withMessages([
                    new SystemMessage($this->systemPrompt()),
                    new UserMessage($this->userPrompt($description, $mealType)),
                ])
                ->withMaxTokens(700)
                ->usingTemperature(0.2)
                ->withClientOptions([
                    'timeout' => (int) env('PRISM_REQUEST_TIMEOUT', 120),
                ])
                ->generate();

            $rawText = trim($response->text ?: '');

            $parsed = $this->parseJson($rawText);

            if (! $parsed) {
                return $this->fallbackResult(
                    mealType: $mealType,
                    description: $description,
                    note: 'لم أتمكن من قراءة نتيجة التحليل بشكل منظم. جرّب وصف الوجبة بتفاصيل أوضح.',
                    raw: $rawText
                );
            }

            return $this->normalizeResult($parsed, $description, $mealType, $rawText);
        } catch (PrismException $e) {
            Log::warning('AI meal analysis Prism error', [
                'model' => $this->model(),
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackResult(
                mealType: $mealType,
                description: $description,
                note: 'خدمة تحليل الوجبات غير متاحة مؤقتًا. جرّب مرة أخرى بعد قليل.',
                raw: $e->getMessage()
            );
        } catch (Throwable $e) {
            Log::error('AI meal analysis generic error', [
                'model' => $this->model(),
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackResult(
                mealType: $mealType,
                description: $description,
                note: 'حدث خطأ أثناء تحليل الوجبة. جرّب مرة أخرى.',
                raw: $e->getMessage()
            );
        }
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
أنت مساعد غذائي داخل منصة اتزان.

مهمتك:
- تقدير السعرات والعناصر الغذائية من وصف وجبة مكتوب.
- أعد النتيجة بصيغة JSON فقط بدون شرح خارج JSON.
- لا تعطِ تشخيصًا طبيًا.
- التقدير تقريبي وليس بديلًا عن الطبيب أو أخصائي التغذية.

قواعد JSON المطلوبة:
{
  "meal_name": "اسم مختصر للوجبة",
  "calories": 0,
  "protein": 0,
  "carbs": 0,
  "fat": 0,
  "confidence": 0,
  "notes": "ملاحظة قصيرة للمريض",
  "items": [
    {"name": "اسم العنصر", "estimated_calories": 0}
  ]
}

الأرقام تكون integers فقط.
confidence من 0 إلى 100.
PROMPT;
    }

    private function userPrompt(string $description, string $mealType): string
    {
        return <<<PROMPT
نوع الوجبة: {$mealType}

وصف الوجبة:
{$description}

حلل الوجبة وأرجع JSON فقط.
PROMPT;
    }

    private function parseJson(string $text): ?array
    {
        if ($text === '') {
            return null;
        }

        $clean = trim($text);

        $clean = preg_replace('/^```json/i', '', $clean);
        $clean = preg_replace('/^```/i', '', $clean);
        $clean = preg_replace('/```$/', '', $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function normalizeResult(array $data, string $description, string $mealType, string $rawText = ''): array
    {
        return [
            'meal_type' => $mealType,
            'meal_name' => $this->stringValue($data['meal_name'] ?? null, 'وجبة محللة'),
            'description' => $description,
            'calories' => $this->intRange($data['calories'] ?? 0, 0, 5000),
            'protein' => $this->intRange($data['protein'] ?? 0, 0, 400),
            'carbs' => $this->intRange($data['carbs'] ?? 0, 0, 700),
            'fat' => $this->intRange($data['fat'] ?? 0, 0, 400),
            'confidence' => $this->intRange($data['confidence'] ?? 70, 0, 100),
            'ai_notes' => $this->stringValue($data['notes'] ?? null, 'هذه نتيجة تقديرية، راجع الكمية قبل الحفظ.'),
            'items' => is_array($data['items'] ?? null) ? $data['items'] : [],
            'raw' => $rawText,
            'status' => 'success',
        ];
    }

    private function fallbackResult(string $mealType, string $description, string $note, string $raw = ''): array
    {
        return [
            'meal_type' => $mealType,
            'meal_name' => 'وجبة غير مؤكدة',
            'description' => $description,
            'calories' => 0,
            'protein' => 0,
            'carbs' => 0,
            'fat' => 0,
            'confidence' => 0,
            'ai_notes' => $note,
            'items' => [],
            'raw' => $raw,
            'status' => 'fallback',
        ];
    }

    private function intRange(mixed $value, int $min, int $max): int
    {
        $number = is_numeric($value) ? (int) round((float) $value) : 0;

        return max($min, min($max, $number));
    }

    private function stringValue(mixed $value, string $fallback): string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : $fallback;
    }

    private function model(): string
    {
        return env('OPENROUTER_MODEL', 'baidu/cobuddy:free');
    }
}
