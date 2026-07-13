@extends('layouts.public')

@section('title', 'انضم كطبيب | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/join-doctor.css') }}">

<style>
/* =========================================================
   Doctor Application Success Modal
========================================================= */

.doctor-success-modal {
  position: fixed;
  inset: 0;
  z-index: 99999;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 22px;
  direction: rtl;
}

.doctor-success-modal.is-open {
  display: flex;
}

.doctor-success-modal__overlay {
  position: absolute;
  inset: 0;
  background: rgba(7, 27, 32, 0.48);
  backdrop-filter: blur(12px);
}

.doctor-success-modal__card {
  position: relative;
  z-index: 2;
  width: min(570px, 100%);
  border-radius: 34px;
  padding: 34px 34px 30px;
  background:
    radial-gradient(circle at top right, rgba(29, 158, 117, 0.13), transparent 38%),
    radial-gradient(circle at bottom left, rgba(77, 168, 218, 0.13), transparent 35%),
    #ffffff;
  border: 1px solid rgba(29, 158, 117, 0.18);
  box-shadow: 0 30px 90px rgba(24, 51, 59, 0.24);
  text-align: center;
  animation: doctorModalPop 0.35s ease;
}

.doctor-success-modal__close {
  position: absolute;
  top: 18px;
  inset-inline-start: 18px;
  width: 42px;
  height: 42px;
  border: 0;
  border-radius: 16px;
  background: rgba(29, 158, 117, 0.10);
  color: #15795A;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.doctor-success-modal__icon {
  width: 82px;
  height: 82px;
  margin: 0 auto 16px;
  border-radius: 28px;
  background: linear-gradient(135deg, #1D9E75, #4DA8DA);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 34px;
  box-shadow: 0 20px 45px rgba(29, 158, 117, 0.28);
}

.doctor-success-modal__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  margin: 0 auto 12px;
  padding: 8px 14px;
  border-radius: 999px;
  background: rgba(29, 158, 117, 0.11);
  color: #15795A;
  font-size: 13px;
  font-weight: 900;
}

.doctor-success-modal__card h2 {
  margin: 0 0 10px;
  color: #18333B;
  font-size: 30px;
  font-weight: 950;
  line-height: 1.35;
}

.doctor-success-modal__card p {
  margin: 0 auto 22px;
  max-width: 450px;
  color: #647B84;
  font-size: 14px;
  font-weight: 750;
  line-height: 1.9;
}

.doctor-success-modal__steps {
  display: grid;
  gap: 10px;
  margin: 22px 0;
}

.doctor-success-step {
  min-height: 66px;
  border-radius: 20px;
  padding: 13px 15px;
  background: rgba(248, 252, 251, 0.94);
  border: 1px solid rgba(29, 158, 117, 0.13);
  display: flex;
  align-items: center;
  gap: 12px;
  text-align: right;
}

.doctor-success-step span {
  width: 11px;
  height: 11px;
  min-width: 11px;
  border-radius: 50%;
  background: #1D9E75;
  box-shadow: 0 0 0 6px rgba(29, 158, 117, 0.10);
}

.doctor-success-step strong {
  display: block;
  color: #18333B;
  font-size: 14px;
  font-weight: 950;
  margin-bottom: 3px;
}

.doctor-success-step small {
  display: block;
  color: #647B84;
  font-size: 12px;
  font-weight: 750;
  line-height: 1.6;
}

.doctor-success-modal__actions {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-top: 22px;
  flex-wrap: wrap;
}

.doctor-success-btn {
  min-width: 155px;
  height: 48px;
  border-radius: 999px;
  padding: 0 22px;
  border: 0;
  cursor: pointer;
  text-decoration: none;
  font-size: 14px;
  font-weight: 950;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.doctor-success-btn-primary {
  color: #ffffff;
  background: linear-gradient(135deg, #1D9E75, #4DA8DA);
  box-shadow: 0 14px 28px rgba(29, 158, 117, 0.22);
}

.doctor-success-btn-outline {
  color: #15795A;
  background: #ffffff;
  border: 1px solid rgba(29, 158, 117, 0.22);
}

@keyframes doctorModalPop {
  from {
    opacity: 0;
    transform: translateY(18px) scale(0.96);
  }

  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@media (max-width: 560px) {
  .doctor-success-modal__card {
    padding: 28px 20px 24px;
    border-radius: 26px;
  }

  .doctor-success-modal__card h2 {
    font-size: 24px;
  }

  .doctor-success-modal__actions {
    flex-direction: column;
  }

  .doctor-success-btn {
    width: 100%;
  }
}
</style>
@endpush

@section('content')

<section class="doctor-apply-page">
  <div class="doctor-apply-shell">
    <div class="doctor-apply-wrap">

      <div class="mini-deco mini-deco--1"><i class="fa-solid fa-user-doctor"></i></div>
      <div class="mini-deco mini-deco--2"><i class="fa-solid fa-stethoscope"></i></div>
      <div class="mini-deco mini-deco--3"><i class="fa-solid fa-file-shield"></i></div>
      <div class="mini-deco mini-deco--4"><i class="fa-solid fa-circle-check"></i></div>
      <div class="mini-deco mini-deco--5"><i class="fa-solid fa-heart-pulse"></i></div>

      <div class="doctor-apply-card">
        <div class="doctor-apply-badge" id="doctorApplyBadge">
          <i class="fa-solid fa-user-doctor"></i>
          بوابة الاعتماد الطبي
        </div>

        <div class="doctor-apply-head">
          <h1 id="doctorApplyTitle">انضم كطبيب إلى المنصة</h1>
          <p id="doctorApplyDesc">
            أكمل خطوات الاعتماد المهني ليتم مراجعة طلبك من قبل الإدارة بطريقة واضحة ومنظمة.
          </p>
        </div>

        <div class="doctor-stepper">
          <div class="doctor-progress-line">
            <span id="doctorProgressFill"></span>
          </div>

          <div class="doctor-steps">
            <div class="doctor-step is-active" data-step="1">
              <span>1</span>
              <small>الأساسيات</small>
            </div>

            <div class="doctor-step" data-step="2">
              <span>2</span>
              <small>المهنية</small>
            </div>

            <div class="doctor-step" data-step="3">
              <span>3</span>
              <small>المستندات</small>
            </div>

            <div class="doctor-step" data-step="4">
              <span>4</span>
              <small>المراجعة</small>
            </div>
          </div>
        </div>

        <form id="doctorApplyForm" class="doctor-apply-form" method="POST" action="{{ route('join-doctor.store') }}" enctype="multipart/form-data" novalidate>
          @csrf

          <!-- STEP 1 -->
          <div class="doctor-pane is-active" data-pane="1">
            <div class="doctor-pane-head">
              <h2>المعلومات الأساسية</h2>
              <p>ابدأ ببياناتك الرئيسية كما تريد أن تظهر في الطلب.</p>
            </div>

            <div class="doctor-grid">
              <div class="form-field">
                <label for="doctorName">الاسم الكامل</label>
                <div class="input-shell @error('full_name') is-invalid @enderror">
                  <i class="fa-regular fa-user"></i>
                  <input
                    type="text"
                    id="doctorName"
                    name="full_name"
                    value="{{ old('full_name') }}"
                    placeholder="أدخل اسمك الكامل"
                  />
                </div>
                @error('full_name')
                  <small class="field-error">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-field">
                <label for="doctorEmail">البريد الإلكتروني</label>
                <div class="input-shell @error('email') is-invalid @enderror">
                  <i class="fa-regular fa-envelope"></i>
                  <input
                    type="email"
                    id="doctorEmail"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="أدخل بريدك الإلكتروني"
                    autocomplete="email"
                  />
                </div>
                @error('email')
                  <small class="field-error">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-field">
                <label for="doctorPhone">رقم الهاتف</label>
                <div class="input-shell @error('phone') is-invalid @enderror">
                  <i class="fa-solid fa-phone"></i>
                  <input
                    type="tel"
                    id="doctorPhone"
                    name="phone"
                    value="{{ old('phone') }}"
                    placeholder="أدخل رقم الهاتف"
                  />
                </div>
                @error('phone')
                  <small class="field-error">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-field">
                <label for="doctorWorkplace">مكان العمل الحالي</label>
                <div class="input-shell @error('workplace') is-invalid @enderror">
                  <i class="fa-solid fa-hospital"></i>
                  <input
                    type="text"
                    id="doctorWorkplace"
                    name="workplace"
                    value="{{ old('workplace') }}"
                    placeholder="اسم المستشفى أو المركز"
                  />
                </div>
                @error('workplace')
                  <small class="field-error">{{ $message }}</small>
                @enderror
              </div>
            </div>
          </div>

          <!-- STEP 2 -->
          <div class="doctor-pane" data-pane="2">
            <div class="doctor-pane-head">
              <h2>البيانات المهنية</h2>
              <p>أدخل المعلومات المهنية الأساسية التي ستُراجع من قبل الإدارة.</p>
            </div>

            <div class="form-field form-field--full">
              <label>اختر التخصص</label>

              <div class="specialty-pills @error('specialty') is-invalid @enderror">
                @forelse ($specialties as $specialty)
                  <label class="specialty-pill">
                    <input
                      type="radio"
                      name="specialty"
                      value="{{ $specialty->name }}"
                      {{ old('specialty') == $specialty->name ? 'checked' : '' }}
                    />

                    <span>
                      <i class="{{ $specialty->icon ?? 'fa-solid fa-stethoscope' }}"></i>
                      {{ $specialty->name }}
                    </span>
                  </label>
                @empty
                  <div class="specialty-empty">
                    لا توجد تخصصات متاحة حاليًا. يرجى المحاولة لاحقًا.
                  </div>
                @endforelse
              </div>

              @error('specialty')
                <small class="field-error">{{ $message }}</small>
              @enderror
            </div>

            <div class="doctor-grid">
              <div class="form-field">
                <label for="doctorExperience">سنوات الخبرة</label>
                <div class="input-shell @error('experience_years') is-invalid @enderror">
                  <i class="fa-solid fa-briefcase-medical"></i>
                  <input
                    type="number"
                    id="doctorExperience"
                    name="experience_years"
                    min="0"
                    value="{{ old('experience_years') }}"
                    placeholder="مثال: 8"
                  />
                </div>
                @error('experience_years')
                  <small class="field-error">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-field">
                <label for="doctorLicense">رقم الترخيص</label>
                <div class="input-shell @error('license_number') is-invalid @enderror">
                  <i class="fa-solid fa-id-card"></i>
                  <input
                    type="text"
                    id="doctorLicense"
                    name="license_number"
                    value="{{ old('license_number') }}"
                    placeholder="أدخل رقم الترخيص"
                  />
                </div>
                @error('license_number')
                  <small class="field-error">{{ $message }}</small>
                @enderror
              </div>

              <div class="form-field form-field--full">
                <label for="doctorBio">نبذة مهنية</label>
                <div class="textarea-shell @error('bio') is-invalid @enderror">
                  <i class="fa-regular fa-note-sticky"></i>
                  <textarea
                    id="doctorBio"
                    name="bio"
                    placeholder="اكتب نبذة مختصرة عن خبرتك، اهتماماتك الطبية، وطبيعة عملك..."
                  >{{ old('bio') }}</textarea>
                </div>
                @error('bio')
                  <small class="field-error">{{ $message }}</small>
                @enderror
              </div>
            </div>
          </div>

          <!-- STEP 3 -->
          <div class="doctor-pane" data-pane="3">
            <div class="doctor-pane-head doctor-pane-head--split">
              <div>
                <h2>المستندات المهنية</h2>
                <p>أرفق الملفات المطلوبة ليتم تقييم الطلب بشكل أسرع.</p>
              </div>

              <div class="doc-status-card">
                <span class="doc-status-card__label">جاهزية الملف</span>
                <div class="doc-status-card__line">
                  <span id="docsProgressFill"></span>
                </div>
                <strong id="docsProgressText">0 / 3 مكتمل</strong>
              </div>
            </div>

            <div class="upload-grid">
              <label class="upload-tile" data-upload-tile>
                <input type="file" class="upload-input" name="profile_photo" accept=".jpg,.jpeg,.png,.webp" data-upload-input />

                <div class="upload-tile__head">
                  <div class="upload-tile__icon">
                    <i class="fa-regular fa-id-badge"></i>
                  </div>
                  <div class="upload-tile__text">
                    <h3>الصورة الشخصية</h3>
                    <p>JPG / PNG / WEBP</p>
                  </div>
                  <span class="upload-tile__badge">مطلوب</span>
                </div>

                <div class="upload-tile__drop">
                  <div class="upload-tile__drop-icon">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                  </div>
                  <span>اسحب الملف هنا أو اختره من جهازك</span>
                </div>

                <div class="upload-tile__footer">
                  <div class="upload-tile__filename" data-upload-name>لم يتم إرفاق ملف بعد</div>
                </div>
              </label>

              <label class="upload-tile" data-upload-tile>
                <input type="file" class="upload-input" name="license_file" accept=".pdf,.jpg,.jpeg,.png" data-upload-input />

                <div class="upload-tile__head">
                  <div class="upload-tile__icon">
                    <i class="fa-solid fa-file-shield"></i>
                  </div>
                  <div class="upload-tile__text">
                    <h3>إثبات مزاولة المهنة</h3>
                    <p>PDF / JPG / PNG</p>
                  </div>
                  <span class="upload-tile__badge">مطلوب</span>
                </div>

                <div class="upload-tile__drop">
                  <div class="upload-tile__drop-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                  </div>
                  <span>أضف الترخيص أو الإثبات المهني</span>
                </div>

                <div class="upload-tile__footer">
                  <div class="upload-tile__filename" data-upload-name>لم يتم إرفاق ملف بعد</div>
                </div>
              </label>

              <label class="upload-tile" data-upload-tile>
                <input type="file" class="upload-input" name="cv_file" accept=".pdf,.doc,.docx" data-upload-input />

                <div class="upload-tile__head">
                  <div class="upload-tile__icon">
                    <i class="fa-regular fa-file-lines"></i>
                  </div>
                  <div class="upload-tile__text">
                    <h3>السيرة الذاتية</h3>
                    <p>PDF / DOC / DOCX</p>
                  </div>
                  <span class="upload-tile__badge upload-tile__badge--soft">اختياري</span>
                </div>

                <div class="upload-tile__drop">
                  <div class="upload-tile__drop-icon">
                    <i class="fa-solid fa-file-arrow-up"></i>
                  </div>
                  <span>أرفق السيرة الذاتية إن وجدت</span>
                </div>

                <div class="upload-tile__footer">
                  <div class="upload-tile__filename" data-upload-name>لم يتم إرفاق ملف بعد</div>
                </div>
              </label>
            </div>

            @error('profile_photo')
              <small class="field-error">{{ $message }}</small>
            @enderror

            @error('license_file')
              <small class="field-error">{{ $message }}</small>
            @enderror

            @error('cv_file')
              <small class="field-error">{{ $message }}</small>
            @enderror
          </div>

          <!-- STEP 4 -->
          <div class="doctor-pane" data-pane="4">
            <div class="doctor-pane-head">
              <h2>المراجعة النهائية</h2>
              <p>راجع جميع بياناتك قبل إرسال الطلب.</p>
            </div>

            <div class="review-grid">
              <div class="review-card">
                <span>الاسم الكامل</span>
                <strong id="reviewName">—</strong>
              </div>

              <div class="review-card">
                <span>البريد الإلكتروني</span>
                <strong id="reviewEmail">—</strong>
              </div>

              <div class="review-card">
                <span>رقم الهاتف</span>
                <strong id="reviewPhone">—</strong>
              </div>

              <div class="review-card">
                <span>مكان العمل</span>
                <strong id="reviewWorkplace">—</strong>
              </div>

              <div class="review-card">
                <span>التخصص</span>
                <strong id="reviewSpecialty">—</strong>
              </div>

              <div class="review-card">
                <span>سنوات الخبرة</span>
                <strong id="reviewExperience">—</strong>
              </div>

              <div class="review-card">
                <span>رقم الترخيص</span>
                <strong id="reviewLicense">—</strong>
              </div>

              <div class="review-card review-card--full">
                <span>النبذة المهنية</span>
                <strong id="reviewBio">—</strong>
              </div>

              <div class="review-card">
                <span>الصورة الشخصية</span>
                <strong id="reviewProfilePhoto">—</strong>
              </div>

              <div class="review-card">
                <span>إثبات مزاولة المهنة</span>
                <strong id="reviewLicenseFile">—</strong>
              </div>

              <div class="review-card">
                <span>السيرة الذاتية</span>
                <strong id="reviewCvFile">—</strong>
              </div>
            </div>

            <label class="agreement-check agreement-check--review">
              <input type="checkbox" id="doctorAgreement" name="agreement" value="1" {{ old('agreement') ? 'checked' : '' }} />
              <span>أؤكد أن جميع البيانات المقدمة صحيحة وقابلة للمراجعة من قبل الإدارة.</span>
            </label>

            @error('agreement')
              <small class="field-error">{{ $message }}</small>
            @enderror
          </div>

          <div class="doctor-actions" id="doctorActions">
            <button type="button" class="secondary-action" id="prevDoctorBtn" disabled>
              السابق
            </button>

            <button type="button" class="primary-action" id="nextDoctorBtn">
              التالي
            </button>
          </div>

          <div class="bottom-switch">
            لديك حساب طبيب بالفعل؟
            <a href="{{ route('login') }}">تسجيل الدخول</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

@if(session('doctor_apply_success'))
  <div class="doctor-success-modal is-open" id="doctorSuccessModal">
    <div class="doctor-success-modal__overlay" data-close-doctor-modal></div>

    <div class="doctor-success-modal__card">
      <button type="button" class="doctor-success-modal__close" data-close-doctor-modal aria-label="إغلاق">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="doctor-success-modal__icon">
        <i class="fa-solid fa-check"></i>
      </div>

      <span class="doctor-success-modal__badge">
        تم استلام الطلب
      </span>

      <h2>{{ session('doctor_apply_success') }}</h2>

      <p>
        {{ session('doctor_apply_note') }}
      </p>

      <div class="doctor-success-modal__steps">
        <div class="doctor-success-step">
          <span></span>
          <div>
            <strong>تم حفظ بياناتك</strong>
            <small>وصل طلب الانضمام إلى إدارة منصة اتزان.</small>
          </div>
        </div>

        <div class="doctor-success-step">
          <span></span>
          <div>
            <strong>الطلب قيد المراجعة</strong>
            <small>سيتم مراجعة بياناتك والمستندات المرفقة قبل اتخاذ القرار.</small>
          </div>
        </div>

        <div class="doctor-success-step">
          <span></span>
          <div>
            <strong>راجعي بريدك الإلكتروني</strong>
            <small>سيصلك تحديث عند قبول الطلب أو عند الحاجة إلى معلومات إضافية.</small>
          </div>
        </div>
      </div>

      <div class="doctor-success-modal__actions">
        <a href="{{ route('home') }}" class="doctor-success-btn doctor-success-btn-primary">
          العودة للرئيسية
        </a>

        <button type="button" class="doctor-success-btn doctor-success-btn-outline" data-close-doctor-modal>
          البقاء في الصفحة
        </button>
      </div>
    </div>
  </div>
@endif

@endsection

@push('scripts')
<script src="{{ asset('front/js/join-doctor.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('doctorSuccessModal');

  if (!modal) {
    return;
  }

  const closeButtons = modal.querySelectorAll('[data-close-doctor-modal]');

  const closeModal = () => {
    modal.classList.remove('is-open');
  };

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeModal();
    }
  });
});
</script>
@endpush
