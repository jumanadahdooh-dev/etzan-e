@extends('layouts.patient')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/journey.css') }}">
@endpush

@section('content')
@php
    $tasks = collect($tasks ?? []);
    $calendarTasksSource = collect($calendarTasksSource ?? $tasks);

    $isTaskLate = function ($task): bool {
        $status = data_get($task, 'status');

        if ($status === 'completed') {
            return false;
        }

        if (in_array($status, ['missed', 'late'], true)) {
            return true;
        }

        return filled(data_get($task, 'late_notification_sent_at'));
    };

    $lateTasksCount = $tasks->filter($isTaskLate)->count();

    $stats = $stats ?? [
        'total' => $tasks->count(),
        'completed' => $tasks->where('status', 'completed')->count(),
        'pending' => $tasks->filter(fn ($task) => data_get($task, 'status') === 'pending' && ! $isTaskLate($task))->count(),
        'missed' => $lateTasksCount,
        'doctor' => $tasks->where('source', 'doctor')->count(),
        'patient' => $tasks->whereIn('source', ['patient', 'self'])->count(),
        'progress' => 0,
    ];

    $hasAnyJourneyTasks = $tasks->isNotEmpty() || $calendarTasksSource->isNotEmpty();

    $doctorNote = $doctorNote ?? null;

    $taskDateValue = function ($task) {
        $date = data_get($task, 'task_date') ?? data_get($task, 'date') ?? now();

        try {
            return $date instanceof \Carbon\Carbon
                ? $date->toDateString()
                : \Carbon\Carbon::parse($date)->toDateString();
        } catch (\Throwable $e) {
            return now()->toDateString();
        }
    };

    $formatArabicTime = function ($time) {
        if (! $time) {
            return 'بدون وقت';
        }

        try {
            $parsed = \Carbon\Carbon::parse($time);
            $hour = (int) $parsed->format('H');
            $minute = $parsed->format('i');
            $period = $hour >= 12 ? 'مساءً' : 'صباحًا';

            $displayHour = $hour % 12;
            $displayHour = $displayHour === 0 ? 12 : $displayHour;

            return $displayHour . ':' . $minute . ' ' . $period;
        } catch (\Throwable $e) {
            return 'بدون وقت';
        }
    };

    $statusData = function ($task) use ($taskDateValue, $isTaskLate) {
        $status = data_get($task, 'status');

        if ($status === 'completed') {
            return [
                'key' => 'completed',
                'class' => 'is-completed',
                'label' => 'تم إنجازها',
                'icon' => '✓',
            ];
        }

        if ($isTaskLate($task)) {
            return [
                'key' => 'late',
                'class' => 'is-late',
                'label' => 'متأخرة',
                'icon' => '!',
            ];
        }

        $taskTime = data_get($task, 'task_time');

        if ($taskTime) {
            try {
                $taskDateTime = \Carbon\Carbon::parse($taskDateValue($task) . ' ' . $taskTime);

                if (now()->between($taskDateTime->copy()->subMinutes(20), $taskDateTime->copy()->addMinutes(30))) {
                    return [
                        'key' => 'now',
                        'class' => 'is-now',
                        'label' => 'حان وقتها',
                        'icon' => '!',
                    ];
                }
            } catch (\Throwable $e) {
                //
            }
        }

        return [
            'key' => 'pending',
            'class' => 'is-pending',
            'label' => 'قادمة',
            'icon' => '○',
        ];
    };

    $sourceData = function ($task) {
        $source = data_get($task, 'source');

        if ($source === 'doctor') {
            return [
                'key' => 'doctor',
                'class' => 'doctor',
                'label' => 'من الطبيب',
            ];
        }

        return [
            'key' => 'self',
            'class' => 'self',
            'label' => 'منّي',
        ];
    };

    $taskTitle = function ($task) {
        return data_get($task, 'title')
            ?? data_get($task, 'name')
            ?? data_get($task, 'task_title')
            ?? 'مهمة يومية';
    };

    $taskDescription = function ($task) {
        return data_get($task, 'description')
            ?? data_get($task, 'details')
            ?? data_get($task, 'notes')
            ?? 'مهمة ضمن رحلتك اليومية في اتزان.';
    };

    $totalTasks = (int) ($stats['total'] ?? $tasks->count());
    $completedTasks = (int) ($stats['completed'] ?? $tasks->where('status', 'completed')->count());
    $lateTasks = $lateTasksCount;
    $remainingTasks = max($totalTasks - $completedTasks - $lateTasks, 0);
    $progressValue = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

    $todayValue = now()->toDateString();
    $todayLabel = now()->translatedFormat('d F، Y');

    $scheduledTasks = $tasks
        ->filter(fn ($task) => data_get($task, 'task_time'))
        ->sortBy(fn ($task) => data_get($task, 'task_time'))
        ->values();

    $calendarTasks = $calendarTasksSource->map(function ($task) use ($taskTitle, $taskDescription, $statusData, $sourceData, $taskDateValue, $formatArabicTime) {
        $status = $statusData($task);
        $source = $sourceData($task);
        $attachmentPath = data_get($task, 'attachment_path');
        $attachmentExtension = strtolower(pathinfo((string) $attachmentPath, PATHINFO_EXTENSION));

        return [
            'id' => data_get($task, 'id') ?? ('task_' . md5($taskTitle($task) . $taskDateValue($task) . data_get($task, 'task_time'))),
            'title' => $taskTitle($task),
            'description' => $taskDescription($task),
            'date' => $taskDateValue($task),
            'time' => data_get($task, 'task_time') ? \Carbon\Carbon::parse(data_get($task, 'task_time'))->format('H:i') : '',
            'timeLabel' => $formatArabicTime(data_get($task, 'task_time')),
            'status' => $status['key'],
            'statusLabel' => $status['label'],
            'source' => $source['key'],
            'sourceLabel' => $source['label'],
            'patientNote' => data_get($task, 'patient_note') ?: '',
            'hasAttachment' => $source['key'] === 'doctor' && filled($attachmentPath),
            'attachmentUrl' => $source['key'] === 'doctor' && filled($attachmentPath)
                ? route('patient.journey.tasks.attachment.show', $task)
                : '',
            'attachmentName' => $attachmentPath ? basename($attachmentPath) : '',
            'attachmentType' => 'image',
        ];
    })->values();
@endphp

<section class="journey-page">

    @if (session('success'))
        <div class="journey-alert is-success">
            <span class="journey-alert-icon">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="journey-alert is-error">
            <span class="journey-alert-icon">!</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="journey-alert is-error">
            <span class="journey-alert-icon">!</span>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="journey-board-page" data-today="{{ $todayValue }}" dir="rtl">

        <script type="application/json" id="journeyTasksSeed">
            @json($calendarTasks, JSON_UNESCAPED_UNICODE)
        </script>

        <header class="journey-board-header">
            <div class="journey-header-copy">
                <span class="journey-header-eyebrow">لوحة المريض اليومية</span>

                <h1>رحلتي اليوم</h1>

                <p>
                    تابع مهامك اليومية بخطوات واضحة، واضغط على أي يوم لمعرفة
                    تفاصيل المهام ومتابعة تقدمك بشكل مرتب ومريح.
                </p>

                <div class="journey-header-tags">
                    <span class="journey-header-tag">مهام اليوم</span>
                    <span class="journey-header-tag">متابعة الطبيب</span>
                    <span class="journey-header-tag">خطة واضحة</span>
                </div>
            </div>

            <div class="journey-header-actions">
                <div class="journey-header-date-card">
                    <span>تاريخ اليوم</span>
                    <strong>{{ $todayLabel }}</strong>
                </div>

                <button type="button" class="journey-add-task-btn" id="openJourneyModal">
                    <span>إضافة مهمة</span>
                    <i>+</i>
                </button>
            </div>
        </header>

        <div class="journey-board-shell">

            <main class="journey-main-panel">

                <div class="journey-panel-head">
                    <div>
                        <span class="panel-label">Mission Board</span>
                        <h2>لوحة مهام اليوم</h2>
                    </div>

                    <div class="panel-date" id="journeyPanelDate">
                        {{ $todayLabel }}
                    </div>
                </div>

                <div class="journey-tabs" role="tablist">
                    <button type="button" class="journey-tab is-active" data-filter="all">الكل</button>
                    <button type="button" class="journey-tab" data-filter="doctor">من الطبيب</button>
                    <button type="button" class="journey-tab" data-filter="self">منّي</button>
                    <button type="button" class="journey-tab" data-filter="completed">مكتملة</button>
                    <button type="button" class="journey-tab" data-filter="late">متأخرة</button>
                </div>

                <section class="journey-task-grid journey-mission-timeline" id="journeyTaskGrid">
                    <div class="mission-timeline-head">
                        <div>
                            <span>Daily Mission Stack</span>
                            <h3>مهام اليوم</h3>
                        </div>
                    </div>

                    <div class="mission-timeline-list">
                        @foreach ($tasks as $task)
                            @php
                                $cardStatus = $statusData($task);
                                $cardSource = $sourceData($task);
                                $timeLabel = $formatArabicTime(data_get($task, 'task_time'));
                                $taskDate = $taskDateValue($task);
                                $cardId = data_get($task, 'id') ?? ('task_' . md5($taskTitle($task) . $taskDate . data_get($task, 'task_time')));

                                $patientNote = data_get($task, 'patient_note');

                                $canUploadResultImage = $cardSource['key'] === 'doctor';

                                $attachmentPath = data_get($task, 'attachment_path');
                                $hasAttachment = $canUploadResultImage && filled($attachmentPath);

                                $attachmentExtension = strtolower(pathinfo((string) $attachmentPath, PATHINFO_EXTENSION));
                                $isImageAttachment = in_array($attachmentExtension, ['jpg', 'jpeg', 'png', 'webp'], true);

                                $resultImageUrl = ($hasAttachment && $isImageAttachment)
                                    ? route('patient.journey.tasks.attachment.show', $task)
                                    : null;

                                $canDeleteTask = $cardSource['key'] === 'self';
                            @endphp

                            <article
                                class="mission-task-item {{ $cardStatus['class'] }}"
                                data-task-id="{{ $cardId }}"
                                data-date="{{ $taskDate }}"
                                data-source="{{ $cardSource['key'] }}"
                                data-status="{{ $cardStatus['key'] }}"
                            >
                                <div class="mission-line">
                                    @if ($cardStatus['key'] === 'completed')
                                        <button type="button" class="mission-check-btn is-done" disabled aria-label="تم إنجاز المهمة">
                                            ✓
                                        </button>
                                    @else
                                        <form
                                            method="POST"
                                            action="{{ route('patient.journey.tasks.complete', $task) }}"
                                            class="mission-check-form"
                                            data-server-action="complete"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit" class="mission-check-btn complete-task" aria-label="إنجاز المهمة">
                                                <span></span>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <div class="mission-card">
                                    <div class="mission-card-main">
                                        <div class="mission-copy">
                                            <div class="mission-meta">
                                                <span class="mission-time-chip">{{ $timeLabel }}</span>
                                                <span class="source-badge {{ $cardSource['class'] }}">{{ $cardSource['label'] }}</span>
                                                <span class="status-badge {{ $cardStatus['key'] }}">{{ $cardStatus['label'] }}</span>

                                                @if ($hasAttachment)
                                                    <span class="attachment-chip">صورة نتيجة</span>
                                                @endif
                                            </div>

                                            <h3 class="task-title">{{ $taskTitle($task) }}</h3>
                                            <p class="task-desc">{{ $taskDescription($task) }}</p>

                                            @if (filled($patientNote))
                                                <div class="task-patient-note">
                                                    <strong>ملاحظتك:</strong>
                                                    <span>{{ $patientNote }}</span>
                                                </div>
                                            @endif

                                            @if ($hasAttachment && $resultImageUrl)
                                                <div class="task-attachment-preview">
                                                    <a href="{{ $resultImageUrl }}" target="_blank" class="task-attachment-image-link">
                                                        <img src="{{ $resultImageUrl }}" alt="صورة نتيجة المهمة">
                                                        <span>عرض صورة النتيجة</span>
                                                    </a>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="mission-actions">
                                            <div class="mission-menu-wrap">
                                                <button type="button" class="mission-menu-toggle" aria-label="خيارات المهمة">
                                                    ⋯
                                                </button>

                                                <div class="mission-menu">
                                                    <button
                                                        type="button"
                                                        class="mission-menu-item note-task"
                                                        data-note-task
                                                        data-note-action="{{ route('patient.journey.tasks.note', $task) }}"
                                                        data-note-title="{{ e($taskTitle($task)) }}"
                                                        data-note-current="{{ e($patientNote ?? '') }}"
                                                    >
                                                        {{ filled($patientNote) ? 'تعديل الملاحظة' : 'إضافة ملاحظة' }}
                                                    </button>

                                                    @if ($canUploadResultImage)
                                                            <form
                                                                method="POST"
                                                                action="{{ route('patient.journey.tasks.attachment', $task) }}"
                                                                enctype="multipart/form-data"
                                                                class="task-upload-form"
                                                            >
                                                                @csrf

                                                                <input
                                                                    type="file"
                                                                    name="attachment"
                                                                    class="task-attachment-input"
                                                                    accept="image/jpeg,image/png,image/webp"
                                                                    hidden
                                                                >

                                                                <button type="button" class="mission-menu-item upload-task">
                                                                    {{ $hasAttachment ? 'تغيير صورة النتيجة' : 'رفع صورة النتيجة' }}
                                                                </button>
                                                            </form>
                                                        @endif

                                                    @if ($canDeleteTask)
                                                        <form
                                                            method="POST"
                                                            action="{{ route('patient.journey.tasks.destroy', $task) }}"
                                                            class="task-delete-form"
                                                        >
                                                            @csrf
                                                            @method('DELETE')

                                                            <button type="submit" class="mission-menu-item is-danger">
                                                                حذف المهمة
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mission-filter-empty" id="missionFilterEmpty" hidden>
                        <div class="mission-filter-empty-icon">⌁</div>

                        <div>
                            <h4 id="missionFilterEmptyTitle">لا توجد مهام ضمن هذا التصنيف</h4>
                            <p id="missionFilterEmptyText">جرّب اختيار تبويب الكل لعرض كل مهام اليوم.</p>
                        </div>

                        <button type="button" id="showAllTasksFromEmpty">
                            عرض الكل
                        </button>
                    </div>
                </section>

                <section
                    class="journey-empty-state"
                    id="journeyEmptyState"
                    @if ($hasAnyJourneyTasks) hidden @endif
                >
                    <div class="empty-icon">✓</div>
                    <h3>لا توجد مهام اليوم</h3>
                    <p>أضف مهمة شخصية أو انتظر مهام الطبيب.</p>

                    <button type="button" class="journey-add-btn" id="openJourneyModalEmpty">
                        <span>+</span>
                        إضافة مهمة
                    </button>
                </section>

                <section class="journey-calendar-planner" aria-label="تقويم الرحلة">
                    <div class="calendar-planner-head">
                        <div>
                            <h3>تقويم الرحلة</h3>
                            <p>اختر اليوم، الأسبوع، أو الشهر واضغط على أي يوم لعرض تفاصيل مهامه.</p>
                        </div>

                        <div class="calendar-mode-tabs">
                            <button type="button" class="calendar-mode is-active" data-calendar-mode="day">اليوم</button>
                            <button type="button" class="calendar-mode" data-calendar-mode="week">الأسبوع</button>
                            <button type="button" class="calendar-mode" data-calendar-mode="month">الشهر</button>
                        </div>
                    </div>

                    <div class="calendar-toolbar">
                        <button type="button" class="calendar-nav-btn" id="calendarPrevBtn">‹</button>

                        <div class="calendar-current-title">
                            <span id="calendarCurrentRange">اليوم</span>
                            <strong id="calendarSelectedLabel">{{ $todayLabel }}</strong>
                        </div>

                        <button type="button" class="calendar-nav-btn" id="calendarNextBtn">›</button>
                    </div>

                    <div class="journey-calendar-area">
                        <div class="journey-calendar-view" id="journeyCalendarView"></div>

                        <aside class="calendar-day-details" id="calendarDayDetails">
                            <div class="day-details-head">
                                <span>تفاصيل اليوم</span>
                                <h3 id="selectedDayTitle">اليوم</h3>
                            </div>

                            <div class="day-details-summary">
                                <div>
                                    <strong id="selectedDayTotal">0</strong>
                                    <span>مهام</span>
                                </div>

                                <div>
                                    <strong id="selectedDayCompleted">0</strong>
                                    <span>مكتملة</span>
                                </div>

                                <div>
                                    <strong id="selectedDayLate">0</strong>
                                    <span>متأخرة</span>
                                </div>
                            </div>

                            <div class="selected-day-tasks" id="selectedDayTasks"></div>

                            <button type="button" class="details-add-btn" id="addTaskForSelectedDay">
                                + إضافة مهمة لهذا اليوم
                            </button>
                        </aside>
                    </div>

                    <div class="calendar-legend">
                        <span><i class="legend-dot doctor"></i> من الطبيب</span>
                        <span><i class="legend-dot self"></i> منّي</span>
                        <span><i class="legend-dot now"></i> حان وقتها</span>
                        <span><i class="legend-dot late"></i> متأخرة</span>
                        <span><i class="legend-dot completed"></i> مكتملة</span>
                    </div>
                </section>

            </main>

            <aside class="journey-side-panel">

                <section class="schedule-card">
                    <div class="side-card-head">
                        <div>
                            <span class="side-label">Today Schedule</span>
                            <h3>جدول اليوم</h3>
                        </div>

                        <span class="side-count">{{ $totalTasks }} مهام</span>
                    </div>

                    <div class="schedule-list" id="journeyScheduleList">
                        @forelse ($scheduledTasks->take(7) as $task)
                            @php
                                $scheduleStatus = $statusData($task);
                                $scheduleSource = $sourceData($task);

                                $scheduleClass = $scheduleStatus['key'] === 'late'
                                    ? 'late'
                                    : ($scheduleStatus['key'] === 'now'
                                        ? 'now'
                                        : ($scheduleStatus['key'] === 'completed'
                                            ? 'completed'
                                            : $scheduleSource['key']));
                            @endphp

                            <div class="schedule-item {{ $scheduleClass }}">
                                <span class="schedule-dot"></span>

                                <div>
                                    <h4>{{ $taskTitle($task) }}</h4>
                                    <p>{{ $formatArabicTime(data_get($task, 'task_time')) }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="schedule-empty">
                                لا يوجد جدول زمني اليوم.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="progress-card">
                    <div class="side-card-head">
                        <div>
                            <span class="side-label">Daily Progress</span>
                            <h3>تقدم اليوم</h3>
                        </div>
                    </div>

                    <div class="progress-content">
                        <div class="progress-ring" id="journeyProgressRing" style="--progress: {{ $progressValue }}%;">
                            <div class="progress-ring-inner">
                                <strong id="journeyProgressPercent">{{ $progressValue }}%</strong>
                                <span>إنجاز</span>
                            </div>
                        </div>

                        <div class="progress-stats">
                            <div>
                                <strong id="completedCount">{{ $completedTasks }}</strong>
                                <span>مكتملة</span>
                            </div>

                            <div>
                                <strong id="pendingCount">{{ $remainingTasks }}</strong>
                                <span>متبقية</span>
                            </div>

                            <div>
                                <strong id="lateCount">{{ $lateTasks }}</strong>
                                <span>متأخرة</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="doctor-note-card">
                    <div class="note-badge">متابعة الطبيب</div>
                    <h3>ملاحظة الطبيب</h3>

                    @if (!empty($doctorNote))
                        <p>{{ $doctorNote }}</p>
                    @else
                        <p class="doctor-note-empty">
                            لا توجد ملاحظة جديدة من الطبيب حاليًا.
                        </p>
                    @endif
                </section>

            </aside>
        </div>

        <div class="journey-modal" id="journeyModal" aria-hidden="true">
            <div class="journey-modal-backdrop" data-close-modal></div>

            <div class="journey-modal-box" role="dialog" aria-modal="true" aria-labelledby="journeyModalTitle">
                <div class="journey-modal-head">
                    <div>
                        <span class="modal-label">مهمة شخصية</span>
                        <h3 id="journeyModalTitle">إضافة مهمة جديدة</h3>
                    </div>

                    <button type="button" class="modal-close" data-close-modal>×</button>
                </div>

                <form class="journey-form" id="journeyTaskForm" method="POST" action="{{ route('patient.journey.tasks.store') }}">
                    @csrf

                    <label class="journey-field" data-error="اكتب عنوان المهمة">
                        عنوان المهمة
                        <input
                            type="text"
                            id="taskTitleInput"
                            name="title"
                            value="{{ old('title') }}"
                            placeholder="مثال: شرب كوب ماء قبل النوم"
                            required
                            minlength="3"
                        >
                    </label>

                    <label class="journey-field" data-error="اختر تاريخ المهمة">
                        تاريخ المهمة
                        <input
                            type="date"
                            id="taskDateInput"
                            name="task_date"
                            value="{{ old('task_date', $todayValue) }}"
                            required
                        >
                    </label>

                    <label class="journey-field" data-error="اختر وقت المهمة">
                        الوقت
                        <input
                            type="time"
                            id="taskTimeInput"
                            name="task_time"
                            value="{{ old('task_time') }}"
                            required
                        >
                    </label>

                    <label>
                        الوصف
                        <textarea
                            id="taskDescInput"
                            name="description"
                            rows="4"
                            placeholder="اكتب وصفًا قصيرًا للمهمة"
                        >{{ old('description') }}</textarea>
                    </label>

                    <label>
                        التذكير
                        <select id="taskReminderInput" name="reminder_minutes">
                            <option value="0" @selected(old('reminder_minutes', 0) == 0)>بدون تذكير</option>
                            <option value="10" @selected(old('reminder_minutes') == 10)>قبل 10 دقائق</option>
                            <option value="15" @selected(old('reminder_minutes') == 15)>قبل 15 دقيقة</option>
                            <option value="30" @selected(old('reminder_minutes') == 30)>قبل 30 دقيقة</option>
                        </select>
                    </label>

                    <label>
                        التكرار
                        <select id="taskRepeatInput" name="repeat_type">
                            <option value="once" @selected(old('repeat_type', 'once') === 'once')>مرة واحدة</option>
                            <option value="daily" @selected(old('repeat_type') === 'daily')>يوميًا</option>
                            <option value="weekly" @selected(old('repeat_type') === 'weekly')>أسبوعيًا</option>
                        </select>
                    </label>

                    <div class="journey-form-actions">
                        <button type="button" class="modal-cancel" data-close-modal>إلغاء</button>
                        <button type="submit" class="modal-save">حفظ</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="journey-modal journey-note-modal" id="journeyNoteModal" aria-hidden="true">
            <div class="journey-modal-backdrop" data-close-note-modal></div>

            <div class="journey-modal-box" role="dialog" aria-modal="true" aria-labelledby="journeyNoteModalTitle">
                <div class="journey-modal-head">
                    <div>
                        <span class="modal-label">ملاحظة المهمة</span>
                        <h3 id="journeyNoteModalTitle">إضافة ملاحظة</h3>
                        <p class="note-modal-task-title" id="journeyNoteTaskTitle"></p>
                    </div>

                    <button type="button" class="modal-close" data-close-note-modal>×</button>
                </div>

                <form class="journey-form" id="journeyNoteForm" method="POST" action="">
                    @csrf
                    @method('PATCH')

                    <label class="journey-field">
                        ملاحظتك
                        <textarea
                            id="taskNoteInput"
                            name="patient_note"
                            rows="5"
                            maxlength="1000"
                            placeholder="اكتب ملاحظتك عن هذه المهمة..."
                        ></textarea>
                    </label>

                    <div class="journey-form-actions">
                        <button type="button" class="modal-cancel" data-close-note-modal>إلغاء</button>
                        <button type="submit" class="modal-save">حفظ الملاحظة</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="journey-toast" id="journeyToast"></div>

    </section>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/patient-journey.js') }}"></script>


@endpush
