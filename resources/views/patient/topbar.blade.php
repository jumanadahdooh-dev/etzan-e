@php
    $authUser = auth()->user();

    $patientProfile = null;
    $profileColumns = collect();

    if ($authUser && \Illuminate\Support\Facades\Schema::hasTable('patient_profiles')) {
        $patientProfile = \Illuminate\Support\Facades\DB::table('patient_profiles')
            ->where('user_id', $authUser->id)
            ->first();

        $profileColumns = collect(\Illuminate\Support\Facades\Schema::getColumnListing('patient_profiles'));
    }

    $defaultPatientName = trim(
        data_get($patientProfile, 'full_name')
        ?? data_get($patientProfile, 'name')
        ?? data_get($patientProfile, 'patient_name')
        ?? data_get($authUser, 'full_name')
        ?? data_get($authUser, 'name')
        ?? 'مستخدم اتزان'
    );

    $defaultPatientEmail = trim(
        data_get($patientProfile, 'email')
        ?? data_get($authUser, 'email')
        ?? ''
    );

    $defaultPatientInitial = mb_substr($defaultPatientName, 0, 1, 'UTF-8');

    $defaultAvatarPath =
        data_get($patientProfile, 'avatar')
        ?? data_get($patientProfile, 'photo')
        ?? data_get($patientProfile, 'image')
        ?? data_get($patientProfile, 'profile_photo')
        ?? data_get($patientProfile, 'profile_photo_path')
        ?? data_get($authUser, 'avatar')
        ?? data_get($authUser, 'photo')
        ?? data_get($authUser, 'image')
        ?? data_get($authUser, 'profile_photo_path')
        ?? null;

    $defaultAvatarUrl = null;

    if (!empty($defaultAvatarPath)) {
        $defaultAvatarUrl = \Illuminate\Support\Str::startsWith($defaultAvatarPath, ['http://', 'https://'])
            ? $defaultAvatarPath
            : asset('storage/' . ltrim($defaultAvatarPath, '/'));
    }

    /*
     |--------------------------------------------------------------------------
     | حساب اكتمال الملف الصحي تلقائيًا
     |--------------------------------------------------------------------------
     | لو جدول patient_profiles فيه أعمدة كثيرة، بنحسب فقط الأعمدة المهمة
     | ونستبعد أعمدة النظام والصور والملاحظات.
     */
    $ignoredCompletionColumns = [
        'id',
        'user_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'avatar',
        'photo',
        'image',
        'profile_photo',
        'profile_photo_path',
        'profile_completion',
        'completion_percentage',
        'admin_notes',
        'notes',
        'doctor_note',
        'doctor_user_id',
        'selected_doctor_id',
        'status',
    ];

    $preferredCompletionFields = [
        'full_name',
        'name',
        'patient_name',
        'gender',
        'birth_date',
        'date_of_birth',
        'age',
        'phone',
        'height',
        'weight',
        'goal',
        'health_goal',
        'activity_level',
        'diet_type',
        'medical_conditions',
        'chronic_diseases',
        'allergies',
        'medications',
        'food_allergies',
        'emergency_contact',
    ];

    $existingPreferredFields = collect($preferredCompletionFields)
        ->filter(fn ($field) => $profileColumns->contains($field))
        ->unique()
        ->values();

    $completionFields = $existingPreferredFields->isNotEmpty()
        ? $existingPreferredFields
        : $profileColumns
            ->reject(fn ($column) => in_array($column, $ignoredCompletionColumns, true) || str_ends_with($column, '_at'))
            ->values();

    $calculatedProfileCompletion = 0;

    if ($patientProfile && $completionFields->isNotEmpty()) {
        $filledFields = $completionFields->filter(function ($field) use ($patientProfile) {
            $value = data_get($patientProfile, $field);

            if (is_array($value)) {
                return count($value) > 0;
            }

            return !is_null($value) && trim((string) $value) !== '';
        })->count();

        $calculatedProfileCompletion = (int) round(($filledFields / $completionFields->count()) * 100);
        $calculatedProfileCompletion = max(0, min(100, $calculatedProfileCompletion));
    }

    $storedProfileCompletion =
        data_get($patientProfile, 'profile_completion')
        ?? data_get($patientProfile, 'completion_percentage')
        ?? null;

    $finalProfileCompletion = !is_null($storedProfileCompletion) && (int) $storedProfileCompletion > 0
        ? (int) $storedProfileCompletion
        : $calculatedProfileCompletion;

    $patient = array_merge([
        'name' => $defaultPatientName,
        'email' => $defaultPatientEmail,
        'initial' => $defaultPatientInitial,
        'avatar' => $defaultAvatarUrl,
        'profile_completion' => $finalProfileCompletion,
    ], $patient ?? []);

    $patient['name'] = trim($patient['name'] ?? '') !== ''
        ? $patient['name']
        : $defaultPatientName;

    $patient['email'] = trim($patient['email'] ?? '') !== ''
        ? $patient['email']
        : $defaultPatientEmail;

    $patient['initial'] = trim($patient['initial'] ?? '') !== ''
        ? $patient['initial']
        : mb_substr($patient['name'], 0, 1, 'UTF-8');

    $patient['avatar'] = !empty($patient['avatar'])
        ? $patient['avatar']
        : $defaultAvatarUrl;

    /*
     | مهم:
     | هنا نغلب الحساب الجديد على القيمة الافتراضية 0
     | عشان ما يظل اكتمال الملف فاضي.
     */
    $patient['profile_completion'] = $finalProfileCompletion;

    $unreadMessages = $unreadMessages ?? 0;
    $notificationsCount = $notificationsCount ?? 0;
    $patientNotifications = $patientNotifications ?? [];
@endphp

<meta name="patient-live-notifications-url" content="{{ route('patient.live.notifications') }}">

<header class="patient-topbar patient-topbar-simple">
    <div class="topbar-simple-greeting">
        <button type="button" class="mobile-menu-btn simple-mobile-menu" data-sidebar-open aria-label="فتح القائمة">
            <i data-lucide="menu"></i>
        </button>

        <div class="topbar-greeting-icon">
            <i data-lucide="sparkles"></i>
        </div>

        <div class="topbar-greeting-copy">
            <span>مرحباً بك</span>
            <h1>{{ $patient['name'] ?? 'مستخدم اتزان' }}</h1>
        </div>
    </div>

    <div class="topbar-simple-actions">
        <a href="{{ route('patient.messages') }}" class="simple-icon-btn" aria-label="الرسائل">
            <i data-lucide="mail"></i>

            <span
                class="topbar-badge"
                data-live-messages-count
                style="{{ ($unreadMessages ?? 0) > 0 ? '' : 'display:none;' }}"
            >
                {{ ($unreadMessages ?? 0) > 9 ? '9+' : ($unreadMessages ?? 0) }}
            </span>
        </a>

        <div class="topbar-notification-wrap" data-notification-wrap>
            <button
                type="button"
                class="simple-icon-btn patient-notification-bell"
                data-notification-toggle
                aria-label="الإشعارات"
                aria-expanded="false"
            >
                <i data-lucide="bell"></i>

                <span
                    class="topbar-badge"
                    data-live-notifications-count
                    style="{{ ($notificationsCount ?? 0) > 0 ? '' : 'display:none;' }}"
                >
                    {{ ($notificationsCount ?? 0) > 9 ? '9+' : ($notificationsCount ?? 0) }}
                </span>
            </button>

            <div class="notification-popover" data-notification-menu>
                <div class="notification-popover-head">
                    <div>
                        <span>مركز الإشعارات</span>
                        <strong>آخر التحديثات</strong>
                    </div>

                    @if ($notificationsCount > 0)
                        <form method="POST" action="{{ route('patient.notifications.read-all') }}">
                            @csrf

                            <button type="submit" class="mark-all-read-btn">
                                تعليم الكل كمقروء
                            </button>
                        </form>
                    @endif
                </div>

                <div class="notification-popover-list" data-live-notifications-list>
                    @forelse (array_slice($patientNotifications, 0, 5) as $notification)
                        @php
                            $type = $notification['type'] ?? 'general';

                            $icon = match ($type) {
                                'appointment_pending' => 'clock-3',
                                'appointment_confirmed' => 'badge-check',
                                'appointment_rejected' => 'circle-alert',
                                'appointment_reschedule_requested' => 'calendar-clock',
                                'appointment_reminder_60' => 'alarm-clock',
                                'appointment_reminder_10' => 'timer',
                                'appointment_starting_now' => 'radio',
                                'doctor_request_pending' => 'stethoscope',
                                'doctor_request_approved' => 'badge-check',
                                'doctor_request_rejected' => 'circle-alert',
                                'message_received' => 'message-circle',
                                'support_reply' => 'life-buoy',
                                'support_message_sent' => 'life-buoy',
                                'task_reminder' => 'list-checks',
                                'task_due' => 'alarm-clock',
                                'task_late' => 'circle-alert',
                                default => 'bell-ring',
                            };
                        @endphp

                        <a
                            href="{{ $notification['url'] ?? route('patient.notifications') }}"
                            class="notification-popover-item {{ empty($notification['is_read']) ? 'is-unread' : '' }}"
                        >
                            <span class="notification-popover-icon">
                                <i data-lucide="{{ $icon }}"></i>
                            </span>

                            <div>
                                <strong>{{ $notification['title'] ?? 'إشعار' }}</strong>
                                <p>{{ $notification['body'] ?? '' }}</p>
                                <small>{{ $notification['time'] ?? '' }}</small>
                            </div>
                        </a>
                    @empty
                        <div class="notification-popover-empty">
                            <i data-lucide="bell-off"></i>
                            <strong>لا توجد إشعارات الآن</strong>
                            <span>ستظهر هنا تذكيرات المواعيد وتحديثات الطبيب.</span>
                        </div>
                    @endforelse
                </div>

                <div class="notification-popover-footer">
                    <a href="{{ route('patient.notifications') }}">
                        عرض كل الإشعارات
                        <i data-lucide="arrow-left"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="topbar-profile">
            <button type="button" class="simple-avatar-btn" data-profile-toggle aria-expanded="false" aria-label="قائمة الحساب">
                <span class="profile-avatar">
                    @if (!empty($patient['avatar']))
                        <img src="{{ $patient['avatar'] }}" alt="صورة {{ $patient['name'] }}">
                    @else
                        <span>{{ $patient['initial'] ?? 'م' }}</span>
                    @endif
                </span>
            </button>

            <div class="profile-menu" data-profile-menu>
                <div class="profile-menu-head">
                    <span class="profile-menu-avatar">
                        @if (!empty($patient['avatar']))
                            <img src="{{ $patient['avatar'] }}" alt="صورة {{ $patient['name'] }}">
                        @else
                            <span>{{ $patient['initial'] ?? 'م' }}</span>
                        @endif
                    </span>

                    <div>
                        <strong>{{ $patient['name'] ?? 'مستخدم اتزان' }}</strong>
                        <small>{{ !empty($patient['email']) ? $patient['email'] : 'حساب المريض' }}</small>
                    </div>
                </div>

                <div class="profile-completion-mini">
                    <div>
                        <span>اكتمال الملف الصحي</span>
                        <strong>{{ $patient['profile_completion'] ?? 0 }}%</strong>
                    </div>

                    <div class="soft-progress">
                        <span style="width: {{ $patient['profile_completion'] ?? 0 }}%"></span>
                    </div>
                </div>

                <a href="{{ route('patient.profile') }}">
                    <i data-lucide="user-round"></i>
                    <span>الملف الشخصي</span>
                </a>

                

                <button type="button" class="theme-toggle" data-theme-toggle>
    <i data-lucide="moon"></i>
    <span data-theme-label>الوضع الداكن</span>
</button>

                @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit" class="logout-menu-btn">
                            <i data-lucide="log-out"></i>
                            <span>تسجيل الخروج</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</header>

<script src="{{ asset('front/js/patient-live.js') }}"></script>
