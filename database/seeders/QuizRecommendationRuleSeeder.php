<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuizRecommendationRuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('quiz_recommendation_rules')->delete();

        /*
        |--------------------------------------------------------------------------
        | Recommendations
        |--------------------------------------------------------------------------
        */

        $rules = [
            // Doctor result - general
            [
                'content_type' => 'recommendation',
                'result_type' => 'doctor',
                'text' => 'لا تبدأ بخطة غذائية صارمة قبل أخذ رأي مختص.',
                'priority' => 1,
            ],
            [
                'content_type' => 'recommendation',
                'result_type' => 'doctor',
                'text' => 'جهّز معلوماتك الصحية الأساسية قبل حجز الاستشارة.',
                'priority' => 2,
            ],
            [
                'content_type' => 'recommendation',
                'result_type' => 'doctor',
                'text' => 'اكتب أي أعراض أو أدوية تستخدمها حتى تساعد الطبيب على فهم حالتك.',
                'priority' => 3,
            ],

            // Doctor - diabetes
            [
                'content_type' => 'recommendation',
                'result_type' => 'doctor',
                'condition' => 'diabetes',
                'text' => 'بما أنك اخترت السكري، الأفضل أن تكون أي خطة غذائية تحت متابعة مختص.',
                'priority' => 1,
            ],
            [
                'content_type' => 'task',
                'result_type' => 'doctor',
                'condition' => 'diabetes',
                'text' => 'سجّل قراءات السكر أو الملاحظات الصحية قبل الاستشارة.',
                'priority' => 1,
            ],

            // Doctor - pressure
            [
                'content_type' => 'recommendation',
                'result_type' => 'doctor',
                'condition' => 'pressure',
                'text' => 'وجود ضغط يحتاج انتباه عند تغيير النظام الغذائي، خصوصًا في كمية الملح ونمط الوجبات.',
                'priority' => 1,
            ],
            [
                'content_type' => 'task',
                'result_type' => 'doctor',
                'condition' => 'pressure',
                'text' => 'دوّن أي ملاحظات عن ضغطك أو الأعراض المتكررة قبل المتابعة.',
                'priority' => 1,
            ],

            // Doctor - allergy
            [
                'content_type' => 'recommendation',
                'result_type' => 'doctor',
                'condition' => 'allergy',
                'text' => 'بسبب وجود حساسية طعام، تجنّب تجربة أنظمة غذائية عشوائية قبل استشارة مختص.',
                'priority' => 1,
            ],
            [
                'content_type' => 'task',
                'result_type' => 'doctor',
                'condition' => 'allergy',
                'text' => 'اكتب قائمة بالأطعمة التي تسبب لك حساسية أو انزعاج.',
                'priority' => 1,
            ],

            // Doctor - symptoms
            [
                'content_type' => 'recommendation',
                'result_type' => 'doctor',
                'symptoms' => 'dizziness',
                'text' => 'وجود دوخة أو تعب شديد متكرر يجعل المتابعة الطبية خطوة أكثر أمانًا.',
                'priority' => 1,
            ],
            [
                'content_type' => 'recommendation',
                'result_type' => 'doctor',
                'symptoms' => 'weight_change',
                'text' => 'تغير الوزن غير المفسر يحتاج تقييمًا مختصًا قبل البدء بأي خطة.',
                'priority' => 1,
            ],
            [
                'content_type' => 'recommendation',
                'result_type' => 'doctor',
                'symptoms' => 'pain',
                'text' => 'وجود ألم أو مشكلة مستمرة يستدعي التأكد طبيًا قبل تغيير نمطك الغذائي.',
                'priority' => 1,
            ],

            // Doctor - medication
            [
                'content_type' => 'recommendation',
                'result_type' => 'doctor',
                'medication' => 'yes',
                'text' => 'استخدام أدوية مستمرة يعني أن التغيير الغذائي يجب أن يكون بحذر وتوجيه مختص.',
                'priority' => 1,
            ],
            [
                'content_type' => 'task',
                'result_type' => 'doctor',
                'medication' => 'yes',
                'text' => 'جهّز أسماء الأدوية أو صورها قبل التواصل مع الطبيب.',
                'priority' => 1,
            ],

            // Nutritionist result - general
            [
                'content_type' => 'recommendation',
                'result_type' => 'nutritionist',
                'text' => 'ابدأ بخطوات غذائية بسيطة بدل التغيير المفاجئ.',
                'priority' => 1,
            ],
            [
                'content_type' => 'recommendation',
                'result_type' => 'nutritionist',
                'text' => 'متابعة أخصائي تغذية قد تساعدك على اختيار خطة مناسبة لهدفك.',
                'priority' => 2,
            ],
            [
                'content_type' => 'task',
                'result_type' => 'nutritionist',
                'text' => 'سجّل وجبة واحدة اليوم حتى تبدأ بفهم نمطك الغذائي.',
                'priority' => 1,
            ],
            [
                'content_type' => 'task',
                'result_type' => 'nutritionist',
                'text' => 'اشرب كوبين ماء إضافيين خلال اليوم.',
                'priority' => 2,
            ],

            // Nutritionist - loss
            [
                'content_type' => 'recommendation',
                'result_type' => 'nutritionist',
                'goal' => 'loss',
                'text' => 'لأن هدفك خسارة وزن، ركّز على التدرج بدل الحرمان.',
                'priority' => 1,
            ],
            [
                'content_type' => 'task',
                'result_type' => 'nutritionist',
                'goal' => 'loss',
                'text' => 'قلّل مشروبًا سكريًا واحدًا اليوم إن وجد.',
                'priority' => 1,
            ],

            // Nutritionist - gain
            [
                'content_type' => 'recommendation',
                'result_type' => 'nutritionist',
                'goal' => 'gain',
                'text' => 'لأن هدفك زيادة وزن، ركّز على زيادة صحية ومتوازنة وليس عشوائية.',
                'priority' => 1,
            ],
            [
                'content_type' => 'task',
                'result_type' => 'nutritionist',
                'goal' => 'gain',
                'text' => 'أضف وجبة خفيفة صحية بين الوجبات.',
                'priority' => 1,
            ],

            // Low activity
            [
                'content_type' => 'recommendation',
                'activity' => 'low',
                'text' => 'نشاطك اليومي منخفض، لذلك ابدأ بحركة بسيطة جدًا حتى لا تشعر بالضغط.',
                'priority' => 1,
            ],
            [
                'content_type' => 'task',
                'activity' => 'low',
                'text' => 'قف أو تحرك لمدة 5 دقائق خلال اليوم.',
                'priority' => 1,
            ],

            // Self care
            [
                'content_type' => 'recommendation',
                'result_type' => 'self_care',
                'text' => 'ابدأ بعادة صحية صغيرة يسهل الالتزام بها.',
                'priority' => 1,
            ],
            [
                'content_type' => 'recommendation',
                'result_type' => 'self_care',
                'text' => 'لا تحتاج لتغيير كبير الآن، فقط خطوات بسيطة ومنظمة.',
                'priority' => 2,
            ],
            [
                'content_type' => 'task',
                'result_type' => 'self_care',
                'text' => 'اشرب 6 أكواب ماء اليوم.',
                'priority' => 1,
            ],
            [
                'content_type' => 'task',
                'result_type' => 'self_care',
                'text' => 'أضف خضار أو فاكهة لوجبة واحدة.',
                'priority' => 2,
            ],
            [
                'content_type' => 'task',
                'result_type' => 'self_care',
                'text' => 'اقرأ مقالًا صحيًا قصيرًا من اتزان.',
                'priority' => 3,
            ],
        ];

        foreach ($rules as $rule) {
            DB::table('quiz_recommendation_rules')->insert(array_merge([
                'goal' => null,
                'condition' => null,
                'activity' => null,
                'symptoms' => null,
                'medication' => null,
                'article_id' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ], $rule));
        }

        /*
        |--------------------------------------------------------------------------
        | Article Rules
        |--------------------------------------------------------------------------
        | يربط الكويز بمقالات موجودة فعلًا في جدول articles.
        |--------------------------------------------------------------------------
        */

        $this->attachArticleRule('doctor', ['سكري', 'سكر', 'diabetes'], [
            'condition' => 'diabetes',
        ]);

        $this->attachArticleRule('doctor', ['ضغط', 'pressure'], [
            'condition' => 'pressure',
        ]);

        $this->attachArticleRule('doctor', ['حساسية', 'allergy'], [
            'condition' => 'allergy',
        ]);

        $this->attachArticleRule('nutritionist', ['خسارة', 'وزن', 'loss'], [
            'goal' => 'loss',
        ]);

        $this->attachArticleRule('nutritionist', ['زيادة', 'وزن', 'gain'], [
            'goal' => 'gain',
        ]);

        $this->attachArticleRule('self_care', ['عادات', 'ماء', 'صحة', 'توازن'], []);
    }

    private function attachArticleRule(string $resultType, array $keywords, array $conditions = []): void
    {
        if (!Schema::hasTable('articles')) {
            return;
        }

        $articleId = $this->findArticleId($keywords);

        if (!$articleId) {
            return;
        }

        DB::table('quiz_recommendation_rules')->insert(array_merge([
            'content_type' => 'article',
            'result_type' => $resultType,
            'goal' => null,
            'condition' => null,
            'activity' => null,
            'symptoms' => null,
            'medication' => null,
            'article_id' => $articleId,
            'text' => null,
            'priority' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $conditions));
    }

    private function findArticleId(array $keywords): ?int
    {
        if (!Schema::hasTable('articles')) {
            return null;
        }

        $query = DB::table('articles');

        $searchableColumns = collect(['title', 'name', 'slug', 'description', 'summary', 'excerpt'])
            ->filter(fn ($column) => Schema::hasColumn('articles', $column))
            ->values();

        if ($searchableColumns->isEmpty()) {
            return DB::table('articles')->value('id');
        }

        $query->where(function ($q) use ($keywords, $searchableColumns) {
            foreach ($keywords as $keyword) {
                foreach ($searchableColumns as $column) {
                    $q->orWhere($column, 'like', '%' . $keyword . '%');
                }
            }
        });

        return $query->value('id') ?? DB::table('articles')->value('id');
    }
}
