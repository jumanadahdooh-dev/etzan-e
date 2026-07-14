<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Patient\Concerns\PatientContextHelpers;
use App\Http\Requests\Patient\BookAppointmentRequest;
use App\Http\Requests\Patient\CompleteProfileRequest;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\PatientDailyCalorieGoal;
use App\Models\PatientMeal;
use App\Services\AiMealAnalysisService;
use App\Services\AppNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PatientArticleController extends Controller
{
    use PatientContextHelpers;

    public function articles(Request $request): View
    {
        $data = $this->dashboardData([
            'pageTitle' => 'المقالات',
            'activePage' => 'articles',
        ]);

        $search = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'recommended');
        $selectedCategory = (string) $request->query('category', 'all');

        if ($filter === 'admin') {
            $filter = 'etzan';
        }

        if (! in_array($filter, ['recommended', 'all', 'doctor', 'etzan', 'research', 'tips', 'facts', 'ideas', 'wisdom', 'motivation'], true)) {
            $filter = 'recommended';
        }

        $doctor = $data['doctor'] ?? [];
        $doctorProfileId = !empty($doctor['id']) ? (int) $doctor['id'] : null;
        $doctorUserId = !empty($doctor['user_id']) ? (int) $doctor['user_id'] : $this->doctorUserIdFromProfile($doctorProfileId);
        $hasSelectedDoctor = !empty($doctor['is_selected']);

        $articleCategories = $this->patientArticleCategories();
        $doctorArticles = collect();
        $patientArticles = new LengthAwarePaginator([], 0, 9, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
        $featuredArticle = null;
        $doctorArticlesCount = 0;
        $etzanArticlesCount = 0;
        $allVisibleArticlesCount = 0;
        $recommendedArticlesCount = 0;
        $researchArticlesCount = 0;
        $tipsArticlesCount = 0;
        $factsArticlesCount = 0;
        $ideasArticlesCount = 0;
        $wisdomArticlesCount = 0;
        $motivationArticlesCount = 0;

        if ($this->tableExists('articles')) {
            $baseQuery = $this->patientVisibleArticlesQuery();

            if ($search !== '') {
                $baseQuery->where(function (Builder $query) use ($search) {
                    $query->where('title', 'like', '%' . $search . '%');

                    foreach (['excerpt', 'content', 'author_name'] as $column) {
                        if ($this->columnExists('articles', $column)) {
                            $query->orWhere($column, 'like', '%' . $search . '%');
                        }
                    }

                    if ($this->tableExists('article_categories')) {
                        $query->orWhereHas('category', function (Builder $categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', '%' . $search . '%');
                        });
                    }

                    if ($this->tableExists('specialties')) {
                        $query->orWhereHas('specialty', function (Builder $specialtyQuery) use ($search) {
                            $specialtyQuery->where('name', 'like', '%' . $search . '%');
                        });
                    }
                });
            }

            if ($selectedCategory !== 'all' && $selectedCategory !== '') {
                if ($this->columnExists('articles', 'article_category_id')) {
                    $baseQuery->where('article_category_id', $selectedCategory);
                } elseif ($this->columnExists('articles', 'specialty_id')) {
                    $baseQuery->where('specialty_id', $selectedCategory);
                }
            }

            if ($filter === 'recommended') {
                $this->applyPatientRecommendedArticleScope($baseQuery, $data, $doctorProfileId, $doctorUserId);
            } elseif ($filter === 'doctor') {
                if ($doctorProfileId || $doctorUserId) {
                    $this->applyDoctorArticleScope($baseQuery, $doctorProfileId, $doctorUserId);
                } else {
                    $baseQuery->whereRaw('1 = 0');
                }
            } elseif ($filter === 'etzan') {
                $this->applyEtzanArticleScope($baseQuery, $doctorProfileId, $doctorUserId);
            } elseif ($filter === 'research') {
                $this->applyArticleTypeScope($baseQuery, ['research_summary']);
            } elseif ($filter === 'tips') {
                $this->applyArticleTypeScope($baseQuery, ['quick_tip']);
            } elseif ($filter === 'facts') {
                $this->applyArticleTypeScope($baseQuery, ['general_info']);
            } elseif ($filter === 'ideas') {
                $this->applyArticleTypeScope($baseQuery, ['wellness_idea']);
            } elseif ($filter === 'wisdom') {
                $this->applyArticleTypeScope($baseQuery, ['health_wisdom']);
            } elseif ($filter === 'motivation') {
                $this->applyArticleTypeScope($baseQuery, ['motivational_quote']);
            }

            $patientArticles = $baseQuery
                ->paginate(9)
                ->withQueryString();

            if ($doctorProfileId || $doctorUserId) {
                $doctorCounterQuery = $this->patientVisibleArticlesQuery();
                $this->applyDoctorArticleScope($doctorCounterQuery, $doctorProfileId, $doctorUserId);
                $doctorArticlesCount = (int) $doctorCounterQuery->count();

                $doctorQuery = $this->patientVisibleArticlesQuery();
                $this->applyDoctorArticleScope($doctorQuery, $doctorProfileId, $doctorUserId);
                $doctorArticles = $doctorQuery->limit(4)->get();
            }

            $etzanCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyEtzanArticleScope($etzanCounterQuery, $doctorProfileId, $doctorUserId);
            $etzanArticlesCount = (int) $etzanCounterQuery->count();

            $allVisibleArticlesCount = (int) $this->patientVisibleArticlesQuery()->count();

            $recommendedCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyPatientRecommendedArticleScope($recommendedCounterQuery, $data, $doctorProfileId, $doctorUserId);
            $recommendedArticlesCount = (int) $recommendedCounterQuery->count();

            $researchCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyArticleTypeScope($researchCounterQuery, ['research_summary']);
            $researchArticlesCount = (int) $researchCounterQuery->count();

            $tipsCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyArticleTypeScope($tipsCounterQuery, ['quick_tip']);
            $tipsArticlesCount = (int) $tipsCounterQuery->count();

            $factsCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyArticleTypeScope($factsCounterQuery, ['general_info']);
            $factsArticlesCount = (int) $factsCounterQuery->count();

            $ideasCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyArticleTypeScope($ideasCounterQuery, ['wellness_idea']);
            $ideasArticlesCount = (int) $ideasCounterQuery->count();

            $wisdomCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyArticleTypeScope($wisdomCounterQuery, ['health_wisdom']);
            $wisdomArticlesCount = (int) $wisdomCounterQuery->count();

            $motivationCounterQuery = $this->patientVisibleArticlesQuery();
            $this->applyArticleTypeScope($motivationCounterQuery, ['motivational_quote']);
            $motivationArticlesCount = (int) $motivationCounterQuery->count();

            $featuredQuery = $this->patientVisibleArticlesQuery();

            if ($this->columnExists('articles', 'is_featured')) {
                $featuredQuery->where('is_featured', true);
            }

            $featuredArticle = $featuredQuery->first() ?: $this->patientVisibleArticlesQuery()->first();
        }

        $hasActiveArticleFilter = $search !== '' || ! in_array($filter, ['recommended', 'all'], true) || ($selectedCategory !== 'all' && $selectedCategory !== '');

        $articleResultsTitle = match ($filter) {
            'recommended' => 'محتوى مناسب لحالتك',
            'doctor' => 'مقالات طبيبك',
            'etzan' => 'مكتبة اتزان الصحية',
            'research' => 'ملخصات بحثية مبسطة',
            'tips' => 'نصائح سريعة',
            'facts' => 'معلومات صحية عامة',
            'ideas' => 'أفكار صحية يومية',
            'wisdom' => 'حكمة اليوم',
            'motivation' => 'رسائل تحفيزية',
            default => $search !== '' ? 'نتائج البحث' : 'مقالات صحية مختارة لك',
        };

        if ($selectedCategory !== 'all' && $selectedCategory !== '') {
            $selectedCategoryName = optional($articleCategories->firstWhere('id', (int) $selectedCategory))->name;
            if ($selectedCategoryName) {
                $articleResultsTitle = 'مقالات ' . $selectedCategoryName;
            }
        }

        $articleResultsSubtitle = $search !== ''
            ? 'نعرض فقط المقالات الصحية المطابقة لبحثك داخل لوحة المريض.'
            : match ($filter) {
                'recommended' => 'نعرض محتوى مناسبًا لهدفك الصحي وبيانات ملفك مثل الوزن، النشاط، النوم، الماء والحالات الصحية عند توفرها.',
                'doctor' => 'هذه المقالات منشورة من طبيب المتابعة المختار عند توفرها.',
                'etzan' => 'مقالات عامة من فريق اتزان والإدارة، بدون المقالات التقنية أو التجريبية.',
                'research' => 'ملخصات بحثية مبسطة للمريض، وتحتاج دائمًا لمصدر ومراجعة قبل النشر.',
                'tips' => 'نصائح قصيرة قابلة للتطبيق اليوم.',
                'facts' => 'معلومات صحية عامة بلغة بسيطة.',
                'ideas' => 'أفكار صغيرة تساعدك تبني عادة صحية واحدة في اليوم.',
                'wisdom' => 'حكم صحية قصيرة من اتزان، بدون نسبتها لأشخاص أو علماء إلا بوجود مصدر واضح.',
                'motivation' => 'رسائل تحفيزية قصيرة تساعدك على الالتزام اليومي بدون مبالغة أو وعود.',
                default => 'مقالات صحية فقط؛ المقالات التقنية مثل Back-end و Laravel لا تظهر هنا.',
            };

        return view('patient.articles', array_merge($data, [
            'patientArticles' => $patientArticles,
            'patientArticleCategories' => $articleCategories,
            'doctorArticleHighlights' => $doctorArticles,
            'featuredPatientArticle' => $featuredArticle,
            'articleSearch' => $search,
            'articleFilter' => $filter,
            'selectedArticleCategory' => $selectedCategory,
            'hasActiveArticleFilter' => $hasActiveArticleFilter,
            'articleResultsTitle' => $articleResultsTitle,
            'articleResultsSubtitle' => $articleResultsSubtitle,
            'doctorArticlesCount' => $doctorArticlesCount,
            'etzanArticlesCount' => $etzanArticlesCount,
            'allVisibleArticlesCount' => $allVisibleArticlesCount,
            'recommendedArticlesCount' => $recommendedArticlesCount,
            'researchArticlesCount' => $researchArticlesCount,
            'tipsArticlesCount' => $tipsArticlesCount,
            'factsArticlesCount' => $factsArticlesCount,
            'ideasArticlesCount' => $ideasArticlesCount,
            'wisdomArticlesCount' => $wisdomArticlesCount,
            'motivationArticlesCount' => $motivationArticlesCount,
            'articlesDoctor' => [
                'id' => $doctorProfileId,
                'user_id' => $doctorUserId,
                'name' => $doctor['name'] ?? 'طبيب المتابعة',
                'specialty' => $doctor['specialty'] ?? null,
                'avatar' => $doctor['avatar'] ?? null,
                'is_selected' => $hasSelectedDoctor,
            ],
        ]));
    }


    public function articleDetails(string $slug): View
    {
        if (! $this->tableExists('articles')) {
            abort(404);
        }

        $article = $this->patientVisibleArticlesQuery()
            ->where('slug', $slug)
            ->firstOrFail();

        $data = $this->dashboardData([
            'pageTitle' => $article->title ?? 'تفاصيل المقال',
            'activePage' => 'articles',
        ]);

        $doctor = $data['doctor'] ?? [];
        $doctorProfileId = !empty($doctor['id']) ? (int) $doctor['id'] : null;
        $doctorUserId = !empty($doctor['user_id']) ? (int) $doctor['user_id'] : $this->doctorUserIdFromProfile($doctorProfileId);

        $relatedQuery = $this->patientVisibleArticlesQuery()
            ->where('articles.id', '!=', $article->id);

        if ($this->columnExists('articles', 'article_category_id') && !empty($article->article_category_id)) {
            $relatedQuery->where('article_category_id', $article->article_category_id);
        } elseif ($this->columnExists('articles', 'specialty_id') && !empty($article->specialty_id)) {
            $relatedQuery->where('specialty_id', $article->specialty_id);
        }

        $relatedArticles = $relatedQuery->limit(3)->get();

        if ($relatedArticles->isEmpty()) {
            $relatedArticles = $this->patientVisibleArticlesQuery()
                ->where('articles.id', '!=', $article->id)
                ->limit(3)
                ->get();
        }

        $isCurrentDoctorArticle = false;

        if ($doctorUserId && $this->columnExists('articles', 'user_id')) {
            $isCurrentDoctorArticle = (int) ($article->user_id ?? 0) === (int) $doctorUserId;
        }

        if (! $isCurrentDoctorArticle && $doctorProfileId && $this->columnExists('articles', 'doctor_profile_id')) {
            $isCurrentDoctorArticle = (int) ($article->doctor_profile_id ?? 0) === (int) $doctorProfileId;
        }

        return view('patient.article-details', array_merge($data, [
            'patientArticle' => $article,
            'relatedPatientArticles' => $relatedArticles,
            'isCurrentDoctorArticle' => $isCurrentDoctorArticle,
            'articlesDoctor' => [
                'id' => $doctorProfileId,
                'user_id' => $doctorUserId,
                'name' => $doctor['name'] ?? 'طبيب المتابعة',
                'specialty' => $doctor['specialty'] ?? null,
                'avatar' => $doctor['avatar'] ?? null,
            ],
        ]));
    }

}
