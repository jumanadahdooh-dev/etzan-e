@extends('layouts.public')

@section('title', 'إنشاء حساب | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/auth.css') }}">

@endpush

@section('content')
<section class="auth-clean-page">
  <div class="auth-clean-shell">
    <div class="auth-clean-wrap">

      <div class="mini-deco mini-deco--1">
        <i class="fa-regular fa-user"></i>
      </div>

      <div class="mini-deco mini-deco--2">
        <i class="fa-solid fa-lock"></i>
      </div>

      <div class="mini-deco mini-deco--3">
        <i class="fa-regular fa-envelope"></i>
      </div>

      <div class="mini-deco mini-deco--4">
        <i class="fa-solid fa-shield-halved"></i>
      </div>

      <div class="mini-deco mini-deco--5">
        <i class="fa-solid fa-heart-pulse"></i>
      </div>

      <div class="auth-clean-card">
        <div class="auth-clean-badge">
          <i class="fa-solid fa-shield-heart"></i>
          بوابة صحية ذكية
        </div>

        <div class="auth-mode is-active">
          <div class="auth-clean-head">
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
               <a href="{{ route('google.redirect', ['from' => 'register']) }}" class="social-icon-btn js-social-popup" aria-label="التسجيل بجوجل">
                    <i class="fa-brands fa-google"></i>
                </a>

                <a href="{{ route('facebook.redirect', ['from' => 'register']) }}" class="social-icon-btn js-social-popup" aria-label="التسجيل بفيسبوك">
                    <i class="fa-brands fa-facebook-f"></i>
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
  </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('front/js/auth.js') }}"></script>
@endpush
