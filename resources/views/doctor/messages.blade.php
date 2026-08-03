@extends('layouts.doctor')

@php
    $pageTitle = 'الرسائل';
    $activePage = 'messages';
@endphp

@section('content')
<section class="ddash">

    @if (session('success'))
        <div class="doctor-status" style="margin-top:12px">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="doctor-status danger" style="margin-top:12px">{{ session('error') }}</div>
    @endif

    <div class="doctor-chat">
        <div class="doctor-panel">
            <div class="doctor-panel-head">
                <div><span>Inbox</span><h2>المحادثات</h2></div>
                <i data-lucide="message-circle"></i>
            </div>

            <div class="conversation-list">
                @forelse ($conversations as $conversation)
                    <a href="{{ route('doctor.messages.show', $conversation) }}"
                       class="doctor-item"
                       style="text-decoration:none; {{ isset($openConversation) && $openConversation->id === $conversation->id ? 'border-color:var(--primary)' : '' }}">
                        <span class="avatar">{{ mb_substr($conversation->patient_name, 0, 1) }}</span>
                        <div>
                            <strong>{{ $conversation->patient_name }}</strong>
                            <small>{{ \Illuminate\Support\Str::limit($conversation->latestMessage?->body_text ?? 'لا رسائل بعد', 40) }}</small>
                        </div>
                        @if ($conversation->unread_count > 0)
                            <span class="nav-badge">{{ $conversation->unread_count }}</span>
                        @endif
                    </a>
                @empty
                    <p style="color:var(--muted); font-size:13px; font-weight:700; padding:8px;">
                        ما في أي محادثة لسا. المحادثة بتنبلش لما مريضك يبعتلك أول رسالة من صفحة "رسائلي" عندو.
                    </p>
                @endforelse
            </div>
        </div>

        <div class="doctor-panel chat-window">
            @if (isset($openConversation))
                <div class="doctor-panel-head">
                    <div><span>{{ $openPatientName }}</span><h2>محادثة المريض</h2></div>
                    <a class="doctor-btn" href="{{ route('doctor.patient-profile.show', $openPatientProfileId) }}">فتح الملف</a>
                </div>

                <div class="chat-messages">
                    @forelse ($openMessages as $message)
                        <div class="bubble {{ $message->sender_type === 'doctor' ? 'me' : '' }}">
                            {{ $message->body_text }}
                        </div>
                    @empty
                        <p style="color:var(--muted); font-size:13px; font-weight:700;">لا رسائل بهاي المحادثة بعد.</p>
                    @endforelse
                </div>

                <form class="chat-compose" method="POST" action="{{ route('doctor.messages.send', $openConversation) }}">
                    @csrf
                    <input type="text" name="message" placeholder="اكتب ردك هنا..." maxlength="2000" required>
                    <button type="submit" class="doctor-btn primary"><i data-lucide="send"></i> إرسال</button>
                </form>
            @else
                <div class="doctor-panel-head">
                    <div><span>Chat</span><h2>اختر محادثة من القائمة</h2></div>
                    <i data-lucide="message-circle"></i>
                </div>
                <p style="color:var(--muted); font-size:13px; font-weight:700;">
                    اختر مريض من قائمة المحادثات على اليمين حتى تشوف الرسائل وترد عليها.
                </p>
            @endif
        </div>
    </div>
</section>
@endsection
