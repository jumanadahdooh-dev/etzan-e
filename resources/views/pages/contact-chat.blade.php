@extends('layouts.public')

@section('title', 'اتصل بنا | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/contact-chat.css') }}">
@endpush

@section('content')

<section class="page-hero contact-page-hero">
  <div class="container">
    <div class="hero-card contact-hero-card">
      <div class="hero-text">
        <span class="hero-kicker">
          <i class="fa-regular fa-message"></i>
          تواصل معنا
        </span>

        <h1>نحن هنا للاستماع إليك</h1>

        <p>
          أرسل رسالتك وسيقوم فريق الدعم بمتابعتها. سيتم الرد عليك عبر البريد الإلكتروني
          الذي تكتبه في النموذج.
        </p>
      </div>

      <div class="hero-badges">
        <div class="hero-badge">
          <span class="badge-dot"></span>
          <span>استقبال الرسائل متاح</span>
        </div>

        <div class="hero-badge soft">
          <span>الرد عبر البريد الإلكتروني</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="chat-section contact-chat-section">
  <div class="container">

    @if(session('success'))
      <div class="contact-alert contact-alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span>{{ session('success') }}</span>
      </div>
    @endif

    @if(session('error'))
      <div class="contact-alert contact-alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span>{{ session('error') }}</span>
      </div>
    @endif

    @if($errors->any())
      <div class="contact-alert contact-alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span>{{ $errors->first() }}</span>
      </div>
    @endif

    <div class="chat-layout contact-layout">

      <aside class="chat-sidebar">
        <div class="sidebar-card support-info-card">
          <span class="side-mini-badge">مساعدة أسرع</span>

          <h2>ابدأ بشكل أسرع</h2>

          <p>
            اختاري الموضوع الأقرب لرسالتك، أو اكتبي رسالتك مباشرة داخل المحادثة.
          </p>

          <div class="quick-actions">
            <button class="quick-chip" type="button" data-message="عندي استفسار عن التسجيل في منصة اتزان.">
              <i class="fa-regular fa-user"></i>
              عندي استفسار عن التسجيل
            </button>

            <button class="quick-chip" type="button" data-message="أواجه مشكلة داخل الحساب وأحتاج مساعدة.">
              <i class="fa-solid fa-triangle-exclamation"></i>
              أواجه مشكلة داخل الحساب
            </button>

            <button class="quick-chip" type="button" data-message="أريد إرسال اقتراح لتحسين تجربة منصة اتزان.">
              <i class="fa-regular fa-lightbulb"></i>
              أريد إرسال اقتراح
            </button>

            <button class="quick-chip" type="button" data-message="أحتاج مساعدة سريعة من فريق الدعم.">
              <i class="fa-solid fa-headset"></i>
              أحتاج مساعدة سريعة
            </button>
          </div>
        </div>

        <div class="sidebar-card compact support-status-card">
          <div class="sidebar-info">
            <span class="info-label">حالة المحادثة</span>
            <strong>مفتوحة الآن</strong>
          </div>

          <div class="sidebar-info">
            <span class="info-label">ترسل إلى</span>
            <strong>إدارة اتزان</strong>
          </div>

          <div class="sidebar-info">
            <span class="info-label">طريقة الرد</span>
            <strong>على بريدك الإلكتروني</strong>
          </div>
        </div>
      </aside>

      <div class="chat-shell contact-chat-shell">
        <div class="chat-topbar">
          <div class="chat-profile">
            <div class="chat-avatar contact-support-avatar">
              <i class="fa-solid fa-headset"></i>
            </div>

            <div class="chat-profile-text">
              <h3>فريق الدعم</h3>

              <div class="chat-status">
                <span class="status-indicator"></span>
                <span>جاهز لاستقبال رسالتك</span>
              </div>
            </div>
          </div>

          <div class="chat-topbar-tag">محادثة دعم</div>
        </div>

        <div class="chat-messages" id="chatMessages">
          <div class="messages-date">اليوم</div>

          <div class="message-row support-message">
            <div class="message-bubble support-bubble">
              أهلًا بك في اتزان 💚<br>
              اكتبي اسمك وبريدك ورسالتك، وسيتم الرد عليك عبر البريد الإلكتروني.
            </div>
          </div>

          <div class="typing-row" id="typingRow" hidden>
            <div class="typing-bubble">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        </div>

        <div class="chat-composer">
          <form
            id="chatForm"
            class="composer-form contact-form"
            action="{{ route('contact.messages.store') }}"
            method="POST"
            data-store-url="{{ route('contact.messages.store') }}"
            novalidate
          >
            @csrf

            <div class="guest-fields">
              <div class="guest-field">
                <label for="guestName">اسمك</label>
                <input
                  type="text"
                  id="guestName"
                  name="guest_name"
                  value="{{ old('guest_name') }}"
                  placeholder="مثال: سارة أحمد"
                >
              </div>

              <div class="guest-field">
                <label for="guestEmail">بريدك الإلكتروني</label>
                <input
                  type="email"
                  id="guestEmail"
                  name="guest_email"
                  value="{{ old('guest_email') }}"
                  placeholder="example@email.com"
                >
              </div>
            </div>

            <div class="contact-subject-row">
              <label for="subjectInput">الموضوع</label>
              <input
                type="text"
                id="subjectInput"
                name="subject"
                value="{{ old('subject') }}"
                placeholder="مثال: مشكلة في الحساب"
              >
            </div>

            <div class="contact-message-row">
              <div class="message-field">
                <textarea
                  id="messageInput"
                  name="message"
                  class="message-input"
                  rows="2"
                  placeholder="اكتبي رسالتك هنا..."
                >{{ old('message') }}</textarea>
              </div>

              <button type="submit" class="send-button" aria-label="إرسال">
                <svg viewBox="0 0 24 24" fill="none">
                  <path
                    d="M10 14L21 3M10 14L14 21L21 3M10 14L3 10L21 3"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </button>
            </div>

            <p class="composer-note">
              سيتم إرسال رد فريق الدعم إلى البريد الإلكتروني الذي كتبته.
            </p>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>

@endsection

@push('scripts')
<script src="{{ asset('front/js/contact-chat.js') }}"></script>
@endpush
