@extends('layouts.patient')

@php
    $pageTitle = 'تحليل الوجبات';
    $activePage = $activePage ?? 'calories';

    $summary = $caloriesSummary ?? [];
    $todayMeals = collect($todayMeals ?? []);
    $recentAnalyses = collect($recentAnalyses ?? []);
    $aiMealDraft = $aiMealDraft ?? session('ai_meal_draft');
    $weightLogs = collect($weightLogs ?? []);

    try {
        $selectedDate = !empty($selectedDate ?? null)
            ? \Carbon\Carbon::parse($selectedDate)->startOfDay()
            : (request('date') ? \Carbon\Carbon::parse(request('date'))->startOfDay() : now()->startOfDay());
    } catch (\Throwable $e) {
        $selectedDate = now()->startOfDay();
    }

    $weekStart = $selectedDate->copy()->startOfWeek(\Carbon\CarbonInterface::SATURDAY);
    $weekDays = collect(range(0, 6))->map(fn ($index) => $weekStart->copy()->addDays($index));

    $caloriesPageUrl = \Illuminate\Support\Facades\Route::has('patient.calories')
        ? route('patient.calories')
        : url('/patient/calories');

    $previousWeekUrl = $caloriesPageUrl . '?date=' . $selectedDate->copy()->subWeek()->toDateString();
    $nextWeekUrl = $caloriesPageUrl . '?date=' . $selectedDate->copy()->addWeek()->toDateString();

    $arabicDays = [
        0 => 'الأحد',
        1 => 'الإثنين',
        2 => 'الثلاثاء',
        3 => 'الأربعاء',
        4 => 'الخميس',
        5 => 'الجمعة',
        6 => 'السبت',
    ];

    $arabicDaysShort = [
        0 => 'أحد',
        1 => 'إثن',
        2 => 'ثلا',
        3 => 'أرب',
        4 => 'خمي',
        5 => 'جمع',
        6 => 'سبت',
    ];

    $summaryTarget = data_get($summary, 'target');

    $patientDailyGoal = data_get($patient ?? [], 'profile.daily_calorie_goal')
        ?? data_get($patient ?? [], 'profile.doctor_calorie_goal')
        ?? data_get($patient ?? [], 'profile.approved_calorie_goal')
        ?? data_get($patient ?? [], 'profile.suggested_calorie_goal')
        ?? data_get($patient ?? [], 'profile.ai_calorie_goal');

    $hasDailyGoal = (!empty($summaryTarget) && (int) $summaryTarget > 0)
        || (!empty($patientDailyGoal) && (int) $patientDailyGoal > 0);

    $targetCalories = $hasDailyGoal
        ? (int) ($summaryTarget ?? $patientDailyGoal)
        : null;

    $goalStatus = data_get($summary, 'goal_status', 'pending');

    $consumedCalories = (int) (
        data_get($summary, 'consumed')
        ?? $todayMeals->sum(fn ($meal) => (int) data_get($meal, 'calories', 0))
    );

    $protein = (int) (
        data_get($summary, 'protein')
        ?? $todayMeals->sum(fn ($meal) => (int) data_get($meal, 'protein', 0))
    );

    $carbs = (int) (
        data_get($summary, 'carbs')
        ?? $todayMeals->sum(fn ($meal) => (int) data_get($meal, 'carbs', 0))
    );

    $fat = (int) (
        data_get($summary, 'fat')
        ?? $todayMeals->sum(fn ($meal) => (int) data_get($meal, 'fat', 0))
    );

    $proteinTarget = (int) (data_get($summary, 'protein_target') ?? 90);
    $carbsTarget = (int) (data_get($summary, 'carbs_target') ?? 220);
    $fatTarget = (int) (data_get($summary, 'fat_target') ?? 65);

    $remainingCalories = $hasDailyGoal
        ? max(0, $targetCalories - $consumedCalories)
        : null;

    $caloriesPercent = ($hasDailyGoal && $targetCalories > 0)
        ? max(0, min(100, (int) round(($consumedCalories / $targetCalories) * 100)))
        : 0;

    $proteinPercent = $proteinTarget > 0
        ? max(0, min(100, (int) round(($protein / $proteinTarget) * 100)))
        : 0;

    $carbsPercent = $carbsTarget > 0
        ? max(0, min(100, (int) round(($carbs / $carbsTarget) * 100)))
        : 0;

    $fatPercent = $fatTarget > 0
        ? max(0, min(100, (int) round(($fat / $fatTarget) * 100)))
        : 0;

    $analyzeRoute = \Illuminate\Support\Facades\Route::has('patient.calories.analyze')
        ? route('patient.calories.analyze')
        : '#';

    $confirmRoute = \Illuminate\Support\Facades\Route::has('patient.calories.confirm')
        ? route('patient.calories.confirm')
        : '#';

    $destroyRouteName = \Illuminate\Support\Facades\Route::has('patient.calories.destroy')
        ? 'patient.calories.destroy'
        : null;

    $updateRouteName = \Illuminate\Support\Facades\Route::has('patient.calories.update')
        ? 'patient.calories.update'
        : null;

    $mealTypeArabic = [
        'breakfast' => 'الإفطار',
        'lunch' => 'الغداء',
        'dinner' => 'العشاء',
        'snack' => 'سناك',
    ];

    $mealTypeLabel = [
        'breakfast' => 'Breakfast',
        'lunch' => 'Lunch',
        'dinner' => 'Dinner',
        'snack' => 'Snack',
    ];

    $mealTypeEmoji = [
        'breakfast' => '🥣',
        'lunch' => '🥗',
        'dinner' => '🍽️',
        'snack' => '🍎',
    ];

    $mealTypeIcon = [
        'breakfast' => 'sun',
        'lunch' => 'utensils',
        'dinner' => 'moon',
        'snack' => 'cookie',
    ];

    $draftCalories = (int) data_get($aiMealDraft ?? [], 'calories', 0);
    $draftProtein = (int) data_get($aiMealDraft ?? [], 'protein', 0);
    $draftCarbs = (int) data_get($aiMealDraft ?? [], 'carbs', 0);
    $draftFat = (int) data_get($aiMealDraft ?? [], 'fat', 0);
    $draftConfidence = (int) data_get($aiMealDraft ?? [], 'confidence', 0);
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/calories.css') }}">
@endpush

@section('content')
<section class="cal-page" id="calAiMealStudio">
    @if (session('success'))
        <div class="cal-alert cal-alert-success">
            <i data-lucide="badge-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="cal-alert cal-alert-error">
            <i data-lucide="circle-alert"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="cal-alert cal-alert-error">
            <i data-lucide="circle-alert"></i>
            <span>
                @if ($errors->has('weight_kg') || $errors->has('target_weight_kg'))
                    {{ $errors->first('weight_kg') ?: $errors->first('target_weight_kg') }}
                @else
                    راجع بيانات الوجبة قبل التحليل أو الحفظ.
                @endif
            </span>
        </div>
    @endif

    <section class="cal-hero">
        <div class="cal-hero-copy">
            <span class="cal-kicker">
                <i data-lucide="sparkles"></i>
                AI Meal Analysis
            </span>

            <h1>حلّل وجبتك بالذكاء الاصطناعي</h1>

            <p>
                ارفع صورة الوجبة أو اكتب وصفها، واتزان يحلل السعرات والبروتين والكربوهيدرات والدهون.
                بعدها راجع النتيجة وعدّل الكمية ثم احفظها في سجل المتابعة.
            </p>

            <div class="cal-hero-actions">
                <a href="#calAnalyzerCard" class="cal-primary-btn">
                    <i data-lucide="scan-line"></i>
                    ابدأ تحليل وجبة
                </a>

                <a href="#calSavedMeals" class="cal-secondary-btn">
                    <i data-lucide="list-checks"></i>
                    وجبات اليوم
                </a>
            </div>
        </div>

        <div class="cal-hero-visual">
            <div class="cal-food-preview">
                <div class="cal-food-image">
                    <span>🥗</span>
                </div>

                <div class="cal-food-info">
                    <span>AI Estimate</span>
                    <strong>{{ $draftCalories ?: '—' }} Cal</strong>
                    <small>نتيجة تقديرية قبل الحفظ</small>
                </div>
            </div>

            <div class="cal-hero-mini-card">
                <i data-lucide="badge-check"></i>
                <div>
                    <strong>{{ $draftConfidence ?: 0 }}%</strong>
                    <span>ثقة التحليل</span>
                </div>
            </div>
        </div>
    </section>

    <section class="cal-dashboard-grid">
        <article class="cal-card cal-analyzer-card" id="calAnalyzerCard">
            <div class="cal-card-head">
                <div>
                    <span>Analyze New Meal</span>
                    <h2>تحليل وجبة جديدة</h2>
                    <p>النتيجة لا تُحفظ مباشرة، راجعها ثم اضغط اعتماد وحفظ.</p>
                </div>

                <i data-lucide="camera"></i>
            </div>

            <form action="{{ $analyzeRoute }}" method="POST" enctype="multipart/form-data" class="cal-ai-form" data-cal-form>
                @csrf

                <input type="hidden" name="meal_date" value="{{ $selectedDate->toDateString() }}">

                <div class="cal-tabs" data-cal-tabs>
                    <button type="button" class="is-active" data-cal-tab="photo">
                        <i data-lucide="image-plus"></i>
                        صورة الوجبة
                    </button>

                    <button type="button" data-cal-tab="text">
                        <i data-lucide="message-square-text"></i>
                        وصف نصي
                    </button>
                </div>

                <div class="cal-tab-panels">
                    <div class="cal-tab-panel is-active" data-cal-panel="photo">
                        <label class="cal-upload-zone">
                            <input type="file" name="meal_photo" accept="image/*" data-cal-file>

                            <span class="cal-upload-icon">
                                <i data-lucide="cloud-upload"></i>
                            </span>

                            <strong data-cal-file-label>ارفع صورة الوجبة</strong>
                            <small>حاليًا أضف وصفًا نصيًا مع الصورة حتى يعمل التحليل المجاني بدقة أفضل.</small>

                            <div class="cal-image-preview" data-cal-preview hidden></div>
                        </label>
                    </div>

                    <div class="cal-tab-panel" data-cal-panel="text">
                        <label class="cal-text-zone">
                            <span>اكتب وصف الوجبة</span>
                            <textarea
                                name="meal_text"
                                rows="5"
                                placeholder="مثال: صدر دجاج مشوي مع أرز بني وسلطة خضراء بدون صوص..."
                                data-cal-autogrow
                            >{{ old('meal_text') }}</textarea>
                        </label>
                    </div>
                </div>

                <div class="cal-form-footer">
                    <label class="cal-select-field">
                        <i data-lucide="utensils"></i>
                        <select name="meal_type">
                            <option value="breakfast">الإفطار</option>
                            <option value="lunch" selected>الغداء</option>
                            <option value="dinner">العشاء</option>
                            <option value="snack">سناك</option>
                        </select>
                    </label>

                    <button type="submit" class="cal-primary-btn">
                        <i data-lucide="sparkles"></i>
                        تحليل الوجبة
                    </button>
                </div>
            </form>

            <div class="cal-safe-note">
                <i data-lucide="shield-check"></i>
                <span>لن يتم حفظ أي نتيجة إلا بعد اعتمادك لها، وبعدها تظهر في سجل الوجبات.</span>
            </div>
        </article>

        <article class="cal-card cal-summary-card">
            <div class="cal-summary-top">
                <div>
                    <span>Today's Calories</span>
                    <h2>السعرات ووجبات اليوم</h2>
                    <p>اختر اليوم وشاهد السعرات والوجبات المحفوظة له.</p>
                </div>

                <i data-lucide="activity"></i>
            </div>

            <div class="cal-date-switcher">
                <a href="{{ $previousWeekUrl }}" class="cal-date-arrow" aria-label="الأسبوع السابق">
                    <i data-lucide="chevron-right"></i>
                </a>

                <div class="cal-current-date">
                    <span>
                        <i data-lucide="calendar-days"></i>
                    </span>

                    <div>
                        <strong>{{ $selectedDate->format('d') }} {{ $selectedDate->translatedFormat('F') }}</strong>
                        <small>{{ $arabicDays[$selectedDate->dayOfWeek] ?? $selectedDate->translatedFormat('l') }}</small>
                    </div>
                </div>

                <a href="{{ $nextWeekUrl }}" class="cal-date-arrow" aria-label="الأسبوع التالي">
                    <i data-lucide="chevron-left"></i>
                </a>
            </div>

            <div class="cal-week-compact">
                @foreach ($weekDays as $day)
                    @php
                        $isSelectedDay = $day->isSameDay($selectedDate);
                        $isToday = $day->isToday();
                        $dayUrl = $caloriesPageUrl . '?date=' . $day->toDateString();
                    @endphp

                    <a
                        href="{{ $dayUrl }}"
                        class="cal-week-chip {{ $isSelectedDay ? 'is-active' : '' }} {{ $isToday ? 'is-today' : '' }}"
                    >
                        <span>{{ $arabicDaysShort[$day->dayOfWeek] ?? $day->translatedFormat('D') }}</span>
                        <strong>{{ $day->format('d') }}</strong>
                    </a>
                @endforeach
            </div>

            <div class="cal-calorie-focus">
                <div class="cal-calorie-ring" style="--value: {{ $caloriesPercent }};">
                    <div>
                        <strong>{{ $consumedCalories }}</strong>
                        <span>سعرة محفوظة</span>
                    </div>
                </div>

                <div class="cal-calorie-side">
                    <div>
                        <span>المتبقي</span>
                        <strong>{{ $hasDailyGoal ? $remainingCalories : '—' }}</strong>
                    </div>

                    <div>
                        <span>الهدف</span>
                        <strong>{{ $hasDailyGoal ? $targetCalories : '—' }}</strong>
                    </div>
                </div>
            </div>

            <div class="cal-macro-compact">
                <div class="cal-macro-item cal-ring-protein" style="--value: {{ $proteinPercent }};">
                    <div class="cal-mini-circle">
                        <strong>{{ $proteinPercent }}%</strong>
                    </div>

                    <div>
                        <span>Protein</span>
                        <small>{{ $protein }}/{{ $proteinTarget }}g</small>
                    </div>
                </div>

                <div class="cal-macro-item cal-ring-carbs" style="--value: {{ $carbsPercent }};">
                    <div class="cal-mini-circle">
                        <strong>{{ $carbsPercent }}%</strong>
                    </div>

                    <div>
                        <span>Carbs</span>
                        <small>{{ $carbs }}/{{ $carbsTarget }}g</small>
                    </div>
                </div>

                <div class="cal-macro-item cal-ring-fat" style="--value: {{ $fatPercent }};">
                    <div class="cal-mini-circle">
                        <strong>{{ $fatPercent }}%</strong>
                    </div>

                    <div>
                        <span>Fat</span>
                        <small>{{ $fat }}/{{ $fatTarget }}g</small>
                    </div>
                </div>
            </div>

            <div class="cal-goal-note">
                <i data-lucide="{{ $hasDailyGoal ? 'target' : 'info' }}"></i>

                <div>
                    @if ($hasDailyGoal && $goalStatus === 'approved')
                        <strong>هدف هذا اليوم معتمد من الطبيب</strong>
                        <span>هذا الهدف خاص بالتاريخ المحدد فقط، وقد يختلف عن باقي الأيام.</span>
                    @elseif ($hasDailyGoal && $goalStatus === 'suggested')
                        <strong>هدف هذا اليوم مقترح مبدئيًا</strong>
                        <span>هذا الهدف يحتاج مراجعة الطبيب أو اعتماده لاحقًا.</span>
                    @else
                        <strong>لم يحدد الطبيب هدفًا لهذا اليوم</strong>
                        <span>اختر يومًا آخر أو انتظر إضافة هدف السعرات لهذا التاريخ.</span>
                    @endif

                    @if (data_get($summary, 'goal_note'))
                        <p class="cal-ai-note">ملاحظة طبيبك: {{ data_get($summary, 'goal_note') }}</p>
                    @endif
                </div>
            </div>
        </article>
    </section>

    {{-- ══════════ وزني — تسجيل حقيقي من المريض نفسه ══════════ --}}
    <section class="cal-card cal-weight-section">
        <div class="cal-section-head">
            <div>
                <span>My Weight</span>
                <h2>وزني</h2>
                <p>سجّلي وزنك بشكل دوري حتى يتابع طبيبك تقدّمك بدقة.</p>
            </div>
            <i data-lucide="scale"></i>
        </div>

        {{-- الوزن الحالي + نموذج الإضافة --}}
        <div class="cal-weight-stat">
            <div class="cal-weight-stat__value">
                @if ($weightLogs->isNotEmpty())
                    <strong>{{ $weightLogs->last()->weight_kg }}</strong>
                    <span>كغم</span>
                @else
                    <strong>—</strong>
                @endif
            </div>
            <p class="cal-weight-stat__meta">
                @if ($weightLogs->isNotEmpty())
                    آخر قياس بتاريخ {{ \Carbon\Carbon::parse($weightLogs->last()->logged_date)->locale('ar')->translatedFormat('j F Y') }}
                @else
                    لسا ما سجّلتي أي وزن
                @endif
            </p>

            <form method="POST" action="{{ route('patient.weight.log') }}" class="cal-weight-add-form" data-weight-add-form novalidate>
                @csrf
                <input type="number" step="0.1" min="25" max="350" name="weight_kg" placeholder="الوزن (كغم)" required data-weight-input>
                <input type="date" name="logged_date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}">
                <button type="submit" class="cal-primary-btn">
                    <i data-lucide="plus"></i>
                    تسجيل
                </button>
                <span class="cal-weight-inline-error" data-weight-error hidden></span>
            </form>
        </div>

        {{-- هدف الوزن --}}
        @php
            $targetWeight = data_get($patient ?? [], 'profile.target_weight_kg');
        @endphp
        <div class="cal-weight-goal">
            <div class="cal-weight-goal__icon"><i data-lucide="target"></i></div>
            <div class="cal-weight-goal__text">
                @if ($targetWeight)
                    <strong>الهدف: {{ $targetWeight }} كغم</strong>
                    @if ($weightLogs->isNotEmpty())
                        @php $remaining = round($weightLogs->last()->weight_kg - $targetWeight, 1); @endphp
                        <span>
                            @if ($remaining != 0)
                                باقي {{ abs($remaining) }} كغم {{ $remaining > 0 ? 'لتنزلي' : 'لتزيدي' }}
                            @else
                                وصلتي لهدفك! 🎉
                            @endif
                        </span>
                    @endif
                @else
                    <strong>ما حدّدتي هدف وزن بعد</strong>
                    <span>حدّديه من الخانة جنب</span>
                @endif
            </div>
            <form method="POST" action="{{ route('patient.weight.goal') }}" class="cal-weight-goal__form">
                @csrf
                <input type="number" step="0.1" name="target_weight_kg" value="{{ $targetWeight }}" placeholder="كغم">
                <button type="submit" class="cal-secondary-btn">{{ $targetWeight ? 'تحديث' : 'تحديد' }}</button>
            </form>
        </div>

        {{-- الرسم البياني --}}
        @if ($weightLogs->count() >= 2)
            <div class="cal-weight-chart-wrap">
                <canvas id="patWeightChart"></canvas>
            </div>
        @endif

        {{-- سجل القياسات --}}
        @if ($weightLogs->isNotEmpty())
            <div class="cal-weight-list">
                @foreach ($weightLogs->reverse()->take(8) as $log)
                    <article class="cal-weight-row" data-weight-row>
                        <div class="cal-weight-row__view">
                            <span class="cal-weight-row__date">{{ \Carbon\Carbon::parse($log->logged_date)->locale('ar')->translatedFormat('j M Y') }}</span>
                            <strong class="cal-weight-row__value">{{ $log->weight_kg }} كغم</strong>

                            @if (($log->source ?? '') !== 'profile_initial')
                                <div class="cal-weight-row__actions">
                                    <button type="button" data-weight-edit-btn aria-label="تعديل"><i data-lucide="pencil"></i></button>
                                    <form method="POST" action="{{ route('patient.weight.destroy', $log->id) }}" data-weight-delete-form>
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" aria-label="حذف" data-weight-delete-open>
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('patient.weight.update', $log->id) }}" class="cal-weight-row__edit" hidden>
                            @csrf
                            @method('PATCH')
                            <input type="number" step="0.1" min="25" max="350" name="weight_kg" value="{{ $log->weight_kg }}">
                            <input type="date" name="logged_date" value="{{ \Carbon\Carbon::parse($log->logged_date)->toDateString() }}" max="{{ now()->toDateString() }}">
                            <button type="submit" aria-label="حفظ"><i data-lucide="check"></i></button>
                            <button type="button" data-weight-cancel-btn aria-label="إلغاء"><i data-lucide="x"></i></button>
                        </form>
                    </article>
                @endforeach
            </div>
        @endif

        {{-- مودال تأكيد حذف الوزن --}}
        <div class="cal-weight-modal-overlay" data-weight-delete-modal>
            <div class="cal-weight-modal">
                <div class="cal-weight-modal__icon"><i data-lucide="trash-2"></i></div>
                <h3>حذف قياس الوزن؟</h3>
                <p>هاد الإجراء ما ممكن نتراجع عنه بعدين.</p>
                <div class="cal-weight-modal__actions">
                    <button type="button" class="cal-secondary-btn" data-weight-delete-cancel>إلغاء</button>
                    <button type="button" class="cal-weight-modal__confirm" data-weight-delete-confirm>حذف نهائياً</button>
                </div>
            </div>
        </div>
    </section>

    <section class="cal-review-studio" id="calReviewStudio">
        <article class="cal-card cal-review-card">
            <div class="cal-review-head">
                <div>
                    <span>AI Result Review</span>
                    <h2>مراجعة نتيجة التحليل</h2>
                    <p>هنا تظهر نتيجة الذكاء الاصطناعي قبل الحفظ. راجع القيم، عدّل الكمية، ثم اعتمد الوجبة.</p>
                </div>

                <div class="cal-review-icon">
                    <i data-lucide="clipboard-check"></i>
                </div>
            </div>

            @if (!empty($aiMealDraft))
                <form action="{{ $confirmRoute }}" method="POST" class="cal-result-form">
                    @csrf

                    <div class="cal-review-layout">
                        <div class="cal-review-preview">
                            <div class="cal-result-image">
                                @if (data_get($aiMealDraft, 'image_url'))
                                    <img src="{{ data_get($aiMealDraft, 'image_url') }}" alt="صورة الوجبة">
                                @else
                                    <span>🥗</span>
                                @endif
                            </div>

                            <div class="cal-preview-copy">
                                <span>Estimated Meal</span>

                                <label class="cal-note-field cal-meal-name-field">
                                    <span>اسم الوجبة</span>
                                    <input
                                        type="text"
                                        name="meal_name"
                                        value="{{ data_get($aiMealDraft, 'meal_name', 'وجبة محللة') }}"
                                        required
                                    >
                                </label>

                                <div class="cal-result-calories">
                                    <strong>{{ $draftCalories }}</strong>
                                    <small>Cal</small>
                                </div>

                                <div class="cal-confidence-pill">
                                    <i data-lucide="badge-check"></i>
                                    ثقة التحليل {{ $draftConfidence }}%
                                </div>

                                @if (data_get($aiMealDraft, 'ai_notes'))
                                    <p class="cal-ai-note">{{ data_get($aiMealDraft, 'ai_notes') }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="cal-review-editor">
                            <div class="cal-edit-grid cal-premium-macros">
                                <label class="cal-edit-macro cal-macro-calories">
                                    <span>السعرات</span>
                                    <input type="number" name="calories" value="{{ $draftCalories }}" min="0">
                                    <small>Cal</small>
                                </label>

                                <label class="cal-edit-macro cal-macro-protein">
                                    <span>البروتين</span>
                                    <input type="number" name="protein" value="{{ $draftProtein }}" min="0">
                                    <small>g</small>
                                </label>

                                <label class="cal-edit-macro cal-macro-carbs">
                                    <span>الكربوهيدرات</span>
                                    <input type="number" name="carbs" value="{{ $draftCarbs }}" min="0">
                                    <small>g</small>
                                </label>

                                <label class="cal-edit-macro cal-macro-fat">
                                    <span>الدهون</span>
                                    <input type="number" name="fat" value="{{ $draftFat }}" min="0">
                                    <small>g</small>
                                </label>
                            </div>

                            <label class="cal-note-field">
                                <span>ملاحظة أو تعديل الكمية</span>
                                <textarea name="patient_note" rows="3" placeholder="مثال: بدون صوص، أو الكمية كانت أقل...">{{ old('patient_note') }}</textarea>
                            </label>

                            <div class="cal-actions">
                                <button type="submit" class="cal-primary-btn">
                                    <i data-lucide="badge-check"></i>
                                    اعتماد وحفظ الوجبة
                                </button>

                                <a href="#calAnalyzerCard" class="cal-secondary-btn">
                                    <i data-lucide="rotate-ccw"></i>
                                    تحليل وجبة أخرى
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <div class="cal-review-empty">
                    <div class="cal-review-empty-visual">
                        <span class="cal-scan-frame">
                            <i data-lucide="scan-search"></i>
                        </span>
                    </div>

                    <div class="cal-review-empty-copy">
                        <span>Ready to analyze</span>
                        <h3>لم يتم تحليل وجبة بعد</h3>
                        <p>ارفع صورة أو اكتب وصفًا للوجبة، وستظهر هنا النتيجة للمراجعة قبل الحفظ.</p>

                        <a href="#calAnalyzerCard" class="cal-primary-btn">
                            <i data-lucide="sparkles"></i>
                            ابدأ التحليل
                        </a>
                    </div>
                </div>
            @endif

            <div class="cal-review-tips">
                <div>
                    <i data-lucide="scale"></i>
                    <strong>راجع الكمية</strong>
                    <span>تأكد من حجم الطبق أو اكتب ملاحظة.</span>
                </div>

                <div>
                    <i data-lucide="droplet"></i>
                    <strong>اذكر الإضافات</strong>
                    <span>الصوص، الزيت، المكسرات أو أي إضافات.</span>
                </div>

                <div>
                    <i data-lucide="badge-check"></i>
                    <strong>اعتمد بعد المراجعة</strong>
                    <span>بعد الاعتماد تظهر الوجبة في سجل اليوم.</span>
                </div>
            </div>
        </article>
    </section>

    <section class="cal-card cal-meals-section" id="calSavedMeals">
        <div class="cal-section-head">
            <div>
                <span>Saved Today</span>
                <h2>وجبات اليوم المحفوظة</h2>
                <p>هذه الوجبات تم اعتمادها وحفظها في السجل.</p>
            </div>

            <a href="#calAnalyzerCard" class="cal-section-link">
                <i data-lucide="plus"></i>
                تحليل وجبة جديدة
            </a>
        </div>

        @if ($todayMeals->isNotEmpty())
            <div class="cal-meals-list">
                @foreach ($todayMeals as $meal)
                    @php
                        $type = data_get($meal, 'meal_type', 'lunch');
                        $label = $mealTypeArabic[$type] ?? 'وجبة';
                        $english = $mealTypeLabel[$type] ?? 'Meal';
                        $icon = $mealTypeIcon[$type] ?? 'utensils';
                        $emoji = $mealTypeEmoji[$type] ?? '🍽️';

                        $createdAt = data_get($meal, 'created_at');

                        try {
                            $mealTime = $createdAt
                                ? \Carbon\Carbon::parse($createdAt)->format('H:i')
                                : 'اليوم';
                        } catch (\Throwable $e) {
                            $mealTime = 'اليوم';
                        }
                    @endphp

                    <article class="cal-meal-row">
                        <div class="cal-meal-art cal-meal-art-{{ $type }}">
                            @if (data_get($meal, 'image_path'))
                                <img src="{{ asset('storage/' . data_get($meal, 'image_path')) }}" alt="صورة الوجبة">
                            @else
                                <span>{{ $emoji }}</span>
                            @endif
                        </div>

                        <div class="cal-meal-body">
                            <div class="cal-meal-meta">
                                <span>{{ $english }}</span>
                                <i data-lucide="{{ $icon }}"></i>
                            </div>

                            <h3>{{ data_get($meal, 'meal_name', $label) }}</h3>

                            <div class="cal-meal-tags">
                                <span>{{ $mealTime }}</span>
                                <span>{{ data_get($meal, 'source') === 'ai' ? 'AI تحليل' : 'يدوي' }}</span>
                            </div>

                            @if (data_get($meal, 'doctor_note'))
                                <p class="cal-ai-note">ملاحظة طبيبك: {{ data_get($meal, 'doctor_note') }}</p>
                            @endif
                        </div>

                        <div class="cal-meal-side">
                            <strong>{{ (int) data_get($meal, 'calories', 0) }}</strong>
                            <small>Cal</small>

                            @if ($updateRouteName && data_get($meal, 'id'))
                                <button type="button" data-meal-edit-toggle aria-label="تعديل الوجبة">
                                    <i data-lucide="pencil"></i>
                                </button>
                            @endif

                            @if ($destroyRouteName && data_get($meal, 'id'))
                                <form action="{{ route($destroyRouteName, data_get($meal, 'id')) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذه الوجبة؟');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" aria-label="حذف الوجبة">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>

                    @if ($updateRouteName && data_get($meal, 'id'))
                        <form action="{{ route($updateRouteName, data_get($meal, 'id')) }}" method="POST" class="cal-meal-edit-form" data-meal-edit-form hidden>
                            @csrf
                            @method('PUT')

                            <label>
                                <span>اسم الوجبة</span>
                                <input type="text" name="meal_name" value="{{ data_get($meal, 'meal_name') }}" required>
                            </label>

                            <div class="cal-meal-edit-grid">
                                <label>
                                    <span>سعرات</span>
                                    <input type="number" name="calories" value="{{ (int) data_get($meal, 'calories', 0) }}" min="0" max="5000" required>
                                </label>

                                <label>
                                    <span>بروتين (غ)</span>
                                    <input type="number" name="protein" value="{{ (int) data_get($meal, 'protein', 0) }}" min="0" max="400">
                                </label>

                                <label>
                                    <span>كارب (غ)</span>
                                    <input type="number" name="carbs" value="{{ (int) data_get($meal, 'carbs', 0) }}" min="0" max="700">
                                </label>

                                <label>
                                    <span>دهون (غ)</span>
                                    <input type="number" name="fat" value="{{ (int) data_get($meal, 'fat', 0) }}" min="0" max="400">
                                </label>
                            </div>

                            <label>
                                <span>ملاحظتك (اختياري)</span>
                                <textarea name="patient_note" rows="2">{{ data_get($meal, 'patient_note') }}</textarea>
                            </label>

                            <button type="submit" class="cal-primary-btn">حفظ التعديل</button>
                        </form>
                    @endif
                @endforeach
            </div>
        @else
            <div class="cal-empty-state cal-empty-meals">
                <i data-lucide="utensils"></i>
                <strong>لا توجد وجبات محفوظة لهذا اليوم</strong>
                <span>حلّل أول وجبة ثم اعتمد النتيجة حتى تظهر هنا.</span>

                <a href="#calAnalyzerCard" class="cal-primary-btn">
                    <i data-lucide="sparkles"></i>
                    تحليل أول وجبة
                </a>
            </div>
        @endif
    </section>

    <section class="cal-card cal-history-section">
        <div class="cal-section-head">
            <div>
                <span>Recent Analyses</span>
                <h2>آخر تحليلات الوجبات</h2>
                <p>سجل آخر الوجبات التي تم تحليلها أو حفظها.</p>
            </div>

            <i data-lucide="history"></i>
        </div>

        @if ($recentAnalyses->isNotEmpty())
            <div class="cal-history-list">
                @foreach ($recentAnalyses as $analysis)
                    @php
                        $analysisCreatedAt = data_get($analysis, 'created_at');

                        try {
                            $analysisTime = $analysisCreatedAt
                                ? \Carbon\Carbon::parse($analysisCreatedAt)->diffForHumans()
                                : 'اليوم';
                        } catch (\Throwable $e) {
                            $analysisTime = 'اليوم';
                        }
                    @endphp

                    <article class="cal-history-item">
                        <span>🍽️</span>

                        <div>
                            <strong>{{ data_get($analysis, 'meal_name', 'وجبة محللة') }}</strong>
                            <small>{{ $analysisTime }}</small>
                        </div>

                        <em>{{ (int) data_get($analysis, 'calories', 0) }} Cal</em>

                        <b>{{ data_get($analysis, 'status') === 'confirmed' ? 'Saved' : 'Draft' }}</b>
                    </article>
                @endforeach
            </div>
        @else
            <div class="cal-empty-state cal-empty-history">
                <i data-lucide="history"></i>
                <strong>لا توجد تحليلات بعد</strong>
                <span>بعد تحليل واعتماد الوجبات ستظهر هنا.</span>
            </div>
        @endif
    </section>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/calories.js') }}"></script>

    @if ($weightLogs->count() >= 2)
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var canvas = document.getElementById('patWeightChart');
            if (!canvas || typeof Chart === 'undefined') return;

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: @json($weightLogs->map(fn($w) => \Carbon\Carbon::parse($w->logged_date)->locale('ar')->translatedFormat('j M'))),
                    datasets: [{
                        data: @json($weightLogs->pluck('weight_kg')),
                        borderColor: '#1D9E75',
                        backgroundColor: 'rgba(29,158,117,.1)',
                        fill: true,
                        tension: .3,
                        pointRadius: 4,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { grid: { display: false } } }
                }
            });
        });
        </script>
    @endif

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-weight-row]').forEach(function (row) {
            var editBtn = row.querySelector('[data-weight-edit-btn]');
            var cancelBtn = row.querySelector('[data-weight-cancel-btn]');
            var viewEl = row.querySelector('.cal-weight-row__view');
            var editEl = row.querySelector('.cal-weight-row__edit');

            if (editBtn) {
                editBtn.addEventListener('click', function () {
                    viewEl.hidden = true;
                    editEl.hidden = false;
                });
            }
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    editEl.hidden = true;
                    viewEl.hidden = false;
                });
            }
        });

        /* رسالة خطأ مخصّصة بدل فقاعة المتصفح الافتراضية */
        var addForm = document.querySelector('[data-weight-add-form]');
        if (addForm) {
            var weightInput = addForm.querySelector('[data-weight-input]');
            var errorEl = addForm.querySelector('[data-weight-error]');

            addForm.addEventListener('submit', function (event) {
                var value = parseFloat(weightInput.value);
                var min = parseFloat(weightInput.min);
                var max = parseFloat(weightInput.max);
                var message = '';

                if (!weightInput.value) {
                    message = 'ادخلي وزنك أول.';
                } else if (value < min) {
                    message = 'الوزن المدخل صغير جداً (أقل من ' + min + ' كغم).';
                } else if (value > max) {
                    message = 'الوزن المدخل كبير جداً (أكتر من ' + max + ' كغم).';
                }

                if (message) {
                    event.preventDefault();
                    errorEl.textContent = message;
                    errorEl.hidden = false;
                    weightInput.classList.add('is-invalid');
                } else {
                    errorEl.hidden = true;
                    weightInput.classList.remove('is-invalid');
                }
            });

            weightInput.addEventListener('input', function () {
                errorEl.hidden = true;
                weightInput.classList.remove('is-invalid');
            });
        }

        /* مودال تأكيد حذف الوزن */
        var deleteModal = document.querySelector('[data-weight-delete-modal]');
        var deleteCancel = document.querySelector('[data-weight-delete-cancel]');
        var deleteConfirm = document.querySelector('[data-weight-delete-confirm]');
        var pendingDeleteForm = null;

        document.querySelectorAll('[data-weight-delete-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                pendingDeleteForm = btn.closest('[data-weight-delete-form]');
                if (deleteModal) deleteModal.classList.add('is-open');
            });
        });

        function closeDeleteModal() {
            pendingDeleteForm = null;
            if (deleteModal) deleteModal.classList.remove('is-open');
        }

        if (deleteCancel) deleteCancel.addEventListener('click', closeDeleteModal);
        if (deleteModal) {
            deleteModal.addEventListener('click', function (e) {
                if (e.target === deleteModal) closeDeleteModal();
            });
        }
        if (deleteConfirm) {
            deleteConfirm.addEventListener('click', function () {
                if (pendingDeleteForm) pendingDeleteForm.submit();
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDeleteModal();
        });
    });
    </script>
@endpush
