<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Specialty;
use App\Services\AiArticleDraftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminArticleController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = Article::query()
            ->with(['specialty', 'author', 'approver'])
            ->latest();

        if ($request->filled('source') && $request->source !== 'all') {
            if ($request->source === 'ai') {
                if (Schema::hasColumn('articles', 'generated_by_ai')) {
                    $baseQuery->where('generated_by_ai', true);
                } else {
                    $baseQuery->whereRaw('1 = 0');
                }
            } else {
                $baseQuery->where('source', $request->source);
            }
        }

        if ($request->filled('specialty_id') && $request->specialty_id !== 'all') {
            $baseQuery->where('specialty_id', $request->specialty_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Kanban Columns
        |--------------------------------------------------------------------------
        | الصفحة الجديدة ستعرض المقالات كأعمدة حسب الحالة.
        */

        $articleColumns = [
            'pending_review' => [
                'label' => 'قيد المراجعة',
                'description' => 'مقالات الأطباء التي تحتاج قرارًا.',
                'icon' => 'fa-regular fa-clock',
                'items' => (clone $baseQuery)
                    ->where('status', 'pending_review')
                    ->latest()
                    ->get(),
            ],

            'published' => [
                'label' => 'منشورة',
                'description' => 'المقالات الظاهرة في الموقع.',
                'icon' => 'fa-solid fa-circle-check',
                'items' => (clone $baseQuery)
                    ->where('status', 'published')
                    ->latest()
                    ->get(),
            ],

            'draft' => [
                'label' => 'مسودات',
                'description' => 'مقالات غير منشورة بعد.',
                'icon' => 'fa-regular fa-file-lines',
                'items' => (clone $baseQuery)
                    ->where('status', 'draft')
                    ->latest()
                    ->get(),
            ],

            'rejected' => [
                'label' => 'مرفوضة',
                'description' => 'مقالات تم رفضها مع سبب الرفض.',
                'icon' => 'fa-solid fa-ban',
                'items' => (clone $baseQuery)
                    ->where('status', 'rejected')
                    ->latest()
                    ->get(),
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Backward compatibility
        |--------------------------------------------------------------------------
        | نخليه موجود مؤقتًا عشان لو الـ Blade القديم لسه عندك ما ينهار.
        */

        $listQuery = clone $baseQuery;

        if ($request->filled('status') && $request->status !== 'all') {
            $listQuery->where('status', $request->status);
        }

        $articles = $listQuery->paginate(10)->withQueryString();

        $specialties = Specialty::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => Article::count(),
            'published' => Article::where('status', 'published')->count(),
            'draft' => Article::where('status', 'draft')->count(),
            'pending' => Article::where('status', 'pending_review')->count(),
            'rejected' => Article::where('status', 'rejected')->count(),
        ];

        return view('admin.articles', compact(
            'articles',
            'articleColumns',
            'specialties',
            'stats'
        ));
    }

    public function create()
    {
        return redirect()->route('admin.articles.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            '_form' => ['nullable', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'specialty_id' => ['required', 'exists:specialties,id'],
            'excerpt' => ['required', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'reading_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'status' => ['required', 'in:draft,published'],
            'is_featured' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'title.required' => 'عنوان المقال مطلوب.',
            'specialty_id.required' => 'اختيار التخصص مطلوب.',
            'specialty_id.exists' => 'التخصص المختار غير صحيح.',
            'excerpt.required' => 'ملخص المقال مطلوب.',
            'content.required' => 'محتوى المقال مطلوب.',
            'reading_minutes.required' => 'مدة القراءة مطلوبة.',
            'reading_minutes.integer' => 'مدة القراءة يجب أن تكون رقمًا.',
            'reading_minutes.min' => 'مدة القراءة يجب أن تكون دقيقة واحدة على الأقل.',
            'status.required' => 'حالة المقال مطلوبة.',
            'status.in' => 'حالة المقال غير صحيحة.',
            'cover_image.image' => 'صورة الغلاف يجب أن تكون صورة.',
            'cover_image.mimes' => 'صيغة صورة الغلاف يجب أن تكون jpg أو png أو webp.',
            'cover_image.max' => 'حجم صورة الغلاف يجب ألا يتجاوز 4MB.',
        ]);

        $coverImagePath = null;

        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('articles/covers', 'public');
        }

        $status = $validated['status'];
        $isPublished = $status === 'published';

        $extraPayload = $this->articleOptionalPayload([
            'audience' => 'patient',
            'article_type' => 'health_article',
            'generated_by_ai' => false,
            'reviewed_by_admin' => true,
            'medical_disclaimer' => 'هذا المحتوى للتثقيف ولا يغني عن استشارة الطبيب أو أخصائي التغذية.',
        ]);

        Article::create(array_merge([
            'user_id' => auth()->id(),
            'specialty_id' => $validated['specialty_id'],
            'source' => 'admin',
            'title' => $validated['title'],
            'slug' => $this->makeUniqueSlug($validated['title']),
            'excerpt' => $validated['excerpt'],
            'content' => $validated['content'],
            'cover_image' => $coverImagePath,
            'icon' => null,
            'author_name' => $validated['author_name'] ?: (auth()->user()->name ?? 'فريق اتزان'),
            'reading_minutes' => $validated['reading_minutes'],
            'status' => $status,
            'is_featured' => $request->boolean('is_featured'),
            'approved_by' => $isPublished ? auth()->id() : null,
            'approved_at' => $isPublished ? now() : null,
            'submitted_at' => null,
            'rejection_reason' => null,
            'published_at' => $isPublished ? now() : null,
        ], $extraPayload));

        return back()->with('success', 'تم إضافة المقال بنجاح.');
    }

    public function generateAiDraft(Request $request, AiArticleDraftService $aiArticleDraftService)
    {
        $validated = $request->validate([
            '_form' => ['nullable', 'string'],
            'topic' => ['required', 'string', 'max:255'],
            'specialty_id' => ['required', 'exists:specialties,id'],
            'article_type' => ['required', 'in:health_article,research_summary,quick_tip,general_info,wellness_idea,health_wisdom,motivational_quote'],
            'audience' => ['required', 'in:patient,general,weight_loss,weight_gain,diabetes,heart_health,low_activity,sleep_health,hydration'],
            'length' => ['required', 'in:short,medium,long'],
            'source_title' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'source_year' => ['nullable', 'integer', 'min:1950', 'max:' . (now()->year + 1)],
            'extra_notes' => ['nullable', 'string', 'max:1500'],
        ], [
            'topic.required' => 'اكتبي فكرة المقال أو عنوانه أولًا.',
            'specialty_id.required' => 'اختيار التخصص مطلوب.',
            'specialty_id.exists' => 'التخصص المختار غير صحيح.',
            'article_type.required' => 'اختاري نوع المسودة.',
            'article_type.in' => 'نوع المسودة غير صحيح.',
            'audience.required' => 'اختاري الجمهور المستهدف.',
            'audience.in' => 'الجمهور المستهدف غير صحيح.',
            'length.required' => 'اختاري طول المسودة.',
            'length.in' => 'طول المسودة غير صحيح.',
            'source_url.url' => 'رابط المصدر غير صحيح.',
            'source_year.integer' => 'سنة المصدر يجب أن تكون رقمًا.',
        ]);

        $draft = $aiArticleDraftService->generate($validated);

        $articleTypeLabels = [
            'health_article' => 'مقال صحي',
            'research_summary' => 'ملخص بحثي',
            'quick_tip' => 'نصائح سريعة',
            'general_info' => 'معلومة عامة',
            'wellness_idea' => 'فكرة صحية',
            'health_wisdom' => 'حكمة صحية',
            'motivational_quote' => 'رسالة تحفيزية',
        ];

        $metadata = [
            'provider' => 'openrouter',
            'model' => $draft['model'] ?? env('OPENROUTER_MODEL'),
            'generation_status' => $draft['status'] ?? 'success',
            'article_type_label' => $articleTypeLabels[$validated['article_type']] ?? $validated['article_type'],
            'audience' => $validated['audience'],
            'length' => $validated['length'],
            'source_note' => $draft['source_note'] ?? null,
            'raw' => $draft['raw'] ?? null,
        ];

        $payload = [
            'user_id' => auth()->id(),
            'specialty_id' => $validated['specialty_id'],
            'source' => 'admin',
            'title' => $draft['title'],
            'slug' => $this->makeUniqueSlug($draft['title']),
            'excerpt' => $draft['excerpt'],
            'content' => $draft['content'],
            'cover_image' => $this->generateAiCoverImage($draft['title'], $validated['article_type'], $validated['audience']),
            'icon' => null,
            'author_name' => $draft['author_name'] ?? 'فريق اتزان الذكي',
            'reading_minutes' => $draft['reading_minutes'] ?? 3,
            'status' => 'draft',
            'is_featured' => false,
            'approved_by' => null,
            'approved_at' => null,
            'submitted_at' => null,
            'rejection_reason' => null,
            'published_at' => null,
        ];

        $payload = array_merge($payload, $this->articleOptionalPayload([
            'audience' => $validated['audience'],
            'article_type' => $validated['article_type'],
            'generated_by_ai' => true,
            'reviewed_by_admin' => false,
            'source_title' => $validated['source_title'] ?? null,
            'source_url' => $validated['source_url'] ?? null,
            'source_year' => $validated['source_year'] ?? null,
            'medical_disclaimer' => $draft['safety_note'] ?? 'هذا المحتوى للتثقيف ولا يغني عن استشارة الطبيب أو أخصائي التغذية.',
            'ai_prompt' => $validated['topic'],
            'ai_generation_metadata' => $metadata,
        ]));

        Article::create($payload);

        $message = ($draft['status'] ?? 'success') === 'success'
            ? 'تم توليد مسودة AI وحفظها كمسودة بانتظار مراجعتك قبل النشر.'
            : 'تم إنشاء مسودة آمنة، لكن خدمة AI لم ترجع نتيجة كاملة. راجعي المسودة قبل النشر.';

        return redirect()
            ->route('admin.articles.index', ['status' => 'draft'])
            ->with('success', $message);
    }

    public function edit(Article $article)
    {
        return redirect()->route('admin.articles.index');
    }

    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            '_form' => ['nullable', 'string'],
            'article_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'specialty_id' => ['required', 'exists:specialties,id'],
            'excerpt' => ['required', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'reading_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'status' => ['required', 'in:draft,published,pending_review,rejected'],
            'is_featured' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'title.required' => 'عنوان المقال مطلوب.',
            'specialty_id.required' => 'اختيار التخصص مطلوب.',
            'specialty_id.exists' => 'التخصص المختار غير صحيح.',
            'excerpt.required' => 'ملخص المقال مطلوب.',
            'content.required' => 'محتوى المقال مطلوب.',
            'reading_minutes.required' => 'مدة القراءة مطلوبة.',
            'reading_minutes.integer' => 'مدة القراءة يجب أن تكون رقمًا.',
            'reading_minutes.min' => 'مدة القراءة يجب أن تكون دقيقة واحدة على الأقل.',
            'status.required' => 'حالة المقال مطلوبة.',
            'status.in' => 'حالة المقال غير صحيحة.',
            'cover_image.image' => 'صورة الغلاف يجب أن تكون صورة.',
            'cover_image.mimes' => 'صيغة صورة الغلاف يجب أن تكون jpg أو png أو webp.',
            'cover_image.max' => 'حجم صورة الغلاف يجب ألا يتجاوز 4MB.',
        ]);

        $coverImagePath = $article->cover_image;

        if ($request->hasFile('cover_image')) {
            if ($article->cover_image && Storage::disk('public')->exists($article->cover_image)) {
                Storage::disk('public')->delete($article->cover_image);
            }

            $coverImagePath = $request->file('cover_image')->store('articles/covers', 'public');
        }

        $wasPublished = $article->status === 'published';
        $willBePublished = $validated['status'] === 'published';

        $extraPayload = $this->articleOptionalPayload([
            'reviewed_by_admin' => $willBePublished ? true : ($article->reviewed_by_admin ?? false),
            'medical_disclaimer' => $article->medical_disclaimer ?? 'هذا المحتوى للتثقيف ولا يغني عن استشارة الطبيب أو أخصائي التغذية.',
        ]);

        $article->update(array_merge([
            'specialty_id' => $validated['specialty_id'],
            'title' => $validated['title'],
            'slug' => $this->makeUniqueSlug($validated['title'], $article->id),
            'excerpt' => $validated['excerpt'],
            'content' => $validated['content'],
            'cover_image' => $coverImagePath,
            'author_name' => $validated['author_name'] ?: $article->author_name,
            'reading_minutes' => $validated['reading_minutes'],
            'status' => $validated['status'],
            'is_featured' => $request->boolean('is_featured'),
            'approved_by' => $willBePublished ? auth()->id() : $article->approved_by,
            'approved_at' => $willBePublished ? ($article->approved_at ?? now()) : $article->approved_at,
            'published_at' => $willBePublished ? ($article->published_at ?? now()) : ($wasPublished ? $article->published_at : null),
            'rejection_reason' => $validated['status'] === 'rejected' ? $article->rejection_reason : null,
        ], $extraPayload));

        return back()->with('success', 'تم تعديل المقال بنجاح.');
    }

    public function approve(Article $article)
    {
        $article->update(array_merge([
            'status' => 'published',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'published_at' => now(),
            'rejection_reason' => null,
        ], $this->articleOptionalPayload([
            'reviewed_by_admin' => true,
        ])));

        return back()->with('success', 'تم قبول المقال ونشره بنجاح.');
    }

    public function reject(Request $request, Article $article)
    {
        $validated = $request->validate([
            '_form' => ['nullable', 'string'],
            'article_id' => ['nullable', 'integer'],
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ], [
            'rejection_reason.required' => 'سبب الرفض مطلوب.',
            'rejection_reason.max' => 'سبب الرفض طويل جدًا.',
        ]);

        $article->update([
            'status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'published_at' => null,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return back()->with('success', 'تم رفض المقال بنجاح.');
    }

    public function destroy(Article $article)
    {
        if ($article->cover_image && Storage::disk('public')->exists($article->cover_image)) {
            Storage::disk('public')->delete($article->cover_image);
        }

        $article->delete();

        return back()->with('success', 'تم حذف المقال بنجاح.');
    }

    private function generateAiCoverImage(string $title, string $articleType, string $audience): string
    {
        /*
        |--------------------------------------------------------------------------
        | Auto Cover Image
        |--------------------------------------------------------------------------
        | صورة الغلاف هنا لا تحتوي أي كتابة داخل الصورة نفسها.
        | الهدف: صورة صحية رمزية تناسب نوع المحتوى والجمهور المستهدف.
        | النصوص تبقى في عنوان المقال/الكرت، وليس داخل الصورة.
        */

        $palette = match ($audience) {
            'weight_loss' => ['#EFFFF8', '#DDF7F1', '#1D9E75', '#7EDAC2', '#4DA8DA'],
            'weight_gain' => ['#FFF8E9', '#FDEBC6', '#D9892B', '#F6BD60', '#1D9E75'],
            'diabetes' => ['#EDF7FF', '#D7ECFF', '#4DA8DA', '#7BC7F1', '#1D9E75'],
            'heart_health' => ['#FFF1F0', '#FFE3E0', '#D85D57', '#F28B82', '#1D9E75'],
            'low_activity' => ['#F4F7FF', '#E4EAFF', '#6574C4', '#A6B3FF', '#1D9E75'],
            'sleep_health' => ['#F0F4FF', '#DEE8FF', '#4D6FDA', '#91A7FF', '#1D9E75'],
            'hydration' => ['#ECFBFF', '#D8F4FF', '#4DA8DA', '#8BDAF5', '#1D9E75'],
            default => ['#EFFFF8', '#E3F8F3', '#1D9E75', '#8BE0C8', '#4DA8DA'],
        };

        [$bg1, $bg2, $primary, $soft, $accent] = $palette;

        $mainSymbol = match ($articleType) {
            'research_summary' => $this->svgResearchSymbol($primary, $accent),
            'quick_tip' => $this->svgTipSymbol($primary, $accent),
            'general_info' => $this->svgInfoSymbol($primary, $accent),
            'wellness_idea' => $this->svgIdeaSymbol($primary, $accent),
            'health_wisdom' => $this->svgWisdomSymbol($primary, $accent),
            'motivational_quote' => $this->svgMotivationSymbol($primary, $accent),
            default => $this->svgHealthSymbol($primary, $accent),
        };

        $audienceSymbol = match ($audience) {
            'weight_loss' => $this->svgPlateSymbol($primary, $accent),
            'weight_gain' => $this->svgProteinSymbol($primary, $accent),
            'diabetes' => $this->svgBloodSugarSymbol($primary, $accent),
            'heart_health' => $this->svgHeartSymbol($primary, $accent),
            'low_activity' => $this->svgStepsSymbol($primary, $accent),
            'sleep_health' => $this->svgMoonSymbol($primary, $accent),
            'hydration' => $this->svgWaterSymbol($primary, $accent),
            default => $this->svgLeafSymbol($primary, $accent),
        };

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1280" height="720" viewBox="0 0 1280 720" role="img" aria-label="Etzan health illustration cover">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$bg1}"/>
      <stop offset="0.56" stop-color="{$bg2}"/>
      <stop offset="1" stop-color="#F8FCFB"/>
    </linearGradient>
    <linearGradient id="main" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$primary}"/>
      <stop offset="1" stop-color="{$accent}"/>
    </linearGradient>
    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="24" stdDeviation="28" flood-color="#123D47" flood-opacity="0.14"/>
    </filter>
    <filter id="softShadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="14" stdDeviation="20" flood-color="#123D47" flood-opacity="0.10"/>
    </filter>
  </defs>

  <rect width="1280" height="720" rx="56" fill="url(#bg)"/>
  <circle cx="1096" cy="102" r="255" fill="{$primary}" opacity="0.12"/>
  <circle cx="126" cy="612" r="235" fill="{$accent}" opacity="0.12"/>
  <path d="M110 512C205 455 255 485 350 428C448 368 456 267 579 250C720 230 770 330 900 278C1018 231 1063 122 1172 118" fill="none" stroke="{$primary}" stroke-width="16" stroke-linecap="round" opacity="0.10"/>

  <g filter="url(#shadow)">
    <rect x="112" y="96" width="1056" height="528" rx="54" fill="#FFFFFF" opacity="0.78"/>
  </g>

  <g opacity="0.92">
    <circle cx="1016" cy="186" r="72" fill="{$soft}" opacity="0.42"/>
    <circle cx="232" cy="528" r="54" fill="{$accent}" opacity="0.13"/>
    <circle cx="1094" cy="520" r="34" fill="{$primary}" opacity="0.14"/>
    <circle cx="206" cy="180" r="32" fill="{$accent}" opacity="0.16"/>
  </g>

  <g transform="translate(392 150)" filter="url(#softShadow)">
    <rect x="0" y="0" width="496" height="420" rx="60" fill="#ffffff" opacity="0.86"/>
    <circle cx="248" cy="210" r="136" fill="url(#main)" opacity="0.12"/>
    {$mainSymbol}
  </g>

  <g transform="translate(150 176)" filter="url(#softShadow)">
    <rect x="0" y="0" width="210" height="178" rx="44" fill="#ffffff" opacity="0.72"/>
    {$audienceSymbol}
  </g>

  <g transform="translate(920 376)" filter="url(#softShadow)">
    <rect x="0" y="0" width="210" height="178" rx="44" fill="#ffffff" opacity="0.72"/>
    {$this->svgLeafSymbol($primary, $accent)}
  </g>

  <g opacity="0.25">
    <circle cx="560" cy="608" r="8" fill="{$primary}"/>
    <circle cx="604" cy="608" r="8" fill="{$accent}"/>
    <circle cx="648" cy="608" r="8" fill="{$primary}"/>
    <circle cx="692" cy="608" r="8" fill="{$accent}"/>
  </g>
</svg>
SVG;

        $path = 'articles/covers/ai-' . now()->format('Ymd-His') . '-' . Str::random(8) . '.svg';
        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    private function svgHealthSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(124 86)">
  <path d="M124 92C93 54 31 76 31 133c0 73 93 117 151 174 58-57 151-101 151-174 0-57-62-79-93-41-16 20-42 20-58 0z" fill="url(#main)" opacity="0.92"/>
  <path d="M110 183h46l22-52 36 104 24-52h50" fill="none" stroke="#fff" stroke-width="18" stroke-linecap="round" stroke-linejoin="round" opacity="0.88"/>
</g>
SVG;
    }

    private function svgResearchSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(146 70)">
  <rect x="108" y="42" width="58" height="194" rx="29" fill="url(#main)" opacity="0.92"/>
  <path d="M112 132h50" stroke="#fff" stroke-width="12" stroke-linecap="round" opacity="0.76"/>
  <path d="M88 236h98l48 72c16 24-1 56-30 56H70c-29 0-46-32-30-56l48-72z" fill="{$accent}" opacity="0.82"/>
  <path d="M72 308c38-24 81 24 130 0" fill="none" stroke="#fff" stroke-width="14" stroke-linecap="round" opacity="0.82"/>
</g>
SVG;
    }

    private function svgTipSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(114 96)">
  <circle cx="136" cy="136" r="132" fill="url(#main)" opacity="0.92"/>
  <path d="M76 144l42 42 86-104" fill="none" stroke="#fff" stroke-width="26" stroke-linecap="round" stroke-linejoin="round"/>
</g>
SVG;
    }

    private function svgInfoSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(132 80)">
  <circle cx="116" cy="116" r="108" fill="url(#main)" opacity="0.90"/>
  <circle cx="116" cy="72" r="16" fill="#fff"/>
  <path d="M116 116v92" stroke="#fff" stroke-width="24" stroke-linecap="round"/>
</g>
SVG;
    }

    private function svgIdeaSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(120 66)">
  <path d="M130 32c74 0 128 56 128 125 0 44-22 78-52 101-18 14-28 32-28 55h-96c0-24-11-42-29-56-31-24-51-58-51-100C2 88 56 32 130 32z" fill="url(#main)" opacity="0.90"/>
  <path d="M86 342h88" stroke="{$primary}" stroke-width="24" stroke-linecap="round" opacity="0.72"/>
  <path d="M98 386h64" stroke="{$accent}" stroke-width="20" stroke-linecap="round" opacity="0.76"/>
  <path d="M88 154c14-34 48-54 88-46" fill="none" stroke="#fff" stroke-width="18" stroke-linecap="round" opacity="0.70"/>
</g>
SVG;
    }

    private function svgWisdomSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(104 82)">
  <path d="M64 42h244c22 0 40 18 40 40v156c0 22-18 40-40 40H174l-70 64v-64H64c-22 0-40-18-40-40V82c0-22 18-40 40-40z" fill="url(#main)" opacity="0.90"/>
  <path d="M105 128c0-22 16-39 39-39h8v42h-8c-8 0-13 5-13 13v10h21v48h-47v-74zm114 0c0-22 16-39 39-39h8v42h-8c-8 0-13 5-13 13v10h21v48h-47v-74z" fill="#fff" opacity="0.84"/>
</g>
SVG;
    }

    private function svgMotivationSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(116 76)">
  <circle cx="148" cy="148" r="132" fill="url(#main)" opacity="0.88"/>
  <path d="M148 76l22 48 52 6-38 36 10 51-46-25-46 25 10-51-38-36 52-6 22-48z" fill="#fff" opacity="0.90"/>
  <path d="M80 292c43 28 93 42 151 0" fill="none" stroke="{$accent}" stroke-width="18" stroke-linecap="round" opacity="0.45"/>
</g>
SVG;
    }

    private function svgPlateSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(42 30)">
  <circle cx="66" cy="56" r="46" fill="{$primary}" opacity="0.18"/>
  <circle cx="66" cy="56" r="30" fill="none" stroke="{$primary}" stroke-width="10"/>
  <path d="M142 24v96" stroke="{$accent}" stroke-width="12" stroke-linecap="round"/>
  <path d="M124 26v34M142 26v34M160 26v34" stroke="{$accent}" stroke-width="8" stroke-linecap="round"/>
</g>
SVG;
    }

    private function svgProteinSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(38 28)">
  <path d="M42 94c14-44 86-86 132-30 42 51-3 122-62 124-54 2-88-42-70-94z" fill="{$primary}" opacity="0.22"/>
  <path d="M80 83c28-18 67-14 91 14" fill="none" stroke="{$primary}" stroke-width="12" stroke-linecap="round"/>
  <circle cx="65" cy="56" r="16" fill="{$accent}" opacity="0.65"/>
</g>
SVG;
    }

    private function svgBloodSugarSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(54 24)">
  <path d="M70 12c34 44 62 80 62 116a62 62 0 0 1-124 0c0-36 28-72 62-116z" fill="{$accent}" opacity="0.72"/>
  <path d="M42 130h56M70 102v56" stroke="#fff" stroke-width="12" stroke-linecap="round" opacity="0.88"/>
</g>
SVG;
    }

    private function svgHeartSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(34 38)">
  <path d="M74 46C52 18 8 34 8 76c0 54 66 86 108 128 42-42 108-74 108-128 0-42-44-58-66-30-11 14-29 14-42 0z" fill="{$accent}" opacity="0.78"/>
  <path d="M60 116h32l15-36 25 72 16-36h36" fill="none" stroke="#fff" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/>
</g>
SVG;
    }

    private function svgStepsSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(44 28)">
  <ellipse cx="54" cy="60" rx="30" ry="42" transform="rotate(-18 54 60)" fill="{$primary}" opacity="0.70"/>
  <ellipse cx="136" cy="118" rx="30" ry="42" transform="rotate(18 136 118)" fill="{$accent}" opacity="0.70"/>
  <circle cx="34" cy="114" r="10" fill="{$primary}" opacity="0.54"/>
  <circle cx="160" cy="62" r="10" fill="{$accent}" opacity="0.54"/>
</g>
SVG;
    }

    private function svgMoonSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(42 24)">
  <path d="M126 20c-42 20-68 62-68 110 0 38 18 72 45 94C52 214 14 170 14 118 14 52 68 0 126 20z" fill="{$accent}" opacity="0.76"/>
  <circle cx="154" cy="70" r="9" fill="{$primary}" opacity="0.42"/>
  <circle cx="188" cy="112" r="7" fill="{$primary}" opacity="0.32"/>
</g>
SVG;
    }

    private function svgWaterSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(55 24)">
  <path d="M72 10c34 46 66 86 66 126a66 66 0 0 1-132 0c0-40 32-80 66-126z" fill="{$accent}" opacity="0.74"/>
  <path d="M42 132c10 24 30 36 60 32" fill="none" stroke="#fff" stroke-width="12" stroke-linecap="round" opacity="0.78"/>
</g>
SVG;
    }

    private function svgLeafSymbol(string $primary, string $accent): string
    {
        return <<<SVG
<g transform="translate(38 30)">
  <path d="M150 18C74 21 25 68 18 144c78 8 139-41 132-126z" fill="{$primary}" opacity="0.25"/>
  <path d="M40 148c42-50 72-78 112-116" fill="none" stroke="{$primary}" stroke-width="12" stroke-linecap="round"/>
  <path d="M92 94c12 0 28 0 48 12" fill="none" stroke="{$accent}" stroke-width="9" stroke-linecap="round" opacity="0.62"/>
</g>
SVG;
    }

    private function articleOptionalPayload(array $values): array
    {
        $payload = [];

        foreach ($values as $column => $value) {
            if (! Schema::hasColumn('articles', $column)) {
                continue;
            }

            $payload[$column] = $value;
        }

        return $payload;
    }

    private function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);

        if (blank($slug)) {
            $slug = 'article-' . Str::random(8);
        }

        $originalSlug = $slug;
        $counter = 1;

        while (
            Article::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
