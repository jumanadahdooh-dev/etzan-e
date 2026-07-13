@extends('layouts.admin')

@section('title', 'إدارة المستخدمين')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/admin/admin-users.css') }}?v={{ filemtime(public_path('front/css/admin/admin-users.css')) }}">
@endpush

@section('content')

<div class="up-shell">

    {{-- ══════════════════════════════════════════
         اللوحة الرئيسية (يسار)
    ══════════════════════════════════════════ --}}
    <div class="up-main">

        <div class="up-topbar">
            <h1 class="up-topbar__title">إدارة المستخدمين</h1>

            <div class="up-topbar__tools">
                <div class="up-search-wrap {{ !empty($search) ? 'active' : '' }}" id="upSearchWrap">
                    <button type="button" class="up-search-toggle" id="upSearchToggle" onclick="upToggleSearch()" aria-label="بحث">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <form method="GET" action="{{ route('admin.admin-users') }}" class="up-toolbar-pro__search" id="upSearchBox">
                        @if(!empty($filters['role']))
                            <input type="hidden" name="role" value="{{ $filters['role'] }}">
                        @endif
                        @if(!empty($filters['account_status']))
                            <input type="hidden" name="account_status" value="{{ $filters['account_status'] }}">
                        @endif
                        @if(!empty($filters['verified']))
                            <input type="hidden" name="verified" value="{{ $filters['verified'] }}">
                        @endif
                        @if(!empty($filters['date_from']))
                            <input type="hidden" name="date_from" value="{{ $filters['date_from'] }}">
                        @endif
                        @if(!empty($filters['date_to']))
                            <input type="hidden" name="date_to" value="{{ $filters['date_to'] }}">
                        @endif
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" id="upSearchInput" value="{{ $search ?? '' }}" placeholder="ابحث بالاسم أو البريد الإلكتروني..." oninput="upToggleSearchClear()">
                        <button type="button" class="up-search-clear" id="upSearchClear" onclick="upClearSearch()" aria-label="مسح البحث" style="{{ !empty($search) ? 'display:grid' : 'display:none' }}">
                            <i class="fa-solid fa-xmark"></i>
                        </button>                    </form>
                </div>

                <button type="button" class="up-search-toggle" id="upFiltersToggle" onclick="upToggleFiltersPanel()" aria-label="فلاتر" title="فلاتر">
                    <i class="fa-solid fa-sliders"></i>
                    @if(!empty($filters['role']) || !empty($filters['account_status']) || !empty($filters['verified']) || !empty($filters['date_from']) || !empty($filters['date_to']))
                        <span class="up-search-toggle__dot"></span>
                    @endif
                </button>
            </div>
        </div>

        {{-- لوحة الفلاتر: مطوية افتراضياً، بتنفتح لما تدوسي على أيقونة الفلتر --}}
        <form method="GET" action="{{ route('admin.admin-users') }}" class="up-filters-panel {{ (!empty($filters['role']) || !empty($filters['account_status']) || !empty($filters['verified']) || !empty($filters['date_from']) || !empty($filters['date_to'])) ? 'active' : '' }}" id="upFiltersPanel">
            @if(!empty($search))
                <input type="hidden" name="search" value="{{ $search }}">
            @endif

            @php
                $upRoleOptions = ['' => 'كل الأنواع', 'admin' => 'مدراء', 'doctor' => 'أطباء', 'patient' => 'مرضى'];
                $upStatusOptions = ['' => 'كل الحالات', 'active' => 'نشط', 'suspended' => 'موقوف'];
                $upVerifiedOptions = ['' => 'كل التوثيق', 'verified' => 'موثّق', 'unverified' => 'غير موثّق'];
            @endphp

            <div class="up-custom-select">
                <button type="button" class="up-pill-field" onclick="upToggleDropdown(event, 'role')">
                    <i class="fa-solid fa-users"></i>
                    <span class="up-custom-select__label" id="roleLabel">{{ $upRoleOptions[$filters['role'] ?? ''] ?? 'كل الأنواع' }}</span>
                    <i class="fa-solid fa-chevron-down up-custom-select__chevron"></i>
                </button>
                <input type="hidden" name="role" id="roleInput" value="{{ $filters['role'] ?? '' }}">
                <div class="up-custom-select__panel" id="rolePanel">
                    @foreach ($upRoleOptions as $value => $label)
                        <button type="button" class="{{ ($filters['role'] ?? '') === $value ? 'is-active' : '' }}" onclick="upSelectOption(event, 'role', '{{ $value }}', '{{ $label }}')">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="up-custom-select">
                <button type="button" class="up-pill-field" onclick="upToggleDropdown(event, 'account_status')">
                    <i class="fa-solid fa-toggle-on"></i>
                    <span class="up-custom-select__label" id="account_statusLabel">{{ $upStatusOptions[$filters['account_status'] ?? ''] ?? 'كل الحالات' }}</span>
                    <i class="fa-solid fa-chevron-down up-custom-select__chevron"></i>
                </button>
                <input type="hidden" name="account_status" id="account_statusInput" value="{{ $filters['account_status'] ?? '' }}">
                <div class="up-custom-select__panel" id="account_statusPanel">
                    @foreach ($upStatusOptions as $value => $label)
                        <button type="button" class="{{ ($filters['account_status'] ?? '') === $value ? 'is-active' : '' }}" onclick="upSelectOption(event, 'account_status', '{{ $value }}', '{{ $label }}')">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="up-custom-select">
                <button type="button" class="up-pill-field" onclick="upToggleDropdown(event, 'verified')">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span class="up-custom-select__label" id="verifiedLabel">{{ $upVerifiedOptions[$filters['verified'] ?? ''] ?? 'كل التوثيق' }}</span>
                    <i class="fa-solid fa-chevron-down up-custom-select__chevron"></i>
                </button>
                <input type="hidden" name="verified" id="verifiedInput" value="{{ $filters['verified'] ?? '' }}">
                <div class="up-custom-select__panel" id="verifiedPanel">
                    @foreach ($upVerifiedOptions as $value => $label)
                        <button type="button" class="{{ ($filters['verified'] ?? '') === $value ? 'is-active' : '' }}" onclick="upSelectOption(event, 'verified', '{{ $value }}', '{{ $label }}')">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="up-custom-select">
                <button type="button" class="up-pill-field" onclick="upToggleCalendar(event, 'date_from')">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span class="up-date-display" id="date_fromLabel">{{ !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->locale('ar')->translatedFormat('d M Y') : 'من تاريخ' }}</span>
                </button>
                <input type="hidden" name="date_from" id="date_fromInput" value="{{ $filters['date_from'] ?? '' }}">
                <div class="up-calendar-panel" id="date_fromCalendar"></div>
            </div>

            <div class="up-custom-select">
                <button type="button" class="up-pill-field" onclick="upToggleCalendar(event, 'date_to')">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span class="up-date-display" id="date_toLabel">{{ !empty($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->locale('ar')->translatedFormat('d M Y') : 'إلى تاريخ' }}</span>
                </button>
                <input type="hidden" name="date_to" id="date_toInput" value="{{ $filters['date_to'] ?? '' }}">
                <div class="up-calendar-panel" id="date_toCalendar"></div>
            </div>

            <div class="up-toolbar-pro__actions">
                @if(!empty($filters['role']) || !empty($filters['account_status']) || !empty($filters['verified']) || !empty($filters['date_from']) || !empty($filters['date_to']))
                    <a href="{{ route('admin.admin-users', array_filter(['search' => $search ?? null])) }}" class="up-search-toggle up-search-toggle--reset" title="مسح الفلاتر" aria-label="مسح الفلاتر">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                    </a>
                @endif
                <button type="submit" class="up-btn-apply">تطبيق</button>
            </div>
        </form>

        {{-- ══════════════════════════════════════════
             شريط التحديد الجماعي (Action Bar)
        ══════════════════════════════════════════ --}}
        <div class="up-bulkbar" id="upBulkBar">
            <span class="up-bulkbar__badge"><i class="fa-solid fa-circle-check"></i> تم تحديد <strong id="upBulkCount">0</strong> مستخدمين</span>
            <div class="up-bulkbar__actions">
                <button type="button" class="up-tip-btn up-tip-btn--select" id="upSelectAllPrompt" data-tip="تحديد الكل" onclick="upSelectAllUsers()">
                    <i class="fa-solid fa-check-double"></i>
                </button>
                <button type="button" class="up-tip-btn" data-tip="إلغاء التحديد" onclick="upBulkClear()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <button type="button" class="up-tip-btn up-tip-btn--danger" data-tip="حذف المحددين" onclick="upBulkDelete()">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

        {{-- عناوين المسارات (Lane Pills) --}}
        @php
            $upColumns = [
                ['key' => 'admin', 'label' => 'مدراء', 'accent' => 'purple', 'icon' => 'fa-user-shield'],
                ['key' => 'doctor', 'label' => 'أطباء', 'accent' => 'blue', 'icon' => 'fa-user-doctor'],
                ['key' => 'patient', 'label' => 'مرضى', 'accent' => 'orange', 'icon' => 'fa-bed-pulse'],
            ];
            if (!empty($filters['role'])) {
                $upColumns = array_values(array_filter($upColumns, fn ($c) => $c['key'] === $filters['role']));
            }
        @endphp

        {{-- شبكة البطاقات: عنوان كل قسم جوا عموده مباشرة (يضلوا مع بعض مهما كان التخطيط) --}}
        <div class="up-board up-board--cols-{{ count($upColumns) }}">
            @php $upCardIndex = 0; @endphp
            @forelse ($upColumns as $col)
                @php $colUsers = $groupedUsers[$col['key']] ?? collect(); @endphp
                <div class="up-column">
                    <div class="up-lane-pill">
                        <span class="up-lane-pill__dot up-lane-pill__dot--{{ $col['accent'] }}"></span>
                        {{ $col['label'] }}
                        <span class="up-lane-pill__count">{{ $colUsers->count() }}</span>
                    </div>

                    @forelse ($colUsers as $user)
                        @php
                            $isSuspended = ($user->status ?? 'active') === 'suspended';
                            $isVerified = (bool) $user->email_verified_at;
                            $phone = optional($user->patientProfile)->phone;
                        @endphp
                        <div class="up-card" id="up-row-{{ $user->id }}" style="animation-delay: {{ min($upCardIndex++, 12) * 0.03 }}s">

                            {{-- Header --}}
                            <div class="up-card__top-row">
                                @if(auth()->id() !== $user->id)
                                    <input type="checkbox" class="up-checkbox up-row-checkbox" value="{{ $user->id }}" onchange="upUpdateBulkBar()">
                                @else
                                    <span></span>
                                @endif
                                <span class="up-status up-status--{{ $isSuspended ? 'suspended' : 'active' }}" id="up-status-{{ $user->id }}">
                                    <span class="up-status__dot"></span>
                                    {{ $isSuspended ? 'موقوف' : 'نشط' }}
                                </span>
                            </div>

                            <div class="up-card__body">
                                <div class="up-avatar up-avatar--{{ $col['accent'] }}">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                                <div class="up-card__info">
                                    <div class="up-user-name" id="up-name-{{ $user->id }}">{{ $user->name }}</div>
                                    <span class="up-role-badge up-role-badge--{{ $user->role }}">
                                        <span class="up-role-dot"></span>
                                        {{ $col['label'] }}
                                    </span>
                                </div>
                            </div>

                            {{-- Body details --}}
                            <div class="up-card__details">
                                <div class="up-card__detail-row">
                                    <i class="fa-solid fa-envelope"></i>
                                    <a href="mailto:{{ $user->email }}" class="up-user-email up-user-email--{{ $col['accent'] }}" id="up-email-{{ $user->id }}">{{ $user->email }}</a>
                                </div>
                                <div class="up-card__detail-row">
                                    <i class="fa-solid fa-phone"></i>
                                    <span>{{ $phone ?: '—' }}</span>
                                </div>
                                <div class="up-card__detail-row">
                                    <i class="fa-solid {{ $isVerified ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                                    <span class="{{ $isVerified ? 'up-text-good' : 'up-text-muted' }}">{{ $isVerified ? 'بريد موثّق' : 'غير موثّق' }}</span>
                                </div>
                                <div class="up-card__detail-row">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>آخر دخول: {{ $user->last_login_at?->locale('ar')->diffForHumans() ?? 'لسا ما دخل' }}</span>
                                </div>
                                <div class="up-card__detail-row">
                                    <i class="fa-solid fa-calendar-plus"></i>
                                    <span>انضم: {{ $user->created_at?->format('Y/m/d') }}</span>
                                </div>
                            </div>

                            {{-- Footer: 4 أزرار أيقونية زي ما هو متفق (View / Edit / Suspend / Delete) --}}
                            <div class="up-card__footer">
                                <div class="up-card__icon-actions">
                                    <button class="up-tip-btn up-tip-btn--view" data-tip="عرض التفاصيل" onclick="upOpenView({{ $user->id }})" aria-label="عرض تفاصيل {{ $user->name }}">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button class="up-tip-btn" data-tip="تعديل" onclick="upOpenEdit({{ $user->id }})" aria-label="تعديل {{ $user->name }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @if(auth()->id() !== $user->id)
                                        <button class="up-tip-btn" data-tip="{{ $isSuspended ? 'إعادة تفعيل' : 'تعليق' }}" onclick="upToggleStatus({{ $user->id }})" aria-label="تعليق أو تفعيل حساب {{ $user->name }}">
                                            <i class="fa-solid {{ $isSuspended ? 'fa-play' : 'fa-pause' }}"></i>
                                        </button>
                                        <button class="up-tip-btn up-tip-btn--danger" data-tip="حذف" onclick="upOpenDelete({{ $user->id }}, '{{ addslashes($user->name) }}')" aria-label="حذف {{ $user->name }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="up-column__empty">
                            <i class="fa-solid fa-inbox"></i>
                            لا يوجد مستخدمون هون
                        </div>
                    @endforelse
                </div>
            @empty
                <div class="up-column__empty">لا يوجد نتائج مطابقة</div>
            @endforelse
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         اللوحة الجانبية (يمين)
    ══════════════════════════════════════════ --}}
    <div class="up-side">

        <div class="up-side__greeting">
            <div class="up-side__avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
            <div>
                <span>أهلاً،</span>
                <strong>{{ auth()->user()->name ?? 'أدمن' }}</strong>
                <span class="up-role-badge up-role-badge--admin up-role-badge--sm">
                    <span class="up-role-dot"></span> مدير النظام
                </span>
            </div>
        </div>

        <div class="up-side__stats">
            <div class="up-side__stat up-side__stat--primary">
                <strong>{{ number_format($stats['total'] ?? 0) }}</strong>
                <span>إجمالي المستخدمين</span>
            </div>
            <div class="up-side__stat up-side__stat--blue">
                <strong>{{ number_format($stats['active'] ?? 0) }}</strong>
                <span>حسابات نشطة</span>
            </div>
            <div class="up-side__stat up-side__stat--orange">
                <strong>{{ ($stats['verified_rate'] ?? 0) }}%</strong>
                <span>نسبة التحقق</span>
            </div>
            <div class="up-side__stat up-side__stat--red">
                <strong>{{ number_format($stats['suspended'] ?? 0) }}</strong>
                <span>حسابات موقوفة</span>
            </div>
        </div>

        <div class="up-side__activity">
            <div class="up-side__activity-title">
                <i class="fa-solid fa-clock-rotate-left"></i>
                آخر الإجراءات
            </div>
            <div class="up-side__feed">
                @forelse (($recentActivity ?? collect()) as $log)
                    @php
                        $actionIcon = match(true) {
                            str_contains($log->action ?? '', 'حذف') => 'fa-trash',
                            str_contains($log->action ?? '', 'تعليق') => 'fa-pause',
                            str_contains($log->action ?? '', 'تفعيل') => 'fa-play',
                            str_contains($log->action ?? '', 'تعديل') => 'fa-pen',
                            default => 'fa-circle-info',
                        };
                    @endphp
                    <div class="up-feed-item">
                        <span class="up-feed-item__icon"><i class="fa-solid {{ $actionIcon }}"></i></span>
                        <div class="up-feed-item__content">
                            <div class="up-feed-item__bubble">
                                <strong>{{ $log->admin_name ?? 'أدمن' }}</strong>
                                {{ $log->description ?? $log->action }}
                            </div>
                            <span class="up-feed-item__time">{{ \Carbon\Carbon::parse($log->created_at)->locale('ar')->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="up-feed-empty">ما في إجراءات مسجلة لسا.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>

{{-- ══════════════════════════════════════════════════════
     MODAL: تعديل (Tabs: عام / الأمان)
══════════════════════════════════════════════════════ --}}
<div class="up-modal-overlay" id="upEditOverlay" onclick="upCloseEditOverlay(event)">
    <div class="up-modal" role="dialog" aria-labelledby="upEditTitle">

        <div class="up-modal__header">
            <h5 class="up-modal__title" id="upEditTitle">
                <span class="up-modal__title-icon"><i class="fa-solid fa-pen-to-square"></i></span>
                تعديل بيانات المستخدم
            </h5>
            <button class="up-modal__close" onclick="upCloseEdit()" aria-label="إغلاق">✕</button>
        </div>

        <div class="up-modal__tabs">
            <button type="button" class="up-modal__tab is-active" data-tab="general" onclick="upSwitchTab(event, 'general')">
                <i class="fa-solid fa-user"></i> عام
            </button>
            <button type="button" class="up-modal__tab" data-tab="security" onclick="upSwitchTab(event, 'security')">
                <i class="fa-solid fa-shield-halved"></i> الأمان
            </button>
        </div>

        <div class="up-modal__body">

            <div class="up-tab-panel is-active" data-panel="general">
                <div class="up-field">
                    <label class="up-label">الاسم الكامل *</label>
                    <input type="text" class="up-input" id="upEditName" placeholder="أدخل الاسم الكامل">
                    <div class="up-field-error" id="upEditNameErr"><i class="fa-solid fa-circle-exclamation"></i><span></span></div>
                </div>

                <div class="up-field">
                    <label class="up-label">البريد الإلكتروني *</label>
                    <input type="email" class="up-input" id="upEditEmail" placeholder="example@email.com" dir="ltr">
                    <div class="up-field-error" id="upEditEmailErr"><i class="fa-solid fa-circle-exclamation"></i><span></span></div>
                </div>

                <div class="up-field">
                    <label class="up-label">الدور *</label>
                    <select class="up-input" id="upEditRole">
                        <option value="admin">مدير</option>
                        <option value="doctor">طبيب</option>
                        <option value="patient">مريض</option>
                    </select>
                    <div class="up-field-error" id="upEditRoleErr"><i class="fa-solid fa-circle-exclamation"></i><span></span></div>
                </div>
            </div>

            <div class="up-tab-panel" data-panel="security">
                <div class="up-field">
                    <label class="up-label">كلمة مرور جديدة <span>(اتركها فارغة إن لم تريد التغيير)</span></label>
                    <input type="password" class="up-input" id="upEditPass" placeholder="••••••••" dir="ltr">
                    <div class="up-field-error" id="upEditPassErr"><i class="fa-solid fa-circle-exclamation"></i><span></span></div>
                </div>

                <div class="up-field">
                    <label class="up-label">تأكيد كلمة المرور</label>
                    <input type="password" class="up-input" id="upEditPassConf" placeholder="••••••••" dir="ltr">
                    <div class="up-field-error" id="upEditPassConfErr"><i class="fa-solid fa-circle-exclamation"></i><span></span></div>
                </div>
            </div>

        </div>

        <div class="up-modal__footer">
            <button class="up-btn-cancel" onclick="upCloseEdit()">إلغاء</button>
            <button class="up-btn-save" id="upSaveBtn" onclick="upSaveUser()">
                <span class="up-spinner" id="upSaveSpinner"></span>
                <span id="upSaveTxt">حفظ التغييرات</span>
            </button>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL: حذف
══════════════════════════════════════════════════════ --}}
<div class="up-modal-overlay" id="upDeleteOverlay" onclick="upCloseDeleteOverlay(event)">
    <div class="up-modal up-modal--sm" role="dialog" aria-labelledby="upDeleteTitle">

        <div class="up-modal__header">
            <h5 class="up-modal__title" id="upDeleteTitle">
                <span class="up-modal__title-icon up-modal__title-icon--danger"><i class="fa-solid fa-trash"></i></span>
                تأكيد الحذف
            </h5>
            <button class="up-modal__close" onclick="upCloseDelete()" aria-label="إغلاق">✕</button>
        </div>

        <div class="up-modal__body">
            <p style="color:var(--et-text);font-size:.9rem;margin:0;font-weight:750;">
                هل أنت متأكد من حذف حساب
                <span class="up-delete-name" id="upDeleteName"></span>؟
            </p>
            <div class="up-delete-box">
                ⚠️ <strong>تحذير:</strong>
                هذا الإجراء <strong>لا يمكن التراجع عنه.</strong>
                سيتم حذف جميع بيانات هذا المستخدم نهائياً، ولن يستطيع تسجيل الدخول مجدداً.
            </div>
        </div>

        <div class="up-modal__footer">
            <button class="up-btn-cancel" onclick="upCloseDelete()">إلغاء</button>
            <button class="up-btn-danger" id="upDeleteBtn" onclick="upConfirmDelete()">
                <span class="up-spinner" id="upDeleteSpinner"></span>
                <span id="upDeleteTxt">نعم، احذف الحساب</span>
            </button>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     MODAL: عرض التفاصيل الكاملة (Premium View)
══════════════════════════════════════════════════════ --}}
<div class="up-modal-overlay" id="upViewOverlay" onclick="upCloseViewOverlay(event)">
    <div class="up-modal" role="dialog" aria-labelledby="upViewTitle">

        <div class="up-modal__header">
            <h5 class="up-modal__title" id="upViewTitle">
                <span class="up-modal__title-icon"><i class="fa-solid fa-user"></i></span>
                تفاصيل المستخدم
            </h5>
            <button class="up-modal__close" onclick="upCloseView()" aria-label="إغلاق">✕</button>
        </div>

        <div class="up-modal__body" id="upViewBody">
            <div class="up-view-loading">جاري التحميل...</div>
        </div>

        <div class="up-modal__footer">
            <button class="up-btn-cancel" onclick="upCloseView()">إغلاق</button>
            <button class="up-btn-save" id="upViewEditBtn">
                <i class="fa-solid fa-pen-to-square"></i> تعديل
            </button>
        </div>

    </div>
</div>

{{-- Toast --}}
<div class="up-toast-container" id="upToasts"></div>

@endsection

@push('scripts')
<script src="{{ asset('front/js/admin-users.js') }}"></script>
@endpush
