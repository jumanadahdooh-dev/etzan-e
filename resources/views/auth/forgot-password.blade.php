@extends('layouts.public')

@section('title', 'نسيت كلمة المرور | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/auth.css') }}">
<link rel="stylesheet" href="{{ asset('front/css/forgot.css') }}">

@endpush

@section('content')
<section class="recover-page">
  <div class="recover-shell">
    <div class="recover-wrap">

      <div class="mini-deco mini-deco--1">
        <i class="fa-regular fa-envelope"></i>
      </div>

      <div class="mini-deco mini-deco--2">
        <i class="fa-solid fa-key"></i>
      </div>

      <div class="mini-deco mini-deco--3">
        <i class="fa-solid fa-shield-halved"></i>
      </div>

      <div class="mini-deco mini-deco--4">
        <i class="fa-solid fa-lock"></i>
      </div>

      <div class="mini-deco mini-deco--5">
        <i class="fa-solid fa-circle-check"></i>
      </div>

      <div class="recover-card">
        <div class="recover-badge">
          <i class="fa-solid fa-key"></i>
          استعادة الوصول
        </div>

        <div class="recover-head">
          <h1>
            @if ($currentStep == 1)
              نسيت كلمة المرور؟
            @elseif ($currentStep == 2)
              تحقق من بريدك الإلكتروني
            @else
              أنشئ كلمة مرور جديدة
            @endif
          </h1>

          <p>
            @if ($currentStep == 1)
              أدخل بريدك الإلكتروني ثم أكمل التحقق لتعيين كلمة مرور جديدة بسهولة وأمان.
            @elseif ($currentStep == 2)
              أدخل رمز التحقق المكوّن من 4 أرقام الذي تم إرساله إلى بريدك الإلكتروني.
            @else
              أدخل كلمة المرور الجديدة ثم أكدها لإكمال العملية.
            @endif
          </p>
        </div>

        <div class="recover-stepper">
          <div class="recover-progress-line">
            <span style="width:
              @if ($currentStep == 1) 0%;
              @elseif ($currentStep == 2) 50%;
              @else 100%;
              @endif
            "></span>
          </div>

          <div class="recover-steps">
            <div class="recover-step {{ $currentStep == 1 ? 'is-active' : ($currentStep > 1 ? 'is-done' : '') }}">
              <span>1</span>
              <small>البريد</small>
            </div>

            <div class="recover-step {{ $currentStep == 2 ? 'is-active' : ($currentStep > 2 ? 'is-done' : '') }}">
              <span>2</span>
              <small>التحقق</small>
            </div>

            <div class="recover-step {{ $currentStep == 3 ? 'is-active' : '' }}">
              <span>3</span>
              <small>كلمة المرور</small>
            </div>
          </div>
        </div>

        @if (session('success'))
          <div class="auth-alert auth-alert--success">
            {{ session('success') }}
          </div>
        @endif

        {{-- STEP 1 --}}
        @if ($currentStep == 1)
          <form class="recover-form" method="POST" action="{{ route('forgot-password.send-code') }}">
            @csrf

            <div class="recover-pane is-active">
              <div class="form-field">
                <label for="recoverEmail">البريد الإلكتروني</label>
                <div class="input-shell @error('email') is-invalid @enderror">
                  <i class="fa-regular fa-envelope"></i>
                  <input
                    type="email"
                    id="recoverEmail"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="أدخل بريدك الإلكتروني"
                  />
                </div>
                @error('email')
                  <small class="field-error">{{ $message }}</small>
                @enderror
              </div>
            </div>

            <div class="recover-actions">
              <button type="submit" class="primary-action">إرسال الرمز</button>
            </div>
          </form>
        @endif

        {{-- STEP 2 --}}
        @if ($currentStep == 2)
          <form class="recover-form" method="POST" action="{{ route('forgot-password.verify-code') }}">
            @csrf
            <input type="hidden" name="email" value="{{ request('email') }}">

            <div class="recover-pane is-active">
             <div class="code-field">
                <label>رمز التحقق</label>

                <div class="code-boxes @error('code') is-invalid @enderror">
                    <input type="text" maxlength="1" inputmode="numeric" class="code-digit" />
                    <input type="text" maxlength="1" inputmode="numeric" class="code-digit" />
                    <input type="text" maxlength="1" inputmode="numeric" class="code-digit" />
                    <input type="text" maxlength="1" inputmode="numeric" class="code-digit" />
                </div>

                <input type="hidden" name="code" id="fullCode" value="{{ old('code') }}">

                <div class="code-help">
                    تم إرسال رمز التحقق إلى بريدك الإلكتروني.
                </div>

                @error('code')
                    <small class="field-error">{{ $message }}</small>
                @enderror
                </div>
            </div>

            <div class="recover-actions">
              <a href="{{ route('forgot-password') }}" class="secondary-action primary-action--link">السابق</a>
              <button type="submit" class="primary-action">تحقق من الرمز</button>
            </div>
          </form>

          <div class="bottom-switch bottom-switch--small">
            لم يصلك الرمز؟
            <form method="POST" action="{{ route('forgot-password.resend-code') }}" class="resend-inline-form">
              @csrf
              <input type="hidden" name="email" value="{{ request('email') }}">
              <button type="submit" id="resendCodeBtn">إعادة الإرسال</button>
            </form>
          </div>
        @endif

        {{-- STEP 3 --}}
        @if ($currentStep == 3)
          <form class="recover-form" method="POST" action="{{ route('forgot-password.reset') }}">
            @csrf
            <input type="hidden" name="email" value="{{ request('email', session('password_reset_email')) }}">

            <div class="recover-pane is-active">
              <div class="form-field">
                <label for="newPassword">كلمة المرور الجديدة</label>
                <div class="input-shell input-shell--password @error('password') is-invalid @enderror">
                  <i class="fa-solid fa-lock"></i>
                  <input
                    type="password"
                    id="newPassword"
                    name="password"
                    placeholder="أدخل كلمة المرور الجديدة"
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
                <label for="confirmPassword">تأكيد كلمة المرور</label>
                <div class="input-shell input-shell--password">
                  <i class="fa-solid fa-shield-halved"></i>
                  <input
                    type="password"
                    id="confirmPassword"
                    name="password_confirmation"
                    placeholder="أعد إدخال كلمة المرور"
                  />
                  <button type="button" class="toggle-pass" aria-label="إظهار كلمة المرور">
                    <i class="fa-regular fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="strength-wrap">
                <span>مستوى الأمان</span>
                <div class="strength-bar">
                  <span id="strengthFill"></span>
                </div>
              </div>
            </div>

            <div class="recover-actions">
              <a href="{{ route('forgot-password', ['step' => 2, 'email' => request('email', session('password_reset_email'))]) }}" class="secondary-action primary-action--link">السابق</a>
              <button type="submit" class="primary-action">تحديث كلمة المرور</button>
            </div>
          </form>
        @endif

        <div class="bottom-switch">
          تذكرت كلمة المرور؟
          <a href="{{ route('login') }}">العودة لتسجيل الدخول</a>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('front/js/forgot.js') }}"></script>
@endpush
