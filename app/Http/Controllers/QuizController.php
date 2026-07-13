<?php

namespace App\Http\Controllers;

use App\Models\QuizRecommendationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class QuizController extends Controller
{
    public function index()
    {
        return view('pages.quiz');
    }

    public function analyze(Request $request)
    {
        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.goal' => ['nullable', 'string'],
            'answers.condition' => ['nullable', 'string'],
            'answers.activity' => ['nullable', 'string'],
            'answers.symptoms' => ['nullable', 'string'],
            'answers.medication' => ['nullable', 'string'],
        ]);

        return response()->json(
            $this->buildHybridResult($validated['answers'])
        );
    }

    private function buildHybridResult(array $answers): array
    {
        $score = 0;
        $redFlags = [];
        $tags = [];

        $goal = $answers['goal'] ?? null;
        $condition = $answers['condition'] ?? null;
        $activity = $answers['activity'] ?? null;
        $symptoms = $answers['symptoms'] ?? null;
        $medication = $answers['medication'] ?? null;

        if (in_array($condition, ['diabetes', 'pressure', 'allergy'])) {
            $score += 6;
            $redFlags[] = 'وجود حالة صحية تحتاج انتباه قبل البدء بخطة غذائية.';
        }

        if ($medication === 'yes') {
            $score += 5;
            $redFlags[] = 'استخدام أدوية بشكل مستمر يحتاج توجيه مختص.';
        }

        if (in_array($symptoms, ['dizziness', 'pain', 'weight_change'])) {
            $score += 7;
            $redFlags[] = 'وجود أعراض متكررة أو غير مفسرة يحتاج متابعة طبية.';
        }

        if ($goal === 'loss') {
            $score += 2;
            $tags[] = 'هدف خسارة وزن';
        }

        if ($goal === 'gain') {
            $score += 2;
            $tags[] = 'هدف زيادة وزن';
        }

        if ($goal === 'balance') {
            $tags[] = 'توازن غذائي';
        }

        if ($goal === 'health') {
            $tags[] = 'تحسين الصحة';
        }

        if ($activity === 'low') {
            $score += 1;
            $tags[] = 'نشاط يومي منخفض';
        }

        if ($activity === 'medium') {
            $tags[] = 'نشاط يومي متوسط';
        }

        if ($activity === 'high') {
            $tags[] = 'نشاط يومي جيد';
        }

        if (!empty($redFlags) || $score >= 8) {
            $type = 'doctor';
        } elseif ($score >= 3 || in_array($goal, ['loss', 'gain'])) {
            $type = 'nutritionist';
        } else {
            $type = 'self_care';
        }

        $personalized = $this->buildPersonalizedLayer($answers, $type);

        if ($type === 'doctor') {
            return [
                'type' => 'doctor',
                'icon' => 'fa-solid fa-user-doctor',
                'title' => 'يُفضّل التواصل مع طبيب قبل البدء',
                'message' => 'بناءً على إجاباتك، قد يكون من الأفضل أخذ متابعة طبية قبل البدء بأي خطة غذائية، خصوصًا لأن بعض الإجابات تشير إلى عوامل تحتاج تقييمًا مختصًا. هذا التقييم لا يشخص حالتك، لكنه يساعدك على اختيار الخطوة الأكثر أمانًا.',
                'character_message' => 'خلينا نبدأ بأمان. الأفضل تحكي مع مختص قبل أي خطة غذائية.',
                'tags' => array_values(array_unique(array_merge($tags, [
                    'متابعة طبية',
                    'سلامتك أولًا',
                    'تقييم غير تشخيصي',
                ]))),
                'recommendations' => $personalized['recommendations'],
                'articles' => $personalized['articles'],
                'daily_tasks' => $personalized['daily_tasks'],
                'actions' => [
                    [
                        'label' => 'تصفح الأطباء',
                        'url' => url('/doctors'),
                        'style' => 'primary',
                    ],
                    [
                        'label' => 'قراءة مقالات توعوية',
                        'url' => url('/articles'),
                        'style' => 'secondary',
                    ],
                ],
                'red_flags' => $redFlags,
            ];
        }

        if ($type === 'nutritionist') {
            return [
                'type' => 'nutritionist',
                'icon' => 'fa-solid fa-apple-whole',
                'title' => 'يفضل متابعة أخصائي تغذية',
                'message' => 'إجاباتك لا تشير بالضرورة إلى حاجة طبية عاجلة، لكنها توضح أن وجود أخصائي تغذية قد يساعدك على اختيار بداية مناسبة وآمنة حسب هدفك ونشاطك اليومي.',
                'character_message' => 'بدايتك ممتازة، ومع أخصائي تغذية رح تكون أوضح وأسهل.',
                'tags' => array_values(array_unique(array_merge($tags, [
                    'توجيه غذائي',
                    'خطة مناسبة',
                    'متابعة أفضل',
                ]))),
                'recommendations' => $personalized['recommendations'],
                'articles' => $personalized['articles'],
                'daily_tasks' => $personalized['daily_tasks'],
                'actions' => [
                    [
                        'label' => 'البحث عن أخصائي',
                        'url' => url('/doctors'),
                        'style' => 'primary',
                    ],
                    [
                        'label' => 'استكشف المقالات',
                        'url' => url('/articles'),
                        'style' => 'secondary',
                    ],
                ],
                'red_flags' => [],
            ];
        }

        return [
            'type' => 'self_care',
            'icon' => 'fa-solid fa-seedling',
            'title' => 'يمكنك البدء بخطوات صحية بسيطة',
            'message' => 'إجاباتك لا تشير حاليًا إلى حاجة واضحة لمتابعة طبية. يمكنك البدء بخطوات بسيطة مثل تنظيم الوجبات، شرب الماء، قراءة المقالات المناسبة، ومتابعة عاداتك اليومية.',
            'character_message' => 'حلو! نقدر نبدأ بخطوات بسيطة ونبني عادة صحية يوم بعد يوم.',
            'tags' => array_values(array_unique(array_merge($tags, [
                'بداية بسيطة',
                'عادات يومية',
                'محتوى توعوي',
            ]))),
            'recommendations' => $personalized['recommendations'],
            'articles' => $personalized['articles'],
            'daily_tasks' => $personalized['daily_tasks'],
            'actions' => [
                [
                    'label' => 'قراءة المقالات',
                    'url' => url('/articles'),
                    'style' => 'primary',
                ],
                [
                    'label' => 'تصفح الأطباء',
                    'url' => url('/doctors'),
                    'style' => 'secondary',
                ],
            ],
            'red_flags' => [],
        ];
    }

    private function buildPersonalizedLayer(array $answers, string $type): array
    {
        $recommendations = $this->getQuizTextsFromDatabase('recommendation', $answers, $type, 5);
        $articles = $this->getQuizArticlesFromDatabase($answers, $type, 4);
        $dailyTasks = $this->getQuizTextsFromDatabase('task', $answers, $type, 5);

        if (empty($recommendations)) {
            $recommendations = $this->defaultRecommendations($type);
        }

        if (empty($dailyTasks)) {
            $dailyTasks = $this->defaultTasks($type);
        }

        return [
            'recommendations' => $recommendations,
            'articles' => $articles,
            'daily_tasks' => $dailyTasks,
        ];
    }

    private function getQuizTextsFromDatabase(
        string $contentType,
        array $answers,
        string $resultType,
        int $limit = 5
    ): array {
        return $this->baseQuizRulesQuery($contentType, $answers, $resultType)
            ->whereNotNull('text')
            ->where('text', '!=', '')
            ->limit($limit)
            ->pluck('text')
            ->values()
            ->toArray();
    }

    private function getQuizArticlesFromDatabase(
        array $answers,
        string $resultType,
        int $limit = 4
    ): array {
        return $this->baseQuizRulesQuery('article', $answers, $resultType)
            ->with('article')
            ->whereNotNull('article_id')
            ->limit($limit)
            ->get()
            ->filter(fn ($rule) => $rule->article !== null)
            ->map(function ($rule) {
                $article = $rule->article;

                return [
                    'title' => $article->title ?? $article->name ?? 'مقال توعوي',
                    'description' => $article->description ?? $article->summary ?? $article->excerpt ?? '',
                    'category' => data_get($article, 'category.name', 'مقال'),
                    'url' => $this->articleUrl($article),
                ];
            })
            ->values()
            ->toArray();
    }

    private function baseQuizRulesQuery(
        string $contentType,
        array $answers,
        string $resultType
    ) {
        $goal = $answers['goal'] ?? null;
        $condition = $answers['condition'] ?? null;
        $activity = $answers['activity'] ?? null;
        $symptoms = $answers['symptoms'] ?? null;
        $medication = $answers['medication'] ?? null;

        $query = QuizRecommendationRule::query()
            ->where('is_active', true)
            ->where('content_type', $contentType)
            ->where(function ($q) use ($resultType) {
                $q->whereNull('result_type')
                    ->orWhere('result_type', $resultType);
            })
            ->where(function ($q) use ($goal) {
                $q->whereNull('goal')
                    ->orWhere('goal', $goal);
            })
            ->where(function ($q) use ($condition) {
                $q->whereNull('condition')
                    ->orWhere('condition', $condition);
            })
            ->where(function ($q) use ($activity) {
                $q->whereNull('activity')
                    ->orWhere('activity', $activity);
            })
            ->where(function ($q) use ($symptoms) {
                $q->whereNull('symptoms')
                    ->orWhere('symptoms', $symptoms);
            })
            ->where(function ($q) use ($medication) {
                $q->whereNull('medication')
                    ->orWhere('medication', $medication);
            });

        $scoreParts = [];
        $bindings = [];

        foreach ([
            'result_type' => $resultType,
            'goal' => $goal,
            'condition' => $condition,
            'activity' => $activity,
            'symptoms' => $symptoms,
            'medication' => $medication,
        ] as $column => $value) {
            if ($value !== null) {
                $wrappedColumn = '`' . $column . '`';
                $scoreParts[] = "CASE WHEN {$wrappedColumn} = ? THEN 1 ELSE 0 END";
                $bindings[] = $value;
            }
        }

        if (!empty($scoreParts)) {
            $query->orderByRaw('(' . implode(' + ', $scoreParts) . ') DESC', $bindings);
        }

        return $query->orderBy('priority');
    }

    private function articleUrl($article): string
    {
        if (!empty($article->url)) {
            return $article->url;
        }

        if (!empty($article->link)) {
            return $article->link;
        }

        if (Route::has('articles.show')) {
            return route('articles.show', $article->slug ?? $article->id);
        }

        return url('/articles');
    }

    private function defaultRecommendations(string $type): array
    {
        return match ($type) {
            'doctor' => [
                'لا تبدأ بخطة غذائية صارمة قبل أخذ رأي مختص.',
                'جهّز معلوماتك الصحية الأساسية قبل حجز الاستشارة.',
                'اكتب أي أعراض أو أدوية تستخدمها حتى تساعد الطبيب على فهم حالتك.',
            ],
            'nutritionist' => [
                'ابدأ بخطوات غذائية بسيطة بدل التغيير المفاجئ.',
                'متابعة أخصائي تغذية قد تساعدك على اختيار خطة مناسبة لهدفك.',
                'راقب نشاطك ووجباتك اليومية لمدة أسبوع لفهم نمطك.',
            ],
            default => [
                'ابدأ بعادة صحية صغيرة يسهل الالتزام بها.',
                'تابع المقالات التوعوية المناسبة لك داخل اتزان.',
                'لا تحتاج لتغيير كبير الآن، فقط خطوات بسيطة ومنظمة.',
            ],
        };
    }

    private function defaultTasks(string $type): array
    {
        return match ($type) {
            'doctor' => [
                'سجّل الأعراض أو الملاحظات الصحية التي تتكرر معك.',
                'اشرب ماء بانتظام بدون تغيير مفاجئ في نظامك.',
                'احجز أو خطط لمتابعة مختص قبل البدء بخطة جديدة.',
            ],
            'nutritionist' => [
                'سجّل وجبة واحدة اليوم.',
                'امشِ 10 دقائق أو حرّك جسمك بشكل خفيف.',
                'اشرب كوبين ماء إضافيين خلال اليوم.',
            ],
            default => [
                'اشرب 6 أكواب ماء اليوم.',
                'أضف خضار أو فاكهة لوجبة واحدة.',
                'اقرأ مقالًا صحيًا قصيرًا من اتزان.',
            ],
        };
    }
}
