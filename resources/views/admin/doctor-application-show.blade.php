@extends('layouts.admin')

@section('title', 'تفاصيل طلب الطبيب | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/admin/admin-doctor-applications.css') }}">
@endpush

@section('content')
<section class="doctor-show-page doctor-show-v2-page">

    @if (session('success'))
        <div class="doctor-apps-toast doctor-apps-toast--success">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="doctor-show-alert-v2 doctor-show-alert-v2--error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Hero --}}
    <section class="doctor-show-hero-v2 doctor-show-hero-v2--{{ $doctorApplication->status }}">
        <div class="doctor-show-hero-v2__main">
            <a href="{{ route('admin.doctor-applications') }}" class="doctor-show-back-v2" title="رجوع">
                <i class="fa-solid fa-arrow-right"></i>
            </a>

            <div class="doctor-show-avatar-v2">
                @if ($doctorApplication->profile_photo_path)
                    <img src="{{ asset('storage/' . $doctorApplication->profile_photo_path) }}" alt="{{ $doctorApplication->full_name }}">
                @else
                    <span>{{ mb_substr($doctorApplication->full_name, 0, 1) }}</span>
                @endif
            </div>

            <div class="doctor-show-title-v2">
                <span class="doctor-show-kicker-v2">
                    <i class="fa-solid fa-user-doctor"></i>
                    ملف طلب طبيب
                </span>

                <div class="doctor-show-title-row-v2">
                    <h1>{{ $doctorApplication->full_name }}</h1>

                    <span class="doctor-show-status-v2 doctor-show-status-v2--{{ $doctorApplication->status }}">
                        @if ($doctorApplication->status === 'pending')
                            <i class="fa-regular fa-hourglass-half"></i>
                            قيد المراجعة
                        @elseif ($doctorApplication->status === 'approved')
                            <i class="fa-solid fa-circle-check"></i>
                            مقبول
                        @else
                            <i class="fa-solid fa-circle-xmark"></i>
                            مرفوض
                        @endif
                    </span>
                </div>

                <p>{{ $doctorApplication->specialty }} • {{ $doctorApplication->workplace }}</p>

                <div class="doctor-show-hero-chips-v2">
                    <span>
                        <i class="fa-regular fa-calendar"></i>
                        {{ $doctorApplication->created_at->format('Y-m-d') }}
                    </span>

                    <span>
                        <i class="fa-solid fa-briefcase"></i>
                        {{ $doctorApplication->experience_years }} سنوات
                    </span>

                    <span>
                        <i class="fa-solid fa-id-card"></i>
                        {{ $doctorApplication->license_number }}
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- Review Strip --}}
    <section class="doctor-review-strip-v2">
        <article class="doctor-review-item-v2">
            <div class="doctor-review-icon-v2">
                <i class="fa-solid fa-wave-square"></i>
            </div>
            <div>
                <span>الحالة الحالية</span>
                <strong>
                    @if ($doctorApplication->status === 'pending')
                        قيد المراجعة
                    @elseif ($doctorApplication->status === 'approved')
                        مقبول
                    @else
                        مرفوض
                    @endif
                </strong>
            </div>
        </article>

        <article class="doctor-review-item-v2">
            <div class="doctor-review-icon-v2">
                <i class="fa-regular fa-user"></i>
            </div>
            <div>
                <span>تمت بواسطة</span>
                <strong>{{ $doctorApplication->reviewer?->name ?? '—' }}</strong>
            </div>
        </article>

        <article class="doctor-review-item-v2">
            <div class="doctor-review-icon-v2">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div>
                <span>تاريخ المراجعة</span>
                <strong>{{ $doctorApplication->reviewed_at ? $doctorApplication->reviewed_at->format('Y-m-d H:i') : '—' }}</strong>
            </div>
        </article>
    </section>

    {{-- Layout --}}
    <section class="doctor-show-layout-v2">
        <div class="doctor-show-main-v2">

            <section class="doctor-show-card-v2">
                <div class="doctor-show-card-head-v2">
                    <div>
                        <span>المعلومات الأساسية</span>
                        <h2>بيانات الطبيب</h2>
                    </div>
                </div>

                <div class="doctor-show-info-grid-v2">
                    <div class="doctor-show-info-item-v2">
                        <i class="fa-regular fa-user"></i>
                        <span>الاسم الكامل</span>
                        <strong>{{ $doctorApplication->full_name }}</strong>
                    </div>

                    <div class="doctor-show-info-item-v2">
                        <i class="fa-regular fa-envelope"></i>
                        <span>البريد الإلكتروني</span>
                        <strong>{{ $doctorApplication->email }}</strong>
                    </div>

                    <div class="doctor-show-info-item-v2">
                        <i class="fa-solid fa-phone"></i>
                        <span>رقم الهاتف</span>
                        <strong>{{ $doctorApplication->phone }}</strong>
                    </div>

                    <div class="doctor-show-info-item-v2">
                        <i class="fa-solid fa-hospital"></i>
                        <span>مكان العمل</span>
                        <strong>{{ $doctorApplication->workplace }}</strong>
                    </div>

                    <div class="doctor-show-info-item-v2">
                        <i class="fa-solid fa-stethoscope"></i>
                        <span>التخصص</span>
                        <strong>{{ $doctorApplication->specialty }}</strong>
                    </div>

                    <div class="doctor-show-info-item-v2">
                        <i class="fa-solid fa-id-card"></i>
                        <span>رقم الترخيص</span>
                        <strong>{{ $doctorApplication->license_number }}</strong>
                    </div>
                </div>
            </section>

            <section class="doctor-show-card-v2">
                <div class="doctor-show-card-head-v2">
                    <div>
                        <span>النبذة المهنية</span>
                        <h2>الوصف</h2>
                    </div>
                </div>

                <div class="doctor-show-bio-v2">
                    {{ $doctorApplication->bio }}
                </div>
            </section>

            <section class="doctor-show-card-v2">
                <div class="doctor-show-card-head-v2">
                    <div>
                        <span>الملفات</span>
                        <h2>المرفقات</h2>
                    </div>
                </div>

                <div class="doctor-show-files-v2">
                    <a href="{{ route('admin.doctor-applications-file-viewer', ['doctorApplication' => $doctorApplication->id, 'type' => 'photo']) }}"
                       class="doctor-show-file-v2">
                        <span class="doctor-show-file-icon-v2 is-image">
                            <i class="fa-regular fa-image"></i>
                        </span>

                        <div>
                            <strong>الصورة الشخصية</strong>
                            <small>عرض الصورة داخل النظام</small>
                        </div>

                        <span class="doctor-show-file-arrow-v2">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </span>
                    </a>

                    <a href="{{ route('admin.doctor-applications-file-viewer', ['doctorApplication' => $doctorApplication->id, 'type' => 'license']) }}"
                       class="doctor-show-file-v2">
                        <span class="doctor-show-file-icon-v2 is-pdf">
                            <i class="fa-regular fa-file-pdf"></i>
                        </span>

                        <div>
                            <strong>إثبات مزاولة المهنة</strong>
                            <small>فتح ملف الترخيص داخل النظام</small>
                        </div>

                        <span class="doctor-show-file-arrow-v2">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </span>
                    </a>

                    @if ($doctorApplication->cv_file_path)
                        <a href="{{ route('admin.doctor-applications-file-viewer', ['doctorApplication' => $doctorApplication->id, 'type' => 'cv']) }}"
                           class="doctor-show-file-v2">
                            <span class="doctor-show-file-icon-v2 is-file">
                                <i class="fa-regular fa-file-lines"></i>
                            </span>

                            <div>
                                <strong>السيرة الذاتية</strong>
                                <small>فتح الملف داخل النظام</small>
                            </div>

                            <span class="doctor-show-file-arrow-v2">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </span>
                        </a>
                    @endif
                </div>
            </section>
        </div>

        <aside class="doctor-show-side-v2">
            <section class="doctor-show-card-v2 doctor-show-card-v2--sticky">
                <div class="doctor-show-card-head-v2">
                    <div>
                        <span>المراجعة</span>
                        <h2>اتخاذ القرار</h2>
                    </div>
                </div>

                <form action="{{ route('admin.doctor-applications-update-status', $doctorApplication) }}" method="POST" class="doctor-show-form-v2">
                    @csrf
                    @method('PATCH')

                    <div class="doctor-show-form-field-v2">
                        <label>الحالة</label>

                        <div class="doctor-status-choices-v2">
                            <label class="doctor-status-choice-v2 doctor-status-choice-v2--pending">
                                <input type="radio" name="status" value="pending"
                                    @checked(old('status', $doctorApplication->status) === 'pending')>
                                <span>
                                    <i class="fa-regular fa-hourglass-half"></i>
                                    قيد المراجعة
                                </span>
                            </label>

                            <label class="doctor-status-choice-v2 doctor-status-choice-v2--approved">
                                <input type="radio" name="status" value="approved"
                                    @checked(old('status', $doctorApplication->status) === 'approved')>
                                <span>
                                    <i class="fa-solid fa-circle-check"></i>
                                    مقبول
                                </span>
                            </label>

                            <label class="doctor-status-choice-v2 doctor-status-choice-v2--rejected">
                                <input type="radio" name="status" value="rejected"
                                    @checked(old('status', $doctorApplication->status) === 'rejected')>
                                <span>
                                    <i class="fa-solid fa-circle-xmark"></i>
                                    مرفوض
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="doctor-show-form-field-v2">
                        <label>ملاحظة داخلية</label>
                        <textarea name="admin_note" rows="4" placeholder="اكتبي ملاحظة واضحة ومختصرة...">{{ old('admin_note', $doctorApplication->admin_note) }}</textarea>
                    </div>

                    <div class="doctor-show-form-field-v2" id="rejectionReasonWrap">
                        <label>سبب الرفض</label>
                        <textarea name="rejection_reason" id="rejectionReasonInput" rows="4" placeholder="اكتبي سبب الرفض هنا بشكل واضح...">{{ old('rejection_reason', $doctorApplication->rejection_reason) }}</textarea>
                    </div>

                    <button type="submit" class="doctor-show-save-v2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>حفظ القرار</span>
                    </button>
                </form>
            </section>
        </aside>
    </section>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusInputs = document.querySelectorAll('input[name="status"]');
    const rejectionWrap = document.getElementById('rejectionReasonWrap');
    const rejectionInput = document.getElementById('rejectionReasonInput');

    function toggleRejectionReason() {
        const selected = document.querySelector('input[name="status"]:checked');

        if (!selected || !rejectionWrap || !rejectionInput) return;

        if (selected.value === 'rejected') {
            rejectionWrap.classList.add('is-visible');
            rejectionWrap.style.display = 'grid';
            rejectionInput.required = true;
        } else {
            rejectionWrap.classList.remove('is-visible');
            rejectionWrap.style.display = 'none';
            rejectionInput.required = false;
        }
    }

    statusInputs.forEach((input) => {
        input.addEventListener('change', toggleRejectionReason);
    });

    toggleRejectionReason();
});
</script>
@endpush
