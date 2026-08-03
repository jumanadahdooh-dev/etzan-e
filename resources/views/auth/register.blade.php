@extends('layouts.auth')

@section('title', 'إنشاء حساب | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/auth.css') }}">

@endpush

@section('content')
<div class="auth-hero-page">

  <div class="auth-hero__art">
    <div class="auth-decor" aria-hidden="true">
      <span class="auth-decor__dots auth-decor__dots--1"></span>
      <span class="auth-decor__dots auth-decor__dots--2"></span>
      <span class="auth-decor__ring auth-decor__ring--1"></span>
      <span class="auth-decor__ring auth-decor__ring--2"></span>
      <span class="auth-decor__ring auth-decor__ring--3"></span>
      <span class="auth-decor__ring auth-decor__ring--4"></span>
      <span class="auth-decor__dot-solid"></span>
      <span class="auth-decor__blob"></span>
    </div>

    <div class="auth-hero__art-head">
      <h2>ابدأ رحلتك الصحية مع اتزان</h2>
      <p>خطوة وحدة تفصلك عن متابعة صحية ذكية بإشراف نخبة من الأطباء.</p>
    </div>

    <img
      class="auth-hero__bg"
      src="{{ asset('front/image/auth-hero-1.png') }}"
      alt="إنشاء حساب جديد وربط ملف مريض على منصة اتزان"
    >

    <div class="auth-split__media-content">
      <span class="auth-split__chip">
        <i class="fa-solid fa-user-plus"></i>
        ‎+10,000 مستخدم يثق بمنصتنا
      </span>
    </div>
  </div>

  <div class="auth-hero__form auth-hero__form--wide">
    <div class="auth-hero__form-card">

      
      <div class="auth-form-panel__head">
        <span class="auth-form-panel__eyebrow">إنشاء حساب جديد</span>
        <h1>أنشئ حسابك بسهولة</h1>
        <p>
          ابدأ بخطوة بسيطة للوصول إلى تجربة صحية منظمة وآمنة داخل المنصة.
        </p>
      </div>

      <form class="auth-form auth-form--grid" method="POST" action="{{ route('register.submit') }}">
        @csrf

            <div class="form-field">
              <label for="registerName">الاسم الكامل</label>
              <div class="input-shell @error('name') is-invalid @enderror">
                <i class="fa-regular fa-user"></i>
                <input
                  type="text"
                  id="registerName"
                  name="name"
                  value="{{ old('name') }}"
                  placeholder="أدخل اسمك الكامل"
                />
              </div>
              @error('name')
                <small class="field-error">{{ $message }}</small>
              @enderror
            </div>

            <div class="form-field">
              <label for="registerEmail">البريد الإلكتروني</label>
              <div class="input-shell @error('email') is-invalid @enderror">
                <i class="fa-regular fa-envelope"></i>
                <input
                  type="email"
                  id="registerEmail"
                  name="email"
                  value="{{ old('email') }}"
                  placeholder="أدخل بريدك الإلكتروني"
                />
              </div>
              @error('email')
                <small class="field-error">{{ $message }}</small>
              @enderror
            </div>

            <div class="form-field">
              <label for="registerPassword">كلمة المرور</label>
              <div class="input-shell input-shell--password @error('password') is-invalid @enderror">
                <i class="fa-solid fa-lock"></i>
                <input
                  type="password"
                  id="registerPassword"
                  name="password"
                  placeholder="أدخل كلمة المرور"
                />
                <button type="button" class="toggle-pass" aria-label="إظهار كلمة المرور">
                  <i class="fa-regular fa-eye"></i>
                </button>
              </div>
              @error('password')
                <small class="field-error">{{ $message }}</small>
              @enderror
            </div>

            <div class="form-field">
              <label for="registerConfirm">تأكيد كلمة المرور</label>
              <div class="input-shell input-shell--password @error('password_confirmation') is-invalid @enderror">
                <i class="fa-solid fa-shield-halved"></i>
                <input
                  type="password"
                  id="registerConfirm"
                  name="password_confirmation"
                  placeholder="أعد إدخال كلمة المرور"
                />
                <button type="button" class="toggle-pass" aria-label="إظهار كلمة المرور">
                  <i class="fa-regular fa-eye"></i>
                </button>
              </div>
              @error('password_confirmation')
                <small class="field-error">{{ $message }}</small>
              @enderror
            </div>

            

            <div class="form-field form-field--full">
              <label class="agreement-check">
                <input type="checkbox" name="agree_terms" {{ old('agree_terms') ? 'checked' : '' }} />
                <span>
                  أوافق على
                  <a href="{{ route('terms') }}" target="_blank">الشروط والأحكام</a>
                  و
                  <a href="{{ route('privacy') }}" target="_blank">سياسة الخصوصية</a>
                </span>
              </label>

              @error('agree_terms')
                <small class="field-error">{{ $message }}</small>
              @enderror
            </div>

            <div class="form-field form-field--full">
              <button type="submit" class="primary-action">إنشاء الحساب</button>
            </div>

            <div class="form-field form-field--full">
              <div class="auth-divider">
                <span>أو المتابعة باستخدام</span>
              </div>
            </div>

            <div class="form-field form-field--full">
              <div class="social-row">
                <a href="{{ route('google.redirect', ['from' => 'register']) }}" class="social-btn js-social-popup" aria-label="التسجيل بجوجل">
                  <span class="social-btn__icon">
                    <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                      <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                      <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                      <path fill="#FBBC05" d="M10.53 28.59A14.5 14.5 0 0 1 9.5 24c0-1.59.27-3.13.76-4.59l-7.98-6.19A23.94 23.94 0 0 0 0 24c0 3.87.93 7.53 2.56 10.78z"/>
                      <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                  </span>
                  التسجيل عبر Google
                </a>
              </div>
            </div>

            <div class="form-field form-field--full">
              <div class="bottom-switch">
                لديك حساب بالفعل؟
                <a href="{{ route('login') }}">تسجيل الدخول</a>
              </div>
            </div>
      </form>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('front/js/auth.js') }}"></script>
@endpush
