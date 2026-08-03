<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Media\Image;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Throwable;

class AiMealAnalysisService
{
    private const MICRONUTRIENT_KEYS = [
        'vitamin_a', 'vitamin_c', 'vitamin_d', 'vitamin_b12',
        'iron', 'calcium', 'potassium', 'magnesium', 'zinc',
    ];

    /**
     * تحليل غني بالتفاصيل (مكونات + عناصر دقيقة) يحتاج وقتًا أطول من محادثة
     * نصية عادية، خصوصًا مع موديلات vision المجانية. مهلة مخصصة لهذه الخدمة
     * فقط، بدل تعديل prism.request_timeout المشترك مع خدمات AI الأخرى.
     */
    private const REQUEST_TIMEOUT_SECONDS = 150;

    /**
     * تحليل وجبة من وصف نصي و/أو صورة (مسار على قرص التخزين public).
     * الاثنان يمران بنفس المسار وينتجان نفس شكل البيانات.
     */
    public function analyze(?string $description, ?string $imageStoragePath, string $mealType = 'lunch'): array
    {
        $description = trim((string) $description);
        $hasImage = $imageStoragePath !== null && $imageStoragePath !== '';

        if ($description === '' && ! $hasImage) {
            return $this->fallbackResult(
                mealType: $mealType,
                description: $description,
                note: 'اكتب وصفًا مختصرًا للوجبة أو ارفع صورة حتى أقدر أحللها.'
            );
        }

        if (! config('prism.providers.openrouter.api_key')) {
            return $this->fallbackResult(
                mealType: $mealType,
                description: $description,
                note: 'خدمة الذكاء الاصطناعي غير مفعلة حاليًا. أضف مفتاح OpenRouter.'
            );
        }

        $model = $hasImage ? $this->visionModel() : $this->model();

        // بدون هذا، حد PHP الافتراضي لوقت التنفيذ (60 ثانية غالبًا) يقتل الطلب
        // قبل ما يوصل حتى لمهلة الـ HTTP client نفسها مع الموديلات المجانية البطيئة.
        @set_time_limit(self::REQUEST_TIMEOUT_SECONDS + 30);

        try {
            $additionalContent = [];

            if ($hasImage) {
                $additionalContent[] = Image::fromStoragePath($imageStoragePath, 'public');
            }

            $response = Prism::text()
                ->using(Provider::OpenRouter, $model)
                ->withMessages([
                    new SystemMessage($this->systemPrompt()),
                    new UserMessage($this->userPrompt($description, $mealType, $hasImage), $additionalContent),
                ])
                ->withMaxTokens(2000)
                ->usingTemperature(0.2)
                ->withClientOptions([
                    'timeout' => max(self::REQUEST_TIMEOUT_SECONDS, (int) config('prism.request_timeout', 120)),
                ])
                ->generate();

            $rawText = trim($response->text ?: '');
            $parsed = $this->parseJson($rawText);

            if (! $parsed) {
                return $this->fallbackResult(
                    mealType: $mealType,
                    description: $description,
                    note: 'لم أتمكن من قراءة نتيجة التحليل بشكل منظم. جرّب وصفًا أو صورة أوضح.',
                    raw: $rawText
                );
            }

            return $this->normalizeResult($parsed, $description, $mealType, $hasImage, $rawText);
        } catch (PrismException $e) {
            Log::warning('AI meal analysis Prism error', [
                'model' => $model,
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
                'model' => $model,
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

    /**
     * @deprecated استخدم analyze() — أُبقي عليها لتوافق أي استدعاء قديم.
     */
    public function analyzeTextMeal(string $description, string $mealType = 'lunch'): array
    {
        return $this->analyze($description, null, $mealType);
    }

    private function systemPrompt(): string
    {
        $micronutrientKeys = implode(', ', self::MICRONUTRIENT_KEYS);

        return <<<PROMPT
أنت أخصائي تغذية داخل منصة اتزان، وتحلل الوجبات مثل خبير تغذية محترف.

مهمتك:
- إذا أُرفقت صورة: تعرّف على الوجبة وكل مكوّن ظاهر فيها بشكل منفصل، وقدّر كمية كل مكوّن بالجرام.
- إذا كان هناك وصف نصي فقط: قسّم الوصف إلى مكوّناته المنطقية وقدّر كمياتها القياسية.
- لكل مكوّن، قدّر: السعرات، البروتين، الكربوهيدرات، الدهون، الألياف، السكر، الصوديوم (مليغرام)، ونسبة ثقتك بالتعرف عليه (0-100).
- قدّر أيضًا العناصر الدقيقة (micronutrients) الإجمالية للوجبة: {$micronutrientKeys} — كل قيمة برقم تقريبي ووحدة قياس مناسبة (مثل mg أو mcg).
- لا تخترع يقينًا غير موجود؛ إذا كنت غير متأكد من مكوّن، أعطه نسبة ثقة منخفضة بدل حذفه أو التأكيد عليه.
- لا تعطِ تشخيصًا طبيًا. التقدير تقريبي وليس بديلًا عن الطبيب أو أخصائي التغذية.
- اكتب كل القيم النصية (اسم الوجبة، أسماء المكوّنات، طريقة الطهي، الملاحظات) باللغة العربية دائمًا، حتى لو كان وصف المستخدم بلغة أخرى.
- أعد النتيجة بصيغة JSON فقط بدون أي شرح خارج JSON، وبدون Markdown fences.

بنية JSON المطلوبة بالضبط:
{
  "meal_name": "اسم مختصر للوجبة",
  "confidence": 0,
  "cooking_method": "طريقة الطهي إن أمكن تحديدها أو null",
  "ingredients": [
    {
      "name": "اسم المكوّن",
      "confidence": 0,
      "portion_g": 0,
      "calories": 0,
      "protein": 0,
      "carbs": 0,
      "fat": 0,
      "fiber": 0,
      "sugar": 0,
      "sodium": 0
    }
  ],
  "micronutrients": {
    "vitamin_a": {"value": 0, "unit": "mcg"},
    "vitamin_c": {"value": 0, "unit": "mg"},
    "vitamin_d": {"value": 0, "unit": "mcg"},
    "vitamin_b12": {"value": 0, "unit": "mcg"},
    "iron": {"value": 0, "unit": "mg"},
    "calcium": {"value": 0, "unit": "mg"},
    "potassium": {"value": 0, "unit": "mg"},
    "magnesium": {"value": 0, "unit": "mg"},
    "zinc": {"value": 0, "unit": "mg"}
  },
  "notes": "ملاحظة قصيرة للمريض"
}

كل الأرقام integers فقط. confidence من 0 إلى 100. أدرج كل مكوّن ظاهر بشكل منفصل، لا تجمعهم في مكوّن واحد.
PROMPT;
    }

    private function userPrompt(string $description, string $mealType, bool $hasImage): string
    {
        if ($hasImage && $description !== '') {
            return <<<PROMPT
نوع الوجبة: {$mealType}

الصورة المرفقة توضح الوجبة. المريض أضاف أيضًا هذا الوصف/الملاحظة:
{$description}

حلل الوجبة من الصورة والوصف معًا وأرجع JSON فقط حسب البنية المطلوبة.
PROMPT;
        }

        if ($hasImage) {
            return <<<PROMPT
نوع الوجبة: {$mealType}

حلل الوجبة الظاهرة في الصورة المرفقة وأرجع JSON فقط حسب البنية المطلوبة.
PROMPT;
        }

        return <<<PROMPT
نوع الوجبة: {$mealType}

وصف الوجبة:
{$description}

حلل الوجبة وأرجع JSON فقط حسب البنية المطلوبة.
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

    private function normalizeResult(array $data, string $description, string $mealType, bool $hasImage, string $rawText = ''): array
    {
        $ingredients = [];

        foreach ((is_array($data['ingredients'] ?? null) ? $data['ingredients'] : []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $ingredients[] = [
                'name' => $this->stringValue($item['name'] ?? null, 'مكوّن غير محدد'),
                'confidence' => $this->intRange($item['confidence'] ?? 60, 0, 100),
                'portion_g' => $this->intRange($item['portion_g'] ?? 0, 0, 3000),
                'calories' => $this->intRange($item['calories'] ?? 0, 0, 3000),
                'protein' => $this->intRange($item['protein'] ?? 0, 0, 300),
                'carbs' => $this->intRange($item['carbs'] ?? 0, 0, 500),
                'fat' => $this->intRange($item['fat'] ?? 0, 0, 300),
                'fiber' => $this->intRange($item['fiber'] ?? 0, 0, 100),
                'sugar' => $this->intRange($item['sugar'] ?? 0, 0, 300),
                'sodium' => $this->intRange($item['sodium'] ?? 0, 0, 5000),
            ];
        }

        $sum = function (string $key) use ($ingredients): int {
            return (int) array_sum(array_column($ingredients, $key));
        };

        $micronutrients = [];
        $rawMicros = is_array($data['micronutrients'] ?? null) ? $data['micronutrients'] : [];

        foreach (self::MICRONUTRIENT_KEYS as $key) {
            $entry = is_array($rawMicros[$key] ?? null) ? $rawMicros[$key] : [];

            $micronutrients[$key] = [
                'value' => $this->intRange($entry['value'] ?? 0, 0, 20000),
                'unit' => $this->stringValue($entry['unit'] ?? null, 'mg'),
            ];
        }

        return [
            'status' => 'success',
            'source' => $hasImage ? 'image' : 'text',
            'meal_type' => $mealType,
            'meal_name' => $this->stringValue($data['meal_name'] ?? null, 'وجبة محللة'),
            'description' => $description,
            'confidence' => $this->intRange($data['confidence'] ?? 70, 0, 100),
            'cooking_method' => $this->stringValue($data['cooking_method'] ?? null, ''),
            'ingredients' => $ingredients,
            'calories' => $sum('calories'),
            'protein' => $sum('protein'),
            'carbs' => $sum('carbs'),
            'fat' => $sum('fat'),
            'fiber' => $sum('fiber'),
            'sugar' => $sum('sugar'),
            'sodium' => $sum('sodium'),
            'micronutrients' => $micronutrients,
            'ai_notes' => $this->stringValue($data['notes'] ?? null, 'هذه نتيجة تقديرية، راجع الكمية قبل الحفظ.'),
            'raw' => $rawText,
        ];
    }

    private function fallbackResult(string $mealType, string $description, string $note, string $raw = ''): array
    {
        $micronutrients = [];

        foreach (self::MICRONUTRIENT_KEYS as $key) {
            $micronutrients[$key] = ['value' => 0, 'unit' => 'mg'];
        }

        return [
            'status' => 'fallback',
            'source' => 'text',
            'meal_type' => $mealType,
            'meal_name' => 'وجبة غير مؤكدة',
            'description' => $description,
            'confidence' => 0,
            'cooking_method' => '',
            'ingredients' => [],
            'calories' => 0,
            'protein' => 0,
            'carbs' => 0,
            'fat' => 0,
            'fiber' => 0,
            'sugar' => 0,
            'sodium' => 0,
            'micronutrients' => $micronutrients,
            'ai_notes' => $note,
            'raw' => $raw,
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
        return config('prism.providers.openrouter.model') ?: 'openai/gpt-oss-20b:free';
    }

    private function visionModel(): string
    {
        return config('prism.providers.openrouter.vision_model') ?: 'google/gemini-2.0-flash-exp:free';
    }
}
