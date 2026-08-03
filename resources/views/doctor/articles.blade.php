@extends('layouts.doctor')

@php
    $pageTitle = 'مقالاتي';
    $activePage = 'articles';
    $statusPill = ['published' => 'green', 'pending_review' => 'amber', 'rejected' => 'rose', 'draft' => 'muted'];
    $shouldOpenForm = session('open_new_article_form') || $errors->any() || old('title');
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('front/css/doctor/requests.css') }}">

    <style>
        /* ============================================================ */
        /* الصفحة بالكامل - ستايل فخم وحديث                              */
        /* ============================================================ */

        .articles-page .ddash-section-head { margin-bottom: 20px; }

        /* تحسين شكل الإحصائيات */
        .articles-page .ddash-stats-grid .ddash-stat-card {
            padding: 18px 10px;
        }
        .articles-page .ddash-stats-grid .ddash-stat-card__num {
            font-size: 1.8rem;
            margin-bottom: 2px;
        }
        .articles-page .ddash-stats-grid .ddash-stat-card__label {
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* ============================================================ */
        /* زر إضافة مقال جديد (صغير وأنيق)                             */
        /* ============================================================ */
        .art-header-actions {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 24px;
        }

        .art-btn-add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: var(--d-green);
            color: #fff !important;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(29,158,117,.25);
            text-decoration: none;
        }
        .art-btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29,158,117,.35);
            background: #0f7a57;
        }
        .art-btn-add i { width: 18px; height: 18px; }

        /* ============================================================ */
        /* الفورم (يظهر عند الضغط على الزر)                             */
        /* ============================================================ */
        .art-form-container {
            display: none; /* مخفي افتراضياً */
            margin-bottom: 30px;
            animation: slideDown 0.3s ease forwards;
        }
        .art-form-container.is-open { display: block; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .art-form-card {
            background: var(--d-card);
            border: 1px solid var(--d-border);
            border-radius: 20px;
            padding: 28px;
            box-shadow: var(--d-shadow-hover);
            position: relative;
        }

        .art-form-close {
            position: absolute;
            top: 16px;
            left: 20px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--d-soft-bg);
            border: none;
            color: var(--d-muted);
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: 0.2s;
        }
        .art-form-close:hover {
            background: #ef4444;
            color: #fff;
            transform: rotate(90deg);
        }
        .art-form-close i { width: 16px; height: 16px; }

        .art-form-card h3 {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--d-title);
            margin: 0 0 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .art-form-card h3 i { color: var(--d-green); width: 20px; height: 20px; }
        .art-form-card > p {
            font-size: 0.85rem;
            color: var(--d-muted);
            font-weight: 600;
            margin-bottom: 18px;
        }

        /* شريط AI */
        .art-ai-row {
            display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
            background: rgba(29,158,117,.06); border: 1px dashed rgba(29,158,117,.3);
            border-radius: 14px; padding: 10px 16px; margin-bottom: 20px;
        }
        .art-ai-row input {
            flex: 1; min-width: 160px; padding: 8px 12px; border-radius: 10px;
            border: 1px solid var(--d-border); background: var(--d-card);
            font-weight: 600; font-size: 0.85rem; color: var(--d-title);
        }
        .art-ai-row input:focus { outline: none; border-color: var(--d-green); }
        .art-ai-row button {
            padding: 8px 16px; border-radius: 10px; background: var(--d-green);
            color: #fff; border: 0; font-weight: 700; font-size: 0.85rem; cursor: pointer;
            transition: 0.2s;
        }
        .art-ai-row button:hover { background: #0f7a57; }

        /* نموذج الحقول */
        .art-form-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
        }
        .art-form-grid .full-width { grid-column: 1 / -1; }

        .art-form-group { display: flex; flex-direction: column; gap: 4px; }
        .art-form-group label { font-size: 0.75rem; font-weight: 700; color: var(--d-muted); }
        .art-form-group input,
        .art-form-group select,
        .art-form-group textarea {
            padding: 10px 14px; border-radius: 12px; border: 1px solid var(--d-border);
            background: var(--d-card); font-size: 0.9rem; font-weight: 600; color: var(--d-title);
            font-family: inherit; width: 100%; transition: 0.2s;
        }
        .art-form-group input:focus,
        .art-form-group select:focus,
        .art-form-group textarea:focus { outline: none; border-color: var(--d-green); }
        .art-form-group textarea { resize: vertical; min-height: 70px; }
        .art-form-group textarea.content-area { min-height: 140px; }

        .art-form-actions {
            display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px;
            border-top: 1px solid var(--d-border); padding-top: 16px;
        }
        .art-btn {
            padding: 10px 24px; border-radius: 12px; border: 0; font-weight: 800; font-size: 0.85rem;
            cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px;
        }
        .art-btn-submit { background: var(--d-green); color: #fff; }
        .art-btn-submit:hover { background: #0f7a57; transform: translateY(-2px); }
        .art-btn-draft { background: var(--d-soft-bg); color: var(--d-text); }
        .art-btn-draft:hover { background: var(--d-border); }


        /* ============================================================ */
        /* قائمة المقالات - بطاقات فخمة                                */
        /* ============================================================ */
        .art-list { display: flex; flex-direction: column; gap: 16px; }

        .art-card {
            background: var(--d-card); border: 1px solid var(--d-border);
            border-radius: 18px; padding: 18px 22px;
            box-shadow: var(--d-shadow); transition: all 0.2s ease;
            cursor: pointer;
        }
        .art-card:hover {
            box-shadow: var(--d-shadow-hover);
            border-color: var(--d-green);
            transform: translateY(-2px);
        }

        .art-card-top {
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
        }
        .art-card-info { display: flex; align-items: center; gap: 14px; flex: 1; min-width: 0; }

        .art-thumb {
            width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
            background: var(--d-soft-bg); display: grid; place-items: center;
            font-weight: 800; font-size: 1.1rem; color: var(--d-green);
            overflow: hidden;
        }
        .art-thumb img { width: 100%; height: 100%; object-fit: cover; }

        .art-title { font-size: 0.95rem; font-weight: 800; color: var(--d-title); display: block; margin-bottom: 2px; }
        .art-meta { font-size: 0.75rem; color: var(--d-muted); font-weight: 600; display: flex; align-items: center; gap: 8px; }

        .art-status-badge {
            font-size: 0.65rem; font-weight: 700; padding: 4px 12px; border-radius: 50px;
            white-space: nowrap;
        }
        .art-status-badge.green { background: rgba(29,158,117,.12); color: var(--d-green); }
        .art-status-badge.amber { background: rgba(245,158,11,.12); color: #d97706; }
        .art-status-badge.rose { background: rgba(239,68,68,.12); color: #dc2626; }
        .art-status-badge.muted { background: var(--d-soft-bg); color: var(--d-muted); }

        .art-chevron { color: var(--d-muted); transition: transform 0.2s; width: 18px; height: 18px; flex-shrink: 0; }

        /* عند فتح المقال (Edit Mode) */
        .art-card details > summary { list-style: none; cursor: pointer; display: flex; align-items: center; justify-content: space-between; }
        .art-card details > summary::-webkit-details-marker { display: none; }
        .art-card details > summary::marker { content: ""; }
        .art-card details[open] .art-chevron { transform: rotate(180deg); }
        .art-card details[open] .art-card-top { border-bottom: 1px solid var(--d-border); padding-bottom: 14px; margin-bottom: 16px; }

        /* قسم التعديل داخل البطاقة */
        .art-edit-section {
            padding-top: 4px; display: flex; flex-direction: column; gap: 16px;
        }

        .art-rejection-note {
            background: rgba(239,68,68,.06); border: 1px solid rgba(239,68,68,.2);
            border-radius: 12px; padding: 12px 16px; font-size: 0.85rem; font-weight: 700; color: #dc2626;
            display: flex; align-items: center; gap: 8px; margin-bottom: 4px;
        }
        .art-rejection-note i { width: 18px; height: 18px; }

        .art-edit-actions {
            display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
            border-top: 1px solid var(--d-border); padding-top: 16px;
        }
        .art-btn-delete {
            padding: 8px 16px; border-radius: 10px; background: transparent;
            color: #dc2626; border: 1px solid rgba(239,68,68,.2); font-weight: 700; font-size: 0.8rem;
            cursor: pointer; transition: 0.2s; margin-right: auto;
        }
        .art-btn-delete:hover { background: #dc2626; color: #fff; border-color: #dc2626; }

        @media (max-width: 768px) {
            .art-form-grid { grid-template-columns: 1fr; }
            .art-form-close { top: 12px; left: 14px; width: 28px; height: 28px; }
            .art-card-top { flex-wrap: wrap; }
            .art-card-info { width: 100%; }
            .art-status-badge { margin-right: auto; }
        }
    </style>
@endpush

@section('content')
<section class="ddash articles-page">

    {{-- الهيدر --}}
    <div class="ddash-section-head">
        <div>
            <h2>مقالاتي</h2>
            <span>إدارة مقالاتك ومتابعة حالة الاعتماد</span>
        </div>
    </div>

    {{-- رسائل التنبيه --}}
    @if (session('success'))
        <div class="doctor-status" style="margin-bottom:14px">{{ session('success') }}</div>
    @endif
    @if (session('ai_notice'))
        <div class="doctor-status warn" style="margin-bottom:14px">{{ session('ai_notice') }}</div>
    @endif
    @if ($errors->any())
        <div class="doctor-status danger" style="margin-bottom:14px">{{ $errors->first() }}</div>
    @endif

    {{-- الإحصائيات --}}
    <div class="ddash-stats-grid" style="grid-template-columns:repeat(4,1fr)">
        <div class="ddash-stat-card ddash-accent--green">
            <div class="ddash-stat-card__num">{{ $counts['published'] }}</div>
            <div class="ddash-stat-card__label">منشورة</div>
        </div>
        <div class="ddash-stat-card ddash-accent--amber">
            <div class="ddash-stat-card__num">{{ $counts['pending_review'] }}</div>
            <div class="ddash-stat-card__label">قيد المراجعة</div>
        </div>
        <div class="ddash-stat-card ddash-accent--blue">
            <div class="ddash-stat-card__num">{{ $counts['draft'] }}</div>
            <div class="ddash-stat-card__label">مسودات</div>
        </div>
        <div class="ddash-stat-card ddash-accent--rose">
            <div class="ddash-stat-card__num">{{ $counts['rejected'] }}</div>
            <div class="ddash-stat-card__label">مرفوضة</div>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- زر إضافة مقال جديد (صغير وأنيق)            --}}
    {{-- ========================================== --}}
    <div class="art-header-actions">
        <button class="art-btn-add" id="toggleArticleForm">
            <i data-lucide="plus"></i> مقال جديد
        </button>
    </div>

    {{-- ========================================== --}}
    {{-- فورم إضافة مقال (يظهر عند الضغط)           --}}
    {{-- ========================================== --}}
    <div class="art-form-container" id="articleFormContainer">
        <div class="art-form-card">
            <button class="art-form-close" id="closeArticleForm" title="إغلاق">
                <i data-lucide="x"></i>
            </button>

            <h3><i data-lucide="pen-line"></i> كتابة مقال جديد</h3>
            <p>املأ الحقول أدناه، أو استخدم الذكاء الاصطناعي لاقتراح مسودة أولية.</p>

            {{-- شريط AI --}}
            <form method="POST" action="{{ route('doctor.articles.ai-draft') }}" class="art-ai-row">
                @csrf
                <i data-lucide="sparkles" style="color:var(--d-green); width:18px; height:18px;"></i>
                <input type="text" name="topic" placeholder="اكتب موضوعاً لاقتراح مسودة AI..." required>
                <button type="submit">اقترح مسودة</button>
            </form>

            {{-- الفورم الأساسي --}}
            <form method="POST" action="{{ route('doctor.articles.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="art-form-grid">
                    <div class="art-form-group full-width">
                        <label>عنوان المقال</label>
                        <input type="text" name="title" value="{{ old('title') }}" placeholder="مثلاً: فوائد الصيام المتقطع" required>
                    </div>

                    <div class="art-form-group full-width">
                        <label>ملخص قصير (يظهر في القائمة)</label>
                        <textarea name="excerpt" placeholder="نبذة مختصرة عن المقال..." required>{{ old('excerpt') }}</textarea>
                    </div>

                    <div class="art-form-group full-width">
                        <label>محتوى المقال الكامل</label>
                        <textarea name="content" class="content-area" placeholder="اكتب مقالك هنا..." required>{{ old('content') }}</textarea>
                    </div>

                    <div class="art-form-group">
                        <label>التصنيف</label>
                        <select name="article_category_id">
                            <option value="">بدون تصنيف</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('article_category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="art-form-group">
                        <label>التخصص الطبي</label>
                        <select name="specialty_id">
                            <option value="">بدون تخصص</option>
                            @foreach ($specialties as $specialty)
                                <option value="{{ $specialty->id }}" @selected(old('specialty_id') == $specialty->id)>{{ $specialty->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="art-form-group">
                        <label>مدة القراءة (دقيقة)</label>
                        <input type="number" name="reading_minutes" min="1" max="120" value="{{ old('reading_minutes', 3) }}" required>
                    </div>

                    <div class="art-form-group">
                        <label>صورة الغلاف</label>
                        <input type="file" name="cover_image" accept="image/png,image/jpeg,image/webp" style="padding: 8px;">
                    </div>
                </div>

                <div class="art-form-actions">
                    <button type="submit" name="action" value="draft" class="art-btn art-btn-draft">حفظ كمسودة</button>
                    <button type="submit" name="action" value="submit" class="art-btn art-btn-submit">إرسال للمراجعة</button>
                </div>
            </form>
        </div>
    </div>


    {{-- ========================================== --}}
    {{-- قائمة المقالات (بطاقات فخمة)              --}}
    {{-- ========================================== --}}
    @if ($articles->isEmpty())
        <div class="ddash-empty"><i data-lucide="newspaper"></i><p>ما كتبت أي مقال لسا. اضغط على زر "مقال جديد" لبدء أول مقال.</p></div>
    @else
        <div class="art-list">
            @foreach ($articles as $article)
                <div class="art-card">
                    <details>
                        <summary>
                            <div class="art-card-top">
                                <div class="art-card-info">
                                    {{-- الصورة --}}
                                    <div class="art-thumb">
                                        @if ($article->cover_image)
                                            <img src="{{ $article->cover_image_url }}" alt="">
                                        @else
                                            {{ mb_substr($article->title, 0, 1) }}
                                        @endif
                                    </div>

                                    {{-- العنوان والتفاصيل --}}
                                    <div>
                                        <span class="art-title">{{ $article->title }}</span>
                                        <div class="art-meta">
                                            <span>{{ $article->reading_time_label }}</span>
                                            <span>•</span>
                                            <span>{{ $article->created_at->locale('ar')->translatedFormat('j M Y') }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- حالة المقال --}}
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <span class="art-status-badge {{ $statusPill[$article->status] ?? 'muted' }}">
                                        {{ $article->status_label }}
                                    </span>
                                    <i data-lucide="chevron-down" class="art-chevron"></i>
                                </div>
                            </div>
                        </summary>

                        {{-- قسم التعديل (يظهر عند فتح البطاقة) --}}
                        <div class="art-edit-section">

                            {{-- سبب الرفض إن وجد --}}
                            @if ($article->status === 'rejected' && $article->rejection_reason)
                                <div class="art-rejection-note">
                                    <i data-lucide="alert-circle"></i>
                                    سبب الرفض: {{ $article->rejection_reason }}
                                </div>
                            @endif

                            {{-- نموذج التعديل --}}
                            <form method="POST" action="{{ route('doctor.articles.update', $article) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="art-form-grid">
                                    <div class="art-form-group full-width">
                                        <label>العنوان</label>
                                        <input type="text" name="title" value="{{ $article->title }}" required>
                                    </div>
                                    <div class="art-form-group full-width">
                                        <label>الملخص</label>
                                        <textarea name="excerpt" required>{{ $article->excerpt }}</textarea>
                                    </div>
                                    <div class="art-form-group full-width">
                                        <label>المحتوى</label>
                                        <textarea name="content" class="content-area" required>{{ $article->content }}</textarea>
                                    </div>
                                    <div class="art-form-group">
                                        <label>التصنيف</label>
                                        <select name="article_category_id">
                                            <option value="">بدون تصنيف</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" @selected($article->article_category_id == $category->id)>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="art-form-group">
                                        <label>التخصص</label>
                                        <select name="specialty_id">
                                            <option value="">بدون تخصص</option>
                                            @foreach ($specialties as $specialty)
                                                <option value="{{ $specialty->id }}" @selected($article->specialty_id == $specialty->id)>{{ $specialty->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="art-form-group">
                                        <label>مدة القراءة (دقيقة)</label>
                                        <input type="number" name="reading_minutes" min="1" max="120" value="{{ $article->reading_minutes }}" required>
                                    </div>
                                    <div class="art-form-group">
                                        <label>تغيير صورة الغلاف</label>
                                        <input type="file" name="cover_image" accept="image/png,image/jpeg,image/webp" style="padding: 8px;">
                                    </div>
                                </div>

                                <div class="art-edit-actions">
                                    <button type="submit" name="action" value="save" class="art-btn art-btn-draft">حفظ التعديلات</button>
                                    @if ($article->status !== 'published')
                                        <button type="submit" name="action" value="submit" class="art-btn art-btn-submit">إرسال للمراجعة</button>
                                    @endif

                                    @if (in_array($article->status, ['draft', 'rejected']))
                                        <form method="POST" action="{{ route('doctor.articles.destroy', $article) }}" style="display:inline; margin:0;"
                                              onsubmit="return confirm('هل أنت متأكد من حذف هذا المقال نهائياً؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="art-btn-delete">
                                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i> حذف المقال
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </details>
                </div>
            @endforeach
        </div>
    @endif

</section>

{{-- ========================================== --}}
{{-- سكريبت التحكم في إظهار وإخفاء الفورم     --}}
{{-- ========================================== --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleArticleForm');
    const formContainer = document.getElementById('articleFormContainer');
    const closeBtn = document.getElementById('closeArticleForm');

    // إذا كان هناك أخطاء أو تم فتح الفورم سابقاً، نبقيه مفتوحاً
    @if($shouldOpenForm)
        formContainer.classList.add('is-open');
    @endif

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            formContainer.classList.toggle('is-open');
            // إذا تم الفتح، نقوم بتنظيف أي أخطاء سابقة
            if(formContainer.classList.contains('is-open')) {
                // Scroll smoothly to the form
                formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            formContainer.classList.remove('is-open');
        });
    }
});
</script>
@endpush
@endsection
