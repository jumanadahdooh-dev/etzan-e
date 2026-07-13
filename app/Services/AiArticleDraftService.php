<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Exceptions\PrismException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Messages\SystemMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;
use Throwable;

class AiArticleDraftService
{
    public function generate(array $input): array
    {
        if (! env('OPENROUTER_API_KEY')) {
            return $this->fallbackDraft($input, 'خدمة الذكاء الاصطناعي غير مفعلة حاليًا. أضيفي OPENROUTER_API_KEY حتى يتم توليد المقال تلقائيًا.');
        }

        try {
            $response = Prism::text()
                ->using(Provider::OpenRouter, $this->model())
                ->withMessages([
                    new SystemMessage($this->systemPrompt()),
                    new UserMessage($this->userPrompt($input)),
                ])
                ->withMaxTokens($this->maxTokens($input['length'] ?? 'medium'))
                ->usingTemperature(0.32)
                ->withClientOptions([
                    'timeout' => (int) env('PRISM_REQUEST_TIMEOUT', 120),
                ])
                ->generate();

            $rawText = trim($response->text ?: '');
            $decoded = $this->parseJson($rawText);

            if (! $decoded) {
                return $this->fallbackDraft($input, 'لم أتمكن من قراءة نتيجة الذكاء الاصطناعي كـ JSON منظم. تم إنشاء مسودة آمنة يمكن تعديلها يدويًا.', $rawText);
            }

            return $this->normalizeDraft($decoded, $input, $rawText);
        } catch (PrismException $e) {
            Log::warning('AI article draft Prism error', [
                'model' => $this->model(),
                'topic' => $input['topic'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackDraft($input, 'خدمة توليد المقالات غير متاحة مؤقتًا. تم إنشاء مسودة آمنة يمكن تعديلها يدويًا.', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('AI article draft generic error', [
                'model' => $this->model(),
                'topic' => $input['topic'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackDraft($input, 'حدث خطأ أثناء توليد المقال. تم إنشاء مسودة آمنة يمكن تعديلها يدويًا.', $e->getMessage());
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
أنت محرر محتوى صحي داخل منصة "اتزان".

مهمتك توليد مسودة مقال عربي صحي تثقيفي للمريض، وليس تشخيصًا أو وصفة علاج.

قواعد صارمة:
- أعد النتيجة بصيغة JSON فقط بدون Markdown خارج JSON.
- لا تخترع أسماء دراسات أو علماء أو اقتباسات مشهورة.
- إذا لم يقدم المستخدم مصدرًا واضحًا، لا تنسب أي معلومة إلى دراسة محددة أو عالم معين.
- يمكن استخدام عبارات مثل: "تشير الإرشادات الصحية العامة" أو "توضح المصادر الصحية الموثوقة" بدون ادعاء مصدر محدد.
- إذا كان نوع المقال "ملخص بحثي" ولم يوجد مصدر، اكتب أنه "مسودة تحتاج إضافة مصدر ومراجعة".
- لا تقدم تشخيصًا طبيًا.
- لا تصف أدوية أو جرعات.
- لا تطلب من المريض إيقاف دواء.
- لا تقدم وعودًا مثل "سيشفى" أو "مضمون".
- أضف دائمًا تنبيهًا أن المحتوى للتثقيف ولا يغني عن الطبيب.
- لو كان النوع حكمة اليوم أو رسالة اليوم، اجعلها قصيرة ودافئة وقابلة للعرض ككرت داخل الصفحة الرئيسية، ولا تنسبها لشخص مشهور إلا إذا قدم الأدمن المصدر.
- لو كان النوع معلومة عامة، اجعلها قصيرة وواضحة مع تطبيق عملي واحد.
- لو كان النوع حكمة اليوم، اجعل المحتوى أقصر من المقال العادي: حكمة + شرح بسيط + تطبيق عملي.
- لو كان النوع رسالة اليوم التحفيزية، اجعلها مشجعة بدون جلد ذات أو وعود مبالغ فيها.
- لو كان النوع فكرة صحية، اجعلها قابلة للتطبيق خلال يوم واحد.
- اجعل اللغة عربية واضحة ودافئة ومناسبة لمريض في منصة غذائية.

صيغة JSON المطلوبة:
{
  "title": "عنوان قصير وواضح",
  "excerpt": "ملخص من 2-3 جمل",
  "content": "المقال كاملًا بعناوين فرعية ونقاط عملية",
  "reading_minutes": 5,
  "author_name": "فريق اتزان الذكي",
  "safety_note": "تنبيه طبي قصير",
  "source_note": "ملاحظة عن المصدر أو المراجعة"
}
PROMPT;
    }

    private function userPrompt(array $input): string
    {
        $typeLabels = [
            'health_article' => 'مقال صحي تثقيفي',
            'research_summary' => 'ملخص بحثي مبسط',
            'quick_tip' => 'نصائح سريعة عملية',
            'general_info' => 'معلومة صحية عامة قصيرة',
            'wellness_idea' => 'فكرة صحية قابلة للتطبيق',
            'health_wisdom' => 'حكمة اليوم من اتزان بدون نسبتها لشخص مشهور',
            'motivational_quote' => 'رسالة اليوم التحفيزية من اتزان بدون نسبتها لشخص مشهور',
        ];

        $lengthLabels = [
            'short' => 'قصير',
            'medium' => 'متوسط',
            'long' => 'طويل',
        ];

        $type = $typeLabels[$input['article_type'] ?? 'health_article'] ?? 'مقال صحي تثقيفي';
        $length = $lengthLabels[$input['length'] ?? 'medium'] ?? 'متوسط';
        $topic = $input['topic'] ?? 'مقال صحي عام';
        $audience = $input['audience'] ?? 'patient';
        $sourceTitle = $input['source_title'] ?? '';
        $sourceUrl = $input['source_url'] ?? '';
        $sourceYear = $input['source_year'] ?? '';
        $notes = $input['extra_notes'] ?? '';

        return <<<PROMPT
نوع المسودة: {$type}
طول المسودة: {$length}
الجمهور: {$audience}
الموضوع المطلوب: {$topic}

مصدر اختياري قدمه الأدمن:
- العنوان: {$sourceTitle}
- السنة: {$sourceYear}
- الرابط: {$sourceUrl}

ملاحظات إضافية من الأدمن:
{$notes}

اكتب مسودة مناسبة للمرضى داخل منصة اتزان.
إذا كان الجمهور weight_loss فاربط النص بعادات تساعد خسارة الوزن بدون وعود مبالغ فيها.
إذا كان الجمهور weight_gain فاربط النص بزيادة وزن صحية وتغذية متوازنة.
إذا كان الجمهور diabetes فاربط النص بتنظيم الوجبات وسلامة مريض السكري بدون جرعات أو أدوية.
إذا كان الجمهور heart_health فاربط النص بتقليل الملح والدهون المشبعة والنشاط اليومي بدون تشخيص.
إذا كان الجمهور low_activity فاربط النص بخطوات حركة بسيطة وآمنة.
إذا كان الجمهور sleep_health فاربط النص بالنوم وروتين المساء.
إذا كان الجمهور hydration فاربط النص بشرب الماء والعادات اليومية.
إذا لم يوجد مصدر واضح، لا تذكر أسماء دراسات أو علماء.
أعد JSON فقط.
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

    private function normalizeDraft(array $data, array $input, string $rawText = ''): array
    {
        $title = $this->cleanText($data['title'] ?? null, $input['topic'] ?? 'مسودة مقال صحي');
        $excerpt = $this->cleanText($data['excerpt'] ?? null, 'مسودة تثقيفية تحتاج مراجعة قبل النشر.');
        $content = $this->cleanText($data['content'] ?? null, 'هذه مسودة تحتاج مراجعة وتعديل قبل النشر.');
        $safetyNote = $this->cleanText($data['safety_note'] ?? null, 'هذا المحتوى للتثقيف ولا يغني عن استشارة الطبيب أو أخصائي التغذية.');
        $sourceNote = $this->cleanText($data['source_note'] ?? null, 'تحتاج هذه المسودة إلى مراجعة مصدرها قبل النشر.');

        $content = trim($content);

        if (! Str::contains($content, ['لا يغني', 'استشارة الطبيب', 'الطبيب'])) {
            $content .= "\n\nتنبيه مهم: هذا المحتوى للتثقيف العام ولا يغني عن استشارة الطبيب أو أخصائي التغذية، خاصة في حال وجود مرض مزمن أو أدوية منتظمة.";
        }

        return [
            'status' => 'success',
            'title' => Str::limit($title, 250, ''),
            'excerpt' => Str::limit($excerpt, 950, ''),
            'content' => $content,
            'reading_minutes' => $this->readingMinutes($data['reading_minutes'] ?? null, $content),
            'author_name' => $this->cleanText($data['author_name'] ?? null, 'فريق اتزان الذكي'),
            'safety_note' => $safetyNote,
            'source_note' => $sourceNote,
            'raw' => $rawText,
            'model' => $this->model(),
        ];
    }

    private function fallbackDraft(array $input, string $note, string $rawText = ''): array
    {
        $topic = trim((string) ($input['topic'] ?? 'موضوع صحي'));
        $sourceLine = filled($input['source_title'] ?? null)
            ? "\n\nمصدر للمراجعة: {$input['source_title']}" . (filled($input['source_year'] ?? null) ? " ({$input['source_year']})" : '')
            : '';

        $content = <<<CONTENT
# {$topic}

هذه مسودة أولية حول: {$topic}.

## لماذا هذا الموضوع مهم؟
يساعد فهم هذا الموضوع على بناء عادات صحية أوضح، لكنه يحتاج صياغة ومراجعة قبل النشر للمريض.

## نقاط عملية يمكن تطويرها
- ابدئي بشرح الفكرة بلغة بسيطة.
- أضيفي أمثلة من الحياة اليومية.
- اربطي النص بالغذاء، النشاط، النوم، أو الالتزام الصحي حسب الموضوع.
- تجنبي أي تشخيص أو نصيحة علاجية مباشرة.

## تنبيه
هذا المحتوى للتثقيف العام ولا يغني عن استشارة الطبيب أو أخصائي التغذية.{$sourceLine}
CONTENT;

        return [
            'status' => 'fallback',
            'title' => Str::limit($topic, 250, ''),
            'excerpt' => $note,
            'content' => $content,
            'reading_minutes' => 3,
            'author_name' => 'فريق اتزان الذكي',
            'safety_note' => 'هذا المحتوى للتثقيف ولا يغني عن استشارة الطبيب أو أخصائي التغذية.',
            'source_note' => $note,
            'raw' => $rawText,
            'model' => $this->model(),
        ];
    }

    private function cleanText(mixed $value, string $fallback): string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : $fallback;
    }

    private function readingMinutes(mixed $value, string $content): int
    {
        if (is_numeric($value)) {
            return max(1, min(30, (int) round((float) $value)));
        }

        $words = str_word_count(strip_tags($content));

        return max(2, min(30, (int) ceil($words / 180)));
    }

    private function maxTokens(string $length): int
    {
        return match ($length) {
            'short' => 1100,
            'long' => 2800,
            default => 2000,
        };
    }

    private function model(): string
    {
        return env('OPENROUTER_MODEL', 'tngtech/deepseek-r1t2-chimera:free');
    }
}
