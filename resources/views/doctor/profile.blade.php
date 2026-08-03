@extends('layouts.doctor')

@php
    $pageTitle = 'ملفي الشخصي';
    $activePage = 'profile';
    $mySpecialtyIds = $doctorProfile->specialties->pluck('id')->toArray();
    $photoUrl = $doctorProfile->photo_path ? \Illuminate\Support\Facades\Storage::url($doctorProfile->photo_path) : null;
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/doctor/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('front/css/doctor/requests.css') }}">

    <!-- بداية كود الـ CSS الجديد للصفحة -->
    <style>
        /* تنسيق الحاوية الرئيسية */
        .dprof-wrapper {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        /* بطاقة الصورة الشخصية والاسم */
        .dprof-header-card {
            background: var(--d-card);
            border: 1px solid var(--d-border);
            border-radius: 20px;
            padding: 25px 30px;
            box-shadow: var(--d-shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .dprof-user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .dprof-avatar-large {
            position: relative;
            width: 90px;
            height: 90px;
            flex-shrink: 0;
        }

        .dprof-avatar-large__frame {
            width: 90px;
            height: 90px;
            border-radius: 24px;
            overflow: hidden;
            border: 3px solid var(--d-border);
            background: var(--d-soft-bg);
            display: grid;
            place-items: center;
            transition: border-color 0.3s ease;
        }
        .dprof-avatar-large__frame:hover {
            border-color: var(--d-green);
        }
        .dprof-avatar-large__frame img { width: 100%; height: 100%; object-fit: cover; }
        .dprof-avatar-large__fallback {
            font-size: 34px; font-weight: 800; color: var(--d-green);
        }

        .dprof-avatar-upload-btn {
            position: absolute; bottom: -4px; right: -4px;
            width: 34px; height: 34px; border-radius: 50%;
            display: grid; place-items: center; cursor: pointer;
            background: var(--d-green); color: #fff;
            border: 3px solid var(--d-card);
            box-shadow: 0 4px 12px rgba(29,158,117,.35);
            transition: 0.2s ease;
        }
        .dprof-avatar-upload-btn:hover { transform: scale(1.1) rotate(-10deg); }
        .dprof-avatar-upload-btn i { width: 16px; height: 16px; }

        .dprof-user-text h2 {
            font-size: 1.5rem; font-weight: 800; color: var(--d-title); margin: 0 0 4px;
        }
        .dprof-user-text p {
            font-size: 0.9rem; color: var(--d-muted); font-weight: 600; margin: 0;
        }

        .dprof-action-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* زر التعديل */
        .dprof-edit-toggle {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 22px; border-radius: 14px;
            background: var(--d-green); color: #fff !important;
            font-size: 0.9rem; font-weight: 800;
            border: 0; cursor: pointer;
            transition: 0.2s ease;
            text-decoration: none;
        }
        .dprof-edit-toggle:hover { background: #0f7a57; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(29,158,117,.4); }

        .dprof-edit-toggle.cancel {
            background: #ef4444;
        }
        .dprof-edit-toggle.cancel:hover { background: #dc2626; }

        /* شبكة البيانات */
        .dprof-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 992px) {
            .dprof-grid { grid-template-columns: 1fr; }
        }

        .dprof-panel {
            background: var(--d-card);
            border: 1px solid var(--d-border);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--d-shadow);
        }

        .dprof-panel-title {
            display: flex; align-items: center; gap: 8px;
            font-size: 1rem; font-weight: 800; color: var(--d-title);
            margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--d-border);
        }
        .dprof-panel-title i { width: 20px; height: 20px; color: var(--d-green); }

        /* العرض (View Mode) */
        .dprof-view-row {
            display: flex; flex-direction: column; gap: 10px;
        }
        .dprof-view-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 16px; background: var(--d-soft-bg); border-radius: 12px;
        }
        .dprof-view-item__label { font-size: 0.8rem; font-weight: 700; color: var(--d-muted); }
        .dprof-view-item__value { font-size: 0.95rem; font-weight: 750; color: var(--d-title); }

        .dprof-tags-view {
            display: flex; flex-wrap: wrap; gap: 8px;
        }
        .dprof-tag-v {
            padding: 6px 14px; background: rgba(29,158,117,.1); color: var(--d-green);
            border-radius: 50px; font-size: 0.8rem; font-weight: 700;
        }

        /* التعديل (Edit Mode) - مخفي افتراضياً */
        .dprof-edit-form {
            display: none;
            flex-direction: column; gap: 15px;
        }
        .dprof-edit-form.is-open { display: flex; }

        .dprof-form-group {
            display: flex; flex-direction: column; gap: 5px;
        }
        .dprof-form-group label {
            font-size: 0.75rem; font-weight: 750; color: var(--d-muted);
        }
        .dprof-form-group input,
        .dprof-form-group textarea {
            padding: 10px 14px; border-radius: 12px;
            border: 1px solid var(--d-border); background: var(--d-card);
            font-size: 0.9rem; font-weight: 600; color: var(--d-title);
            font-family: inherit; transition: border-color 0.2s;
            width: 100%;
        }
        .dprof-form-group input:focus,
        .dprof-form-group textarea:focus { outline: none; border-color: var(--d-green); }
        .dprof-form-group textarea { resize: vertical; min-height: 80px; }

        /* تخصصات قابلة للاختيار (Edit Mode) */
        .dprof-specialties-edit {
            display: flex; flex-wrap: wrap; gap: 10px; margin-top: 5px;
        }
        .dprof-spec-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 50px;
            border: 1px solid var(--d-border); background: var(--d-card);
            font-size: 0.8rem; font-weight: 700; color: var(--d-muted);
            cursor: pointer; transition: 0.2s ease; user-select: none;
        }
        .dprof-spec-pill:hover { border-color: var(--d-green); }
        .dprof-spec-pill input { display: none; }

        /* عند اختيار التخصص */
        .dprof-spec-pill.is-checked,
        .dprof-spec-pill:has(input:checked) {
            background: var(--d-green); border-color: var(--d-green); color: #fff;
            box-shadow: 0 4px 12px rgba(29,158,117,.25);
        }

        .dprof-form-actions {
            display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px;
        }

        .dprof-btn-primary {
            padding: 10px 28px; border-radius: 12px;
            background: var(--d-green); color: #fff; border: 0;
            font-weight: 800; font-size: 0.9rem; cursor: pointer;
            transition: 0.2s ease;
        }
        .dprof-btn-primary:hover { background: #0f7a57; }

        /* معاينة الملف الشخصي (للمريض) */
        .dprof-preview-box {
            background: var(--d-soft-bg); border-radius: 16px;
            padding: 24px; border: 1px solid var(--d-border);
            display: flex; flex-direction: column; align-items: center; text-align: center;
            gap: 12px;
        }
        .dprof-preview-box .avatar-sm {
            width: 60px; height: 60px; border-radius: 16px;
            background: var(--d-green); color: #fff;
            display: grid; place-items: center;
            font-size: 24px; font-weight: 800;
            overflow: hidden; border: 2px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .dprof-preview-box .avatar-sm img { width: 100%; height: 100%; object-fit: cover; }
        .dprof-preview-box h3 { font-size: 1.1rem; font-weight: 800; color: var(--d-title); margin: 0; }
        .dprof-preview-box .dprof-meta {
            font-size: 0.8rem; font-weight: 600; color: var(--d-muted);
        }
        .dprof-preview-box .dprof-bio { font-size: 0.85rem; color: var(--d-text); line-height: 1.6; margin-top: 5px; }

        .dprof-status-badge {
            display: inline-block; padding: 4px 12px; border-radius: 50px;
            font-size: 0.7rem; font-weight: 700;
            background: rgba(29,158,117,.1); color: var(--d-green);
        }
    </style>
    <!-- نهاية كود الـ CSS -->
@endpush

@section('content')
<section class="ddash">
    <div class="ddash-section-head">
        <h2>ملفي الشخصي</h2>
        <span>إدارة بياناتك التي تظهر للمريض عند البحث عن طبيب</span>
    </div>

    @if (session('success'))
        <div class="doctor-status" style="margin-bottom:14px; background: #d1fae5; color: #065f46; padding:12px; border-radius:12px; font-weight:700;">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="doctor-status danger" style="margin-bottom:14px; background: #fee2e2; color: #991b1b; padding:12px; border-radius:12px; font-weight:700;">{{ $errors->first() }}</div>
    @endif

    <div class="dprof-wrapper">

        <!-- ==================== رأس الصفحة (صورة واسم) ==================== -->
        <div class="dprof-header-card">
            <div class="dprof-user-info">
                <div class="dprof-avatar-large">
                    <div class="dprof-avatar-large__frame">
                        <img id="dprof-photo-preview" src="{{ $photoUrl }}" alt="{{ $doctorProfile->user->name }}" style="{{ $photoUrl ? '' : 'display:none' }}">
                        <span id="dprof-photo-fallback" class="dprof-avatar-large__fallback" style="{{ $photoUrl ? 'display:none' : '' }}">{{ mb_substr($doctorProfile->user->name, 0, 1, 'UTF-8') }}</span>
                    </div>
                    <label for="dprof-photo-input" class="dprof-avatar-upload-btn" title="تغيير الصورة">
                        <i data-lucide="camera"></i>
                    </label>
                    <input type="file" id="dprof-photo-input" name="photo" accept="image/png,image/jpeg,image/webp" hidden>
                </div>

                <div class="dprof-user-text">
                    <h2>{{ $doctorProfile->user->name }}</h2>
                    <p>طبيب متخصص في {{ $doctorProfile->specialties->pluck('name')->join('، ') ?: 'التغذية العلاجية' }}</p>
                </div>
            </div>

            <div class="dprof-action-buttons">
                <button type="button" id="dprof-toggle-edit" class="dprof-edit-toggle">
                    <i data-lucide="pencil"></i> تعديل الملف
                </button>
            </div>
        </div>


        <!-- ==================== المحتوى الرئيسي ==================== -->
        <div class="dprof-grid">

            <!-- ====== البانل الأيسر: العرض والتعديل ====== -->
            <div class="dprof-panel">
                <div class="dprof-panel-title">
                    <i data-lucide="user-circle"></i> بيانات الطبيب
                </div>

                <!-- 1. وضع العرض (View Mode) -->
                <div id="dprof-view-mode" class="dprof-view-row">
                    <div class="dprof-view-item">
                        <span class="dprof-view-item__label">مكان العمل</span>
                        <span class="dprof-view-item__value">{{ $doctorProfile->workplace ?: 'لم يتم التحديد' }}</span>
                    </div>
                    <div class="dprof-view-item">
                        <span class="dprof-view-item__label">سنوات الخبرة</span>
                        <span class="dprof-view-item__value">{{ $doctorProfile->years_experience ? $doctorProfile->years_experience . ' سنة' : 'لم يتم التحديد' }}</span>
                    </div>
                    <div class="dprof-view-item">
                        <span class="dprof-view-item__label">التخصصات</span>
                        <div class="dprof-tags-view">
                            @forelse ($doctorProfile->specialties as $specialty)
                                <span class="dprof-tag-v">{{ $specialty->name }}</span>
                            @empty
                                <span style="color:var(--d-muted); font-weight:600; font-size:0.8rem;">لم يتم اختيار تخصص بعد</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="dprof-view-item" style="flex-direction:column; align-items:stretch; gap:6px; background:transparent; padding:0; margin-top:6px;">
                        <span class="dprof-view-item__label">نبذة عنك</span>
                        <p style="font-size:0.9rem; font-weight:600; color:var(--d-text); line-height:1.6; background:var(--d-card); padding:12px 16px; border-radius:12px; border:1px solid var(--d-border); margin:0;">
                            {{ $doctorProfile->bio ?: 'لم تكتب نبذة بعد. قم بتعديل الملف وإضافة نبذة تعريفية للمرضى.' }}
                        </p>
                    </div>
                </div>

                <!-- 2. وضع التعديل (Edit Mode) - يظهر عند الضغط على زر التعديل -->
                <form id="dprof-edit-mode" class="dprof-edit-form" method="POST" action="{{ route('doctor.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="dprof-form-group">
                        <label>مكان العمل</label>
                        <input type="text" name="workplace" value="{{ old('workplace', $doctorProfile->workplace) }}" placeholder="مثال: عيادة إتزان الطبية">
                    </div>

                    <div class="dprof-form-group">
                        <label>سنوات الخبرة</label>
                        <input type="number" name="years_experience" min="0" max="70" value="{{ old('years_experience', $doctorProfile->years_experience) }}" placeholder="عدد السنوات">
                    </div>

                    <div class="dprof-form-group">
                        <label>التخصصات (يمكنك اختيار أكثر من تخصص)</label>
                        <div class="dprof-specialties-edit">
                            @foreach ($allSpecialties as $specialty)
                                <label class="dprof-spec-pill @checked(in_array($specialty->id, old('specialties', $mySpecialtyIds)))">
                                    <input type="checkbox" name="specialties[]" value="{{ $specialty->id }}"
                                           @checked(in_array($specialty->id, old('specialties', $mySpecialtyIds)))>
                                    <span>{{ $specialty->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="dprof-form-group">
                        <label>نبذة عنك</label>
                        <textarea name="bio" placeholder="اكتب نبذة مختصرة تظهر للمرضى...">{{ old('bio', $doctorProfile->bio) }}</textarea>
                    </div>

                    <div class="dprof-form-actions">
                        <button type="button" id="dprof-cancel-edit" class="dprof-btn-primary" style="background:#9ca3af;">إلغاء</button>
                        <button type="submit" class="dprof-btn-primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>

            <!-- ====== البانل الأيمن: معاينة الملف للمريض ====== -->
            <div class="dprof-panel">
                <div class="dprof-panel-title">
                    <i data-lucide="eye"></i> معاينة (كيف يراها المريض)
                </div>

                <div class="dprof-preview-box" id="dprof-live-preview">
                    <div class="avatar-sm">
                        @if ($photoUrl)
                            <img src="{{ $photoUrl }}" alt="{{ $doctorProfile->user->name }}">
                        @else
                            {{ mb_substr($doctorProfile->user->name, 0, 1) }}
                        @endif
                    </div>
                    <h3>{{ $doctorProfile->user->name }}</h3>
                    <div class="dprof-meta">
                        <span class="dprof-status-badge">
                            {{ $doctorProfile->is_available ? '🟢 متاح' : '🔴 غير متاح حالياً' }}
                        </span>
                        <span> · {{ $doctorProfile->years_experience ? $doctorProfile->years_experience . ' سنة خبرة' : 'طبيب حديث' }}</span>
                    </div>
                    <div class="dprof-meta">
                        {{ $doctorProfile->workplace ?: 'عيادة خاصة' }}
                    </div>
                    <div class="dprof-meta" style="font-weight:700; color:var(--d-green);">
                        {{ $doctorProfile->specialties->pluck('name')->join(' • ') ?: 'أخصائي تغذية علاجية' }}
                    </div>
                    <p class="dprof-bio">{{ $doctorProfile->bio ?: 'لم يتم كتابة نبذة تعريفية بعد.' }}</p>

                    @if ($reviewsCount > 0)
                        <div style="margin-top:10px; font-size:0.9rem; font-weight:700; background:#fff; padding:8px 18px; border-radius:50px; border:1px solid var(--d-border);">
                            ⭐ {{ number_format($avgRating, 1) }} ({{ $reviewsCount }} تقييم)
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ---- 1. التعامل مع الصورة ----
    const photoInput = document.getElementById('dprof-photo-input');
    const photoPreview = document.getElementById('dprof-photo-preview');
    const photoFallback = document.getElementById('dprof-photo-fallback');
    const previewBoxImg = document.querySelector('#dprof-live-preview .avatar-sm img');
    const previewBoxFallback = document.querySelector('#dprof-live-preview .avatar-sm');

    if (photoInput) {
        photoInput.addEventListener('change', function (e) {
            const file = e.target.files && e.target.files[0];
            if (!file) return;

            const url = URL.createObjectURL(file);
            photoPreview.src = url;
            photoPreview.style.display = '';
            if (photoFallback) photoFallback.style.display = 'none';

            // تحديث صورة المعاينة الحية فوراً
            if (previewBoxImg) {
                previewBoxImg.src = url;
                previewBoxImg.style.display = '';
                previewBoxFallback.innerHTML = ''; // نمسح الحرف
                previewBoxFallback.appendChild(previewBoxImg);
            }
        });
    }

    // ---- 2. التبديل بين وضع العرض والتعديل ----
    const toggleBtn = document.getElementById('dprof-toggle-edit');
    const viewMode = document.getElementById('dprof-view-mode');
    const editMode = document.getElementById('dprof-edit-mode');
    const cancelBtn = document.getElementById('dprof-cancel-edit');

    function toggleEditMode(showEdit) {
        if (showEdit) {
            viewMode.style.display = 'none';
            editMode.classList.add('is-open');
            toggleBtn.innerHTML = '<i data-lucide="x"></i> إلغاء التعديل';
            toggleBtn.classList.add('cancel');
        } else {
            viewMode.style.display = 'flex';
            editMode.classList.remove('is-open');
            toggleBtn.innerHTML = '<i data-lucide="pencil"></i> تعديل الملف';
            toggleBtn.classList.remove('cancel');
        }
        // إعادة رسم أيقونات Lucide
        if (window.lucide) lucide.createIcons();
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const isEditOpen = editMode.classList.contains('is-open');
            toggleEditMode(!isEditOpen);
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            toggleEditMode(false);
        });
    }

    // ---- 3. التعامل مع التخصصات (Checkboxes) ----
    document.querySelectorAll('.dprof-spec-pill').forEach(function (pill) {
        const checkbox = pill.querySelector('input[type=checkbox]');
        if (!checkbox) return;

        pill.classList.toggle('is-checked', checkbox.checked);
        checkbox.addEventListener('change', function () {
            pill.classList.toggle('is-checked', checkbox.checked);
        });
    });
});
</script>
@endpush
@endsection
