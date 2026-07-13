@extends('layouts.patient')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/support.css') }}">
@endpush

@section('content')
@php
    $supportMessages = $supportMessages ?? [];
@endphp

<section class="support-chat-page">
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
            <span>اكتب الرسالة قبل الإرسال.</span>
        </div>
    @endif

    <div class="support-chat-card">
        <div class="support-chat-header">
            <div class="support-chat-user">
                <span class="support-avatar">
                    <i data-lucide="headphones"></i>
                </span>

                <div>
                    <h1>الدعم الفني</h1>
                    <p>
                        <span class="online-dot"></span>
                        إدارة اتزان
                    </p>
                </div>
            </div>
        </div>

        <div class="support-chat-body" data-live-support-body>
            @forelse ($supportMessages as $message)
                @php
                    $isPatient = ($message['sender_type'] ?? 'patient') === 'patient';
                @endphp

                <div class="support-message {{ $isPatient ? 'is-patient' : 'is-admin' }}">
                    @if (!$isPatient)
                        <span class="support-message-avatar">
                            <i data-lucide="headphones"></i>
                        </span>
                    @endif

                    <div class="support-bubble">
                        <div class="support-bubble-meta">
                            <strong>{{ $isPatient ? 'أنت' : 'فريق الدعم' }}</strong>
                            <small>{{ $message['time'] ?? '' }}</small>
                        </div>

                        <p>{{ $message['message'] ?? '' }}</p>
                    </div>
                </div>
            @empty
                <div class="support-message is-admin">
                    <span class="support-message-avatar">
                        <i data-lucide="headphones"></i>
                    </span>

                    <div class="support-bubble">
                        <div class="support-bubble-meta">
                            <strong>فريق الدعم</strong>
                            <small>الآن</small>
                        </div>

                        <p>مرحبًا، اكتب رسالتك هنا وسيتم إرسالها مباشرة لإدارة اتزان لمتابعتها.</p>
                    </div>
                </div>
            @endforelse
        </div>

        <form
            action="{{ route('patient.support.send') }}"
            method="POST"
            class="support-composer-form"
            data-chat-form
        >
            @csrf

            <div class="support-composer">
                <div class="support-input-shell">
                    <textarea
                        name="message"
                        class="support-message-textarea"
                        placeholder="اكتبي رسالتك لفريق الدعم هنا..."
                        rows="1"
                        required
                        data-auto-grow
                    >{{ old('message') }}</textarea>
                </div>

                <button type="submit" class="support-send-btn" aria-label="إرسال">
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
