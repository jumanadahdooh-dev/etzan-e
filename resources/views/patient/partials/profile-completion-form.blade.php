@php
    $form = $profileForm ?? [];
    $patient = $patient ?? [];

    $authUser = auth()->user();

    $patient['name'] = $patient['name']
        ?? data_get($authUser, 'name')
        ?? 'مستخدم اتزان';

    $patient['email'] = $patient['email']
        ?? data_get($authUser, 'email')
        ?? '';

    $patient['initial'] = $patient['initial']
        ?? mb_substr($patient['name'], 0, 1, 'UTF-8');

    $patient['avatar'] = $patient['avatar']
        ?? data_get($form, 'avatar')
        ?? data_get($form, 'photo')
        ?? null;

    if (!empty($patient['avatar']) && !\Illuminate\Support\Str::startsWith($patient['avatar'], ['http://', 'https://'])) {
        $patient['avatar'] = asset('storage/' . ltrim($patient['avatar'], '/'));
    }

    $patient['profile_completion'] = (int) (
        $patient['profile_completion']
        ?? $profileCompletion
        ?? data_get($form, 'profile_completion')
        ?? data_get($form, 'completion_percentage')
        ?? 0
    );

    $patient['profile_completion'] = max(0, min(100, $patient['profile_completion']));

    $conditions = old('medical_conditions', $form['medical_conditions'] ?? []);

    if (!is_array($conditions)) {
        $conditions = [];
    }

    

    $healthGoals = [
        'healthy_lifestyle' => [
            'label' => 'تحسين نمط الحياة',
            'hint' => 'تنظيم العادات اليومية ودعم الصحة العامة',
            'icon' => 'leaf',
        ],
        'diabetes_management' => [
            'label' => 'تنظيم السكر',
            'hint' => 'متابعة الوجبات والعادات المناسبة للسكر',
            'icon' => 'droplet',
        ],
        'hypertension_management' => [
            'label' => 'تنظيم الضغط',
            'hint' => 'متابعة الصوديوم ونمط الأكل اليومي',
            'icon' => 'activity',
        ],
        'cholesterol_management' => [
            'label' => 'تحسين الكوليسترول',
            'hint' => 'دعم صحة القلب وتنظيم الدهون',
            'icon' => 'heart-pulse',
        ],
        'chronic_care' => [
            'label' => 'متابعة حالة مزمنة',
            'hint' => 'رعاية صحية وغذائية حسب الحالة',
            'icon' => 'stethoscope',
        ],
        'therapeutic_nutrition' => [
            'label' => 'تغذية علاجية',
            'hint' => 'خطة مخصصة بإشراف مختص',
            'icon' => 'clipboard-plus',
        ],
        'weight_loss' => [
            'label' => 'خسارة وزن',
            'hint' => 'نزول صحي ومتدرج يناسب حالتك',
            'icon' => 'trending-down',
        ],
        'weight_gain' => [
            'label' => 'زيادة وزن صحية',
            'hint' => 'زيادة متوازنة حسب احتياج الجسم',
            'icon' => 'trending-up',
        ],
    ];

    $activityLevels = [
        'low' => 'قليل الحركة',
        'light' => 'نشاط خفيف',
        'moderate' => 'نشاط متوسط',
        'high' => 'نشاط عالي',
    ];

    $conditionOptions = [
        'none' => ['label' => 'لا يوجد', 'icon' => 'check-circle-2'],
        'diabetes' => ['label' => 'سكري', 'icon' => 'droplet'],
        'hypertension' => ['label' => 'ضغط', 'icon' => 'activity'],
        'cholesterol' => ['label' => 'كوليسترول', 'icon' => 'heart-pulse'],
        'heart' => ['label' => 'أمراض قلب', 'icon' => 'heart'],
        'kidney' => ['label' => 'مشاكل كلى', 'icon' => 'shield-alert'],
        'liver' => ['label' => 'مشاكل كبد', 'icon' => 'shield'],
        'thyroid' => ['label' => 'الغدة الدرقية', 'icon' => 'circle-dot'],
        'pcos' => ['label' => 'تكيس', 'icon' => 'sparkles'],
        'pregnancy' => ['label' => 'حمل أو رضاعة', 'icon' => 'baby'],
        'digestive' => ['label' => 'مشاكل هضمية', 'icon' => 'stethoscope'],
        'anemia' => ['label' => 'أنيميا', 'icon' => 'circle-alert'],
        'food_allergy' => ['label' => 'حساسية غذائية', 'icon' => 'wheat-off'],
    ];
@endphp

<section class="pc-page" data-profile-completion-page>
    <div class="pc-hero">
        <div class="pc-hero-copy">
            <span class="pc-kicker">
                <i data-lucide="sparkles"></i>
                ملف صحي ذكي
            </span>

            <h1>أكمل ملفك الصحي لنخصص لك التجربة الأنسب</h1>

            <p>
                بإضافة بياناتك الأساسية وحالتك الصحية واهتماماتك، يتم تحسين التوصيات وترتيب الأطباء
                والمهام اليومية بما يناسب احتياجك الصحي بشكل أفضل.
            </p>

            <div class="pc-hero-tags">
                <span><i data-lucide="shield-check"></i> بيانات آمنة</span>
                <span><i data-lucide="stethoscope"></i> ترشيح حسب الحالة</span>
                <span><i data-lucide="sparkles"></i> تجربة مخصصة</span>
            </div>
        </div>

        <div class="pc-health-orbit" aria-hidden="true">
            <div class="pc-orbit-center">
                <i data-lucide="heart-pulse"></i>
                <strong>{{ $patient['profile_completion'] ?? 0 }}%</strong>
                <span>اكتمال الملف</span>
            </div>

            <span class="pc-orbit-dot dot-1"><i data-lucide="droplet"></i></span>
            <span class="pc-orbit-dot dot-2"><i data-lucide="activity"></i></span>
            <span class="pc-orbit-dot dot-3"><i data-lucide="utensils"></i></span>
            <span class="pc-orbit-dot dot-4"><i data-lucide="pill"></i></span>
        </div>
    </div>

    @if (session('error'))
        <div class="pc-alert is-error">
            <i data-lucide="circle-alert"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="pc-alert is-error">
            <i data-lucide="circle-alert"></i>
            <div>
                <strong>يرجى مراجعة الحقول المطلوبة</strong>
                <span>{{ $errors->first() }}</span>
            </div>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('patient.profile.complete') }}"
        class="pc-shell pc-single-flow"
        data-health-profile-wizard
        enctype="multipart/form-data"
    >
        @csrf

        <main class="pc-card">
            <div class="pc-inline-progress">
                <div class="pc-inline-progress-top">
                    <div>
                        <span>تقدم إعداد الملف</span>
                        <strong data-pc-percent>25%</strong>
                    </div>

                    <div class="pc-inline-progress-bar">
                        <span data-pc-progress style="width: 25%"></span>
                    </div>
                </div>

                <nav class="pc-inline-steps" aria-label="خطوات الملف الصحي">
                    <button type="button" class="pc-inline-step is-active" data-pc-goto="0">
                        <i data-lucide="ruler"></i>
                        <span>البيانات</span>
                    </button>

                    <button type="button" class="pc-inline-step" data-pc-goto="1">
                        <i data-lucide="target"></i>
                        <span>الهدف</span>
                    </button>

                    <button type="button" class="pc-inline-step" data-pc-goto="2">
                        <i data-lucide="heart-pulse"></i>
                        <span>الحالة</span>
                    </button>

                    <button type="button" class="pc-inline-step" data-pc-goto="3">
                        <i data-lucide="user-round-check"></i>
                        <span>المتابعة</span>
                    </button>
                </nav>

                <div class="pc-inline-smart" data-smart-preview>
                    <span>
                        <i data-lucide="sparkles"></i>
                        <strong data-smart-title>سنقترح الأطباء الأنسب بعد الحفظ</strong>
                    </span>

                    <p data-smart-copy>
                        كلما كانت بياناتك الصحية أوضح، أصبحت التوصيات وترتيب الأطباء أدق وأكثر ملاءمة.
                    </p>
                </div>
            </div>

            <section class="pc-step-panel is-active" data-pc-step="0">
                <div class="pc-step-head">
                    <span>01</span>
                    <div>
                        <h2>البيانات الأساسية</h2>
                        <p>ابدأ بالمعلومات الأساسية حتى نستطيع تجهيز ملفك الصحي بصورة دقيقة.</p>
                    </div>
                </div>

                <div class="pc-avatar-uploader">
                    <label class="pc-avatar-panel">
                        <input type="file" name="avatar" accept="image/*" data-avatar-input>

                        <span class="pc-avatar-preview" data-avatar-preview>
                            @if (!empty($patient['avatar']))
                                <img src="{{ $patient['avatar'] }}" alt="صورة المريض">
                            @else
                                <i data-lucide="camera"></i>
                            @endif
                        </span>

                        <span class="pc-avatar-content">
                            <strong>الصورة الشخصية</strong>
                            <small>يمكنك إضافة صورة للحساب إذا رغبت</small>
                        </span>

                        <span class="pc-avatar-chip">
                            <i data-lucide="upload-cloud"></i>
                            رفع صورة
                        </span>
                    </label>
                </div>

                <div class="pc-grid">
                    <label class="pc-field">
                        <span>الطول بالسنتيمتر</span>
                        <div class="pc-input">
                            <i data-lucide="ruler"></i>
                            <input
                                type="number"
                                name="height_cm"
                                min="80"
                                max="240"
                                step="0.1"
                                value="{{ old('height_cm', $form['height_cm'] ?? '') }}"
                                placeholder="مثال: 165"
                                required
                                data-height-input
                            >
                        </div>
                    </label>

                    <label class="pc-field">
                        <span>الوزن بالكيلوغرام</span>
                        <div class="pc-input">
                            <i data-lucide="scale"></i>
                            <input
                                type="number"
                                name="weight_kg"
                                min="25"
                                max="350"
                                step="0.1"
                                value="{{ old('weight_kg', $form['weight_kg'] ?? '') }}"
                                placeholder="مثال: 68"
                                required
                                data-weight-input
                            >
                        </div>
                    </label>

                    <label class="pc-field">
                        <span>تاريخ الميلاد</span>
                        <div class="pc-input">
                            <i data-lucide="calendar-days"></i>
                            <input
                                type="date"
                                name="birth_date"
                                value="{{ old('birth_date', $form['birth_date'] ?? '') }}"
                                required
                            >
                        </div>
                    </label>

                    <label class="pc-field">
                        <span>الجنس</span>
                        <div class="pc-input">
                            <i data-lucide="user-round"></i>
                            <select name="gender" required>
                                <option value="">اختر</option>
                                <option value="female" @selected(old('gender', $form['gender'] ?? '') === 'female')>أنثى</option>
                                <option value="male" @selected(old('gender', $form['gender'] ?? '') === 'male')>ذكر</option>
                            </select>
                        </div>
                    </label>

                    <label class="pc-field">
                        <span>رقم الجوال</span>
                        <div class="pc-input">
                            <i data-lucide="phone"></i>
                            <input
                                type="text"
                                name="phone"
                                value="{{ old('phone', $form['phone'] ?? '') }}"
                                placeholder="مثال: 0590000000"
                            >
                        </div>
                    </label>

                    <label class="pc-field">
                        <span>المدينة</span>
                        <div class="pc-input">
                            <i data-lucide="map-pin"></i>
                            <input
                                type="text"
                                name="city"
                                value="{{ old('city', $form['city'] ?? '') }}"
                                placeholder="مثال: غزة، رام الله، عمّان"
                            >
                        </div>
                    </label>
                </div>

                <div class="pc-bmi-card">
                    <div class="pc-bmi-icon">
                        <i data-lucide="gauge"></i>
                    </div>

                    <div>
                        <span>مؤشر كتلة الجسم التقريبي</span>
                        <strong data-bmi-value>—</strong>
                        <p data-bmi-note>أدخل الطول والوزن ليظهر المؤشر هنا.</p>
                    </div>

                    <small>هذا المؤشر مساعد فقط، والتقييم النهائي يكون مع الطبيب.</small>
                </div>
            </section>

            <section class="pc-step-panel" data-pc-step="1">
                <div class="pc-step-head">
                    <span>02</span>
                    <div>
                        <h2>الهدف الصحي</h2>
                        <p>اختر الهدف الأقرب لك حتى يتم اقتراح المتابعة الأنسب.</p>
                    </div>
                </div>

                <div class="pc-goal-grid">
                    @foreach ($healthGoals as $value => $goal)
                        <label class="pc-goal-option">
                            <input
                                type="radio"
                                name="health_goal"
                                value="{{ $value }}"
                                @checked(old('health_goal', $form['health_goal'] ?? '') === $value)
                                required
                                data-health-goal
                            >

                            <span class="pc-goal-icon">
                                <i data-lucide="{{ $goal['icon'] }}"></i>
                            </span>

                            <strong>{{ $goal['label'] }}</strong>
                            <small>{{ $goal['hint'] }}</small>
                        </label>
                    @endforeach
                </div>

                <div class="pc-grid mt-3">
                    <label class="pc-field">
                        <span>مستوى النشاط</span>
                        <div class="pc-input">
                            <i data-lucide="activity"></i>
                            <select name="activity_level" required>
                                <option value="">اختر مستوى النشاط</option>
                                @foreach ($activityLevels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('activity_level', $form['activity_level'] ?? '') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </label>

                    <label class="pc-field">
                        <span>عدد الوجبات يومياً</span>
                        <div class="pc-input">
                            <i data-lucide="utensils"></i>
                            <input
                                type="number"
                                name="meals_per_day"
                                min="1"
                                max="8"
                                value="{{ old('meals_per_day', $form['meals_per_day'] ?? 3) }}"
                            >
                        </div>
                    </label>

                    <label class="pc-field">
                        <span>ساعات النوم</span>
                        <div class="pc-input">
                            <i data-lucide="moon"></i>
                            <input
                                type="number"
                                name="sleep_hours"
                                min="0"
                                max="16"
                                step="0.5"
                                value="{{ old('sleep_hours', $form['sleep_hours'] ?? '') }}"
                                placeholder="مثال: 7"
                            >
                        </div>
                    </label>

                    <label class="pc-field">
                        <span>أكواب الماء يومياً</span>
                        <div class="pc-input">
                            <i data-lucide="droplet"></i>
                            <input
                                type="number"
                                name="water_cups"
                                min="0"
                                max="20"
                                value="{{ old('water_cups', $form['water_cups'] ?? '') }}"
                                placeholder="مثال: 6"
                            >
                        </div>
                    </label>
                </div>
            </section>

            <section class="pc-step-panel" data-pc-step="2">
                <div class="pc-step-head">
                    <span>03</span>
                    <div>
                        <h2>الحالة الصحية</h2>
                        <p>اختر ما ينطبق على حالتك حتى تكون المتابعة والتوصيات أكثر أمانًا ودقة.</p>
                    </div>
                </div>

                <div class="pc-condition-wrap">
                    <div class="pc-condition-flow">
                        @foreach ($conditionOptions as $value => $condition)
                            <label class="pc-condition-chip">
                                <input
                                    type="checkbox"
                                    name="medical_conditions[]"
                                    value="{{ $value }}"
                                    @checked(in_array($value, $conditions, true))
                                    data-condition-input
                                >

                                <span>
                                    <i data-lucide="{{ $condition['icon'] }}"></i>
                                    {{ $condition['label'] }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="pc-grid mt-3">
                    <label class="pc-field pc-wide">
                        <span>الأدوية الحالية</span>
                        <textarea
                            name="medications"
                            rows="4"
                            placeholder="اكتب الأدوية التي تستخدمها حالياً إن وجدت"
                        >{{ old('medications', $form['medications'] ?? '') }}</textarea>
                    </label>

                    <label class="pc-field pc-wide">
                        <span>الحساسية أو الأطعمة الممنوعة</span>
                        <textarea
                            name="allergies"
                            rows="4"
                            placeholder="اكتب أي حساسية غذائية أو أطعمة لا تناسب حالتك"
                        >{{ old('allergies', $form['allergies'] ?? '') }}</textarea>
                    </label>
                </div>

                <div class="pc-safety-note">
                    <i data-lucide="shield-check"></i>
                    <div>
                        <strong>معلومة مهمة</strong>
                        <span>هذه البيانات تساعد في تقديم متابعة غذائية وصحية أكثر ملاءمة لحالتك.</span>
                    </div>
                </div>
            </section>

            <section class="pc-step-panel" data-pc-step="3">
                <div class="pc-step-head">
                    <span>04</span>
                    <div>
                        <h2>تفضيلات المتابعة</h2>
                        <p>بعد الحفظ سنعرض لك الأطباء الأنسب، ويمكنك اختيار الطبيب الذي يناسبك.</p>
                    </div>
                </div>

                <div class="pc-grid">
                    <label class="pc-field">
                        <span>تفضيل جنس الطبيب</span>
                        <div class="pc-input">
                            <i data-lucide="stethoscope"></i>
                            <select name="preferred_doctor_gender">
                                <option value="any" @selected(old('preferred_doctor_gender', $form['preferred_doctor_gender'] ?? 'any') === 'any')>لا يهم</option>
                                <option value="female" @selected(old('preferred_doctor_gender', $form['preferred_doctor_gender'] ?? '') === 'female')>طبيبة</option>
                                <option value="male" @selected(old('preferred_doctor_gender', $form['preferred_doctor_gender'] ?? '') === 'male')>طبيب</option>
                            </select>
                        </div>
                    </label>

                    <label class="pc-field">
                        <span>نوع الاستشارة المفضل</span>
                        <div class="pc-input">
                            <i data-lucide="video"></i>
                            <select name="preferred_consultation_type">
                                <option value="any" @selected(old('preferred_consultation_type', $form['preferred_consultation_type'] ?? 'any') === 'any')>لا يهم</option>
                                <option value="online" @selected(old('preferred_consultation_type', $form['preferred_consultation_type'] ?? '') === 'online')>أونلاين</option>
                                <option value="clinic" @selected(old('preferred_consultation_type', $form['preferred_consultation_type'] ?? '') === 'clinic')>حضوري</option>
                            </select>
                        </div>
                    </label>

                    <label class="pc-field pc-wide">
                        <span>ملاحظات إضافية</span>
                        <textarea
                            name="notes"
                            rows="5"
                            placeholder="اكتب أي ملاحظات مهمة تريد أن يعرفها الطبيب"
                        >{{ old('notes', $form['notes'] ?? '') }}</textarea>
                    </label>
                </div>

                <div class="pc-doctor-preview">
                    <div class="pc-doctor-glow">
                        <i data-lucide="sparkles"></i>
                    </div>

                    <div>
                        <span>الخطوة التالية</span>
                        <strong>بعد الحفظ ستظهر لك قائمة أطباء مناسبة لحالتك</strong>
                        <p>
                            سيتم ترتيبهم بطريقة تساعدك على اختيار الطبيب الأنسب حسب هدفك الصحي
                            وحالتك وتفضيلاتك.
                        </p>
                    </div>
                </div>
            </section>

            <div class="pc-actions">
                <button type="button" class="pc-btn ghost" data-pc-back disabled>
                    <i data-lucide="arrow-right"></i>
                    السابق
                </button>

                <button type="button" class="pc-btn primary" data-pc-next>
                    التالي
                    <i data-lucide="arrow-left"></i>
                </button>

                <button type="submit" class="pc-btn submit d-none" data-pc-submit>
                    حفظ الملف وعرض الأطباء
                    <i data-lucide="sparkles"></i>
                </button>
            </div>
        </main>
    </form>
</section>
