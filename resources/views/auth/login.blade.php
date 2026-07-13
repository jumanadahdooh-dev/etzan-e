@extends('layouts.public')

@section('title', 'تسجيل الدخول | اتزان')

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
            <h1>مرحبًا بعودتك</h1>
            <p>
              سجّل دخولك للوصول إلى حسابك ومتابعة رحلتك الصحية بسهولة وأمان.
            </p>
          </div>

          @if (session('success'))
            <div class="auth-alert auth-alert--success">
              {{ session('success') }}
            </div>
          @endif

          <form class="auth-form auth-form--stack" method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="form-field">
              <label for="loginEmail">البريد الإلكتروني</label>
              <div class="input-shell @error('email') is-invalid @enderror">
                <i class="fa-regular fa-envelope"></i>
                <input
                  type="email"
                  id="loginEmail"
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
              <label for="loginPassword">كلمة المرور</label>
              <div class="input-shell input-shell--password @error('password') is-invalid @enderror">
                <i class="fa-solid fa-lock"></i>
                <input
                  type="password"
                  id="loginPassword"
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

            <div class="login-tools">
              <label class="agreement-check agreement-check--simple">
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} />
                <span>تذكرني</span>
              </label>

              <a href="{{ route('forgot-password') }}" class="forgot-link">نسيت كلمة المرور؟</a>
            </div>

            <div class="form-field">
              <button type="submit" class="primary-action">تسجيل الدخول</button>
            </div>

            <div class="form-field">
              <div class="auth-divider">
                <span>أو المتابعة باستخدام</span>
              </div>
            </div>

            <div class="form-field">
              <div class="social-row">
                 <a href="{{ route('google.redirect', ['from' => 'login']) }}" class="social-icon-btn js-social-popup" aria-label="تسجيل الدخول بجوجل">
                    <i class="fa-brands fa-google"></i>
                </a>

                <a href="{{ route('facebook.redirect', ['from' => 'login']) }}" class="social-icon-btn js-social-popup" aria-label="تسجيل الدخول بفيسبوك">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
              </div>
            </div>

            <div class="form-field">
              <div class="bottom-switch">
                ليس لديك حساب؟
                <a href="{{ route('register') }}">إنشاء حساب</a>
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
