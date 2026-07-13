@extends('layouts.patient')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/messages.css') }}">
@endpush

@section('content')
@php
    $doctorData = $doctor ?? [];
    $messages = $messages ?? [];
@endphp

<section class="doctor-chat-page">
    @if (session('success'))
        <div class="chat-alert is-success">
            <i data-lucide="badge-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="chat-alert is-error">
            <i data-lucide="circle-alert"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="chat-alert is-error">
            <i data-lucide="circle-alert"></i>
            <span>اكتبي رسالة قبل الإرسال.</span>
        </div>
    @endif

    <div class="doctor-chat-card">
        <div class="doctor-chat-header">
            <div class="doctor-chat-user">
                <span class="doctor-avatar">
                    @if (!empty($doctorData['avatar']))
                        <img src="{{ $doctorData['avatar'] }}" alt="صورة الطبيب">
                    @else
                        <i data-lucide="stethoscope"></i>
                    @endif
                </span>

                <div>
                    <h1>{{ $doctorData['name'] ?? 'د. طبيب اتزان' }}</h1>
                    <p>
                        <span class="online-dot"></span>
                        {{ $doctorData['specialty'] ?? 'طبيب المتابعة' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="doctor-chat-body" data-live-chat-body>
            @forelse ($messages as $message)
                <div class="doctor-message {{ !empty($message['is_me']) ? 'is-me' : 'is-other' }}">
                    <div class="doctor-bubble">
                        <div class="doctor-bubble-meta">
                            <strong>
                                {{ $message['sender'] ?? (!empty($message['is_me']) ? 'أنت' : 'الطبيب') }}
                            </strong>
                            <small>{{ $message['time'] ?? '' }}</small>
                        </div>

                        @if (!empty($message['body']))
                            <p>{{ $message['body'] }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="doctor-message is-other">
                    <div class="doctor-bubble">
                        <div class="doctor-bubble-meta">
                            <strong>{{ $doctorData['name'] ?? 'الطبيب' }}</strong>
                            <small>الآن</small>
                        </div>

                        <p>مرحبًا، يمكنك إرسال استفسارك هنا وسأتابع معك داخل المحادثة.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <form
            action="{{ route('patient.messages.send') }}"
            method="POST"
            class="chat-composer-form"
            data-chat-form
        >
            @csrf

            <div class="chat-composer">
                <div class="chat-input-shell">
                    <textarea
                        name="message"
                        class="chat-message-textarea"
                        placeholder="اكتبي رسالتك هنا..."
                        rows="1"
                        required
                        data-auto-grow
                    >{{ old('message') }}</textarea>
                </div>

                <button type="submit" class="chat-send-btn" aria-label="إرسال">
                    <i data-lucide="send"></i>
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/patient-chat.js') }}"></script>
@endpush
