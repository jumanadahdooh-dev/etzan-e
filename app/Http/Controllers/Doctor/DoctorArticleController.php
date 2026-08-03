<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Specialty;
use App\Services\AiArticleDraftService;
use App\Traits\GeneratesUniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * صفحة "مقالاتي" الحقيقية لجهة الدكتور — قبل هيك كانت view فاضي 10 أسطر
 * فيها أرقام وهمية (6 منشور، 2 قيد المراجعة...) مكتوبة بالكود.
 * الدكتور بينشئ مقال كمسودة أو يرسله للمراجعة، والأدمن (AdminArticleController)
 * هو يلي بيعتمد النشر النهائي — نفس منطق الموافقة الموجود أصلاً بالمشروع.
 */
class DoctorArticleController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(): View
    {
        $articles = Article::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('doctor.articles', [
            'pageTitle' => 'مقالاتي',
            'activePage' => 'articles',
            'articles' => $articles,
            'counts' => [
                'published' => $articles->where('status', 'published')->count(),
                'pending_review' => $articles->where('status', 'pending_review')->count(),
                'draft' => $articles->where('status', 'draft')->count(),
                'rejected' => $articles->where('status', 'rejected')->count(),
            ],
            'categories' => ArticleCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'specialties' => Specialty::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * "اقترح لي مسودة بالذكاء الاصطناعي" — نفس خدمة AiArticleDraftService
     * المستخدمة أصلاً بجهة الأدمن، معروضة هون للدكتور. بترجع لنفس الصفحة
     * وتفتح فورم "مقال جديد" معبّى بالمسودة حتى يراجعها الدكتور ويعدلها.
     */
    public function aiDraft(Request $request, AiArticleDraftService $service): RedirectResponse
    {
        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
        ], [
            'topic.required' => 'اكتب موضوع المقال حتى الذكاء الاصطناعي يقترحلك مسودة.',
        ]);

        $draft = $service->generate([
            'topic' => $validated['topic'],
            'article_type' => 'health_article',
            'audience' => 'patient',
            'length' => 'medium',
        ]);

        return redirect()
            ->route('doctor.articles')
            ->withInput([
                'title' => $draft['title'],
                'excerpt' => $draft['excerpt'],
                'content' => $draft['content'],
                'reading_minutes' => $draft['reading_minutes'],
            ])
            ->with('open_new_article_form', true)
            ->with($draft['status'] === 'success' ? 'success' : 'ai_notice',
                $draft['status'] === 'success'
                    ? 'جهزتلك مسودة بالذكاء الاصطناعي — راجعها وعدّل عليها قبل الإرسال.'
                    : $draft['source_note']);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateArticle($request);

        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('articles/covers', 'public');
        }

        $article = Article::create([
            'user_id' => auth()->id(),
            'article_category_id' => $validated['article_category_id'] ?? null,
            'specialty_id' => $validated['specialty_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $this->makeUniqueSlug(Article::class, $validated['title'], null, 'article'),
            'excerpt' => $validated['excerpt'],
            'content' => $validated['content'],
            'cover_image' => $coverImagePath,
            'reading_minutes' => $validated['reading_minutes'],
            'source' => 'doctor',
            'status' => $request->input('action') === 'submit' ? 'pending_review' : 'draft',
            'submitted_at' => $request->input('action') === 'submit' ? now() : null,
        ]);

        return redirect()
            ->route('doctor.articles')
            ->with('success', $article->status === 'pending_review'
                ? 'تم إرسال مقالك للمراجعة من الإدارة.'
                : 'تم حفظ مقالك كمسودة.');
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        abort_unless((int) $article->user_id === (int) auth()->id(), 403);

        $validated = $this->validateArticle($request);

        $coverImagePath = $article->cover_image;
        if ($request->hasFile('cover_image')) {
            if ($article->cover_image && Storage::disk('public')->exists($article->cover_image)) {
                Storage::disk('public')->delete($article->cover_image);
            }

            $coverImagePath = $request->file('cover_image')->store('articles/covers', 'public');
        }

        $becameSubmitted = $request->input('action') === 'submit' && $article->status !== 'pending_review';

        $article->update([
            'article_category_id' => $validated['article_category_id'] ?? null,
            'specialty_id' => $validated['specialty_id'] ?? null,
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'],
            'content' => $validated['content'],
            'cover_image' => $coverImagePath,
            'reading_minutes' => $validated['reading_minutes'],
            'status' => $becameSubmitted ? 'pending_review' : $article->status,
            'submitted_at' => $becameSubmitted ? now() : $article->submitted_at,
        ]);

        return redirect()
            ->route('doctor.articles')
            ->with('success', 'تم تحديث المقال.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        abort_unless((int) $article->user_id === (int) auth()->id(), 403);
        abort_unless(in_array($article->status, ['draft', 'rejected'], true), 403);

        if ($article->cover_image && Storage::disk('public')->exists($article->cover_image)) {
            Storage::disk('public')->delete($article->cover_image);
        }

        $article->delete();

        return redirect()->route('doctor.articles')->with('success', 'تم حذف المقال.');
    }

    private function validateArticle(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string', 'min:50'],
            'article_category_id' => ['nullable', 'exists:article_categories,id'],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'reading_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'title.required' => 'عنوان المقال مطلوب.',
            'excerpt.required' => 'ملخص قصير مطلوب.',
            'content.required' => 'محتوى المقال مطلوب.',
            'content.min' => 'محتوى المقال قصير جداً.',
            'reading_minutes.required' => 'مدة القراءة مطلوبة.',
            'cover_image.image' => 'صورة الغلاف يجب أن تكون صورة.',
            'cover_image.mimes' => 'صيغة صورة الغلاف يجب أن تكون jpg أو png أو webp.',
            'cover_image.max' => 'حجم صورة الغلاف يجب ألا يتجاوز 4MB.',
        ]);
    }
}
