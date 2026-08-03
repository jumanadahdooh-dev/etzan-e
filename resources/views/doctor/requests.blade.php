@extends('layouts.doctor')

@php
    $pageTitle = 'طلبات الاستشارة';
    $activePage = 'requests';

    $requests = $requests ?? collect();

    $pendingRequests = $requests->where('status', 'pending')->sortBy('created_at')->values();
    $approvedRequests = $requests->where('status', 'approved');
    $rejectedRequests = $requests->where('status', 'rejected');

    $featured = $pendingRequests->first();

    $counts = [
        'all' => $requests->count(),
        'pending' => $pendingRequests->count(),
        'approved' => $approvedRequests->count(),
        'rejected' => $rejectedRequests->count(),
    ];

    $statusLabels = ['pending' => 'معلّق', 'approved' => 'مقبول', 'rejected' => 'مرفوض', 'cancelled' => 'ملغي'];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}?v={{ file_exists(public_path('front/css/doctor/dashboard.css')) ? filemtime(public_path('front/css/doctor/dashboard.css')) : '1' }}">
    <link rel="stylesheet" href="{{ asset('front/css/doctor/requests.css') }}?v={{ file_exists(public_path('front/css/doctor/requests.css')) ? filemtime(public_path('front/css/doctor/requests.css')) : '1' }}">
@endpush

@section('content')
<section class="ddash">

    {{-- ══════════ رأس الصفحة ══════════ --}}
    <div class="ddash-section-head">
        <h2>طلبات الاستشارة</h2>
        <span>راجع الطلبات الجديدة واتخذ القرار المناسب</span>
    </div>

    {{-- ══════════ البطاقة البارزة: أقدم طلب معلّق ══════════ --}}
    @if ($featured)
        @php
            $fp = json_decode(json_encode($featured), true);
            $conditions = [];
            if (!empty($fp['medical_conditions'])) {
                $decoded = json_decode($fp['medical_conditions'], true);
                if (is_array($decoded)) $conditions = $decoded;
            }
        @endphp
        <div class="dreq-featured">
            <div class="dreq-featured__info">
                @if (!empty($featured->avatarUrl))
                    <img src="{{ $featured->avatarUrl }}" class="ddash-avatar-photo" style="width:46px;height:46px" alt="{{ $featured->patient_name }}">
                @else
                    <span class="ddash-avatar ddash-avatar--amber" style="width:46px;height:46px;font-size:1rem">{{ mb_substr($featured->patient_name ?? '؟', 0, 1) }}</span>
                @endif
                <div>
                    <span class="dreq-featured__eyebrow">
                        أقدم طلب معلّق · بانتظارك من {{ \Illuminate\Support\Carbon::parse($featured->created_at)->locale('ar')->diffForHumans(null, true) }}
                    </span>
                    <strong>{{ $featured->patient_name ?? 'مريض' }}</strong>
                    <div class="dreq-featured__tags">
                        @if (!empty($featured->health_goal))
                            <span class="ddash-pill ddash-pill--green">{{ $featured->health_goal }}</span>
                        @endif
                        @foreach (array_slice($conditions, 0, 2) as $cond)
                            <span class="ddash-pill ddash-pill--muted">{{ is_array($cond) ? ($cond['name'] ?? '') : $cond }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="dreq-featured__actions">
                <a href="{{ route('doctor.patient-profile.show', $featured->id) }}" class="dreq-icon-btn" title="عرض التفاصيل" aria-label="عرض التفاصيل">
                    <i data-lucide="eye"></i>
                </a>
                <button type="button" class="dreq-btn dreq-btn--danger" data-reject-open="{{ route('doctor.patient-requests.reject', $featured->id) }}">رفض</button>
                <form method="POST" action="{{ route('doctor.patient-requests.approve', $featured->id) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="dreq-btn dreq-btn--success">قبول</button>
                </form>
            </div>
        </div>
    @endif

    {{-- ══════════ تبويبات الفلترة ══════════ --}}
    <div class="ddash-schedule-filters">
        <button type="button" class="ddash-filter is-active" data-req-filter="all">الكل <span class="ddash-count">{{ $counts['all'] }}</span></button>
        <button type="button" class="ddash-filter" data-req-filter="pending">معلّق <span class="ddash-count">{{ $counts['pending'] }}</span></button>
        <button type="button" class="ddash-filter" data-req-filter="approved">مقبول <span class="ddash-count">{{ $counts['approved'] }}</span></button>
        <button type="button" class="ddash-filter" data-req-filter="rejected">مرفوض <span class="ddash-count">{{ $counts['rejected'] }}</span></button>
    </div>

    {{-- ══════════ قائمة الطلبات ══════════ --}}
    @if ($requests->isNotEmpty())
        <div class="dreq-list" data-req-list>
            @foreach ($requests->sortByDesc('created_at') as $req)
                @php
                    $rConditions = [];
                    if (!empty($req->medical_conditions)) {
                        $decoded = json_decode($req->medical_conditions, true);
                        if (is_array($decoded)) $rConditions = $decoded;
                    }
                    $age = null;
                @endphp
                <div class="dreq-card" data-status="{{ $req->status }}">
                    <div class="dreq-card__top">
                        <div class="dreq-card__who">
                            @if (!empty($req->avatarUrl))
                                <img src="{{ $req->avatarUrl }}" class="ddash-avatar-photo" alt="{{ $req->patient_name }}">
                            @else
                                <span class="ddash-avatar">{{ mb_substr($req->patient_name ?? '؟', 0, 1) }}</span>
                            @endif
                            <div>
                                <strong>{{ $req->patient_name ?? 'مريض' }}</strong>
                                <small>{{ $req->patient_email ?? '' }} · {{ \Illuminate\Support\Carbon::parse($req->created_at)->locale('ar')->diffForHumans() }}</small>
                            </div>
                        </div>
                        <span class="ddash-pill ddash-pill--{{ $req->status === 'approved' ? 'green' : ($req->status === 'pending' ? 'amber' : ($req->status === 'rejected' ? 'rose' : 'muted')) }}">
                            {{ $statusLabels[$req->status] ?? $req->status }}
                        </span>
                    </div>

                    <div class="dreq-card__tags">
                        @if (!empty($req->health_goal))
                            <span class="ddash-pill ddash-pill--green">{{ $req->health_goal }}</span>
                        @endif
                        @foreach (array_slice($rConditions, 0, 3) as $cond)
                            <span class="ddash-pill ddash-pill--muted">{{ is_array($cond) ? ($cond['name'] ?? '') : $cond }}</span>
                        @endforeach
                    </div>

                    <div class="dreq-card__actions">
                        <a href="{{ route('doctor.patient-profile.show', $req->id) }}" class="dreq-icon-btn" title="عرض الملف" aria-label="عرض الملف">
                            <i data-lucide="eye"></i>
                        </a>

                        @if ($req->status === 'pending')
                            <button type="button" class="dreq-btn dreq-btn--danger" data-reject-open="{{ route('doctor.patient-requests.reject', $req->id) }}">رفض</button>
                            <form method="POST" action="{{ route('doctor.patient-requests.approve', $req->id) }}">
                                @csrf
                                <button type="submit" class="dreq-btn dreq-btn--success">قبول</button>
                            </form>
                        @elseif ($req->status === 'approved')
                            <form method="POST" action="{{ route('doctor.patient-requests.complete', $req->id) }}">
                                @csrf
                                <button type="submit" class="dreq-btn dreq-btn--muted">إنهاء المتابعة</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="ddash-card ddash-accent--green" data-req-empty-filtered style="display:none">
            <div class="ddash-empty"><i data-lucide="filter-x"></i><p>ما في طلبات بهاي الحالة حالياً.</p></div>
        </div>
    @else
        <div class="ddash-card ddash-accent--green">
            <div class="ddash-empty"><i data-lucide="inbox"></i><p>ما في طلبات استشارة حالياً.</p></div>
        </div>
    @endif

    {{-- ══════════ مودال سبب الرفض (اختياري) ══════════ --}}
    <div class="dreq-modal-overlay" data-reject-modal>
        <div class="dreq-modal">
            <h3>رفض طلب المتابعة</h3>
            <p>ممكن تكتبي سبب الرفض (اختياري) — رح يوصل للمريض ضمن الإشعار.</p>
            <form method="POST" data-reject-form>
                @csrf
                <textarea name="doctor_response" rows="3" placeholder="مثلاً: التخصص غير مناسب لحالتك..."></textarea>
                <div class="dreq-modal__actions">
                    <button type="button" class="dreq-btn dreq-btn--muted" data-reject-cancel>إلغاء</button>
                    <button type="submit" class="dreq-btn dreq-btn--danger">تأكيد الرفض</button>
                </div>
            </form>
        </div>
    </div>

</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* مودال رفض الطلب بسبب اختياري */
    var rejectModal = document.querySelector('[data-reject-modal]');
    var rejectForm = document.querySelector('[data-reject-form]');
    var rejectCancel = document.querySelector('[data-reject-cancel]');

    if (rejectModal && rejectForm) {
        document.querySelectorAll('[data-reject-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                rejectForm.setAttribute('action', btn.getAttribute('data-reject-open'));
                rejectForm.querySelector('textarea').value = '';
                rejectModal.classList.add('is-open');
            });
        });

        function closeRejectModal() { rejectModal.classList.remove('is-open'); }

        if (rejectCancel) rejectCancel.addEventListener('click', closeRejectModal);
        rejectModal.addEventListener('click', function (e) { if (e.target === rejectModal) closeRejectModal(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeRejectModal(); });
    }

    /* فلترة الطلبات */
    document.querySelectorAll('[data-req-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var filter = btn.getAttribute('data-req-filter');
            document.querySelectorAll('[data-req-filter]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');

            var visibleCount = 0;
            document.querySelectorAll('[data-req-list] .dreq-card').forEach(function (card) {
                var status = card.getAttribute('data-status');
                var isVisible = (filter === 'all' || status === filter);
                card.style.display = isVisible ? '' : 'none';
                if (isVisible) visibleCount++;
            });

            var emptyBox = document.querySelector('[data-req-empty-filtered]');
            if (emptyBox) {
                emptyBox.style.display = visibleCount === 0 ? '' : 'none';
            }
        });
    });
});
</script>
@endpush
@endsection
