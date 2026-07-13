@extends('layouts.patient')

@php
    $pageTitle = $pageTitle ?? 'مساعد اتزان الذكي';
    $activePage = $activePage ?? 'ai-chat';

    $conversations = collect($conversations ?? []);
    $messages = collect($messages ?? []);
    $activeConversation = $activeConversation ?? null;
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('front/css/patient/ai-chat.css') }}">
@endpush

@section('content')
<section
    class="ai-chat-page"
    data-ai-chat-page
    data-send-url="{{ route('patient.ai-chat.send') }}"
    data-chat-base="{{ url('/patient/ai-chat') }}"
>
    @if (session('success'))
        <div class="ai-chat-alert is-success">
            <i data-lucide="badge-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="ai-chat-alert is-error">
            <i data-lucide="circle-alert"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="ai-chat-shell">
        <aside class="ai-chat-sidebar">
            <div class="ai-chat-side-head">
                <div>
                    <span>Etzan AI</span>
                    <h2>محادثاتي</h2>
                </div>

                <a href="{{ route('patient.ai-chat.index') }}" class="ai-new-chat-btn">
                    <i data-lucide="plus"></i>
                    جديد
                </a>
            </div>

            <div class="ai-conversation-list" data-ai-conversation-list>
                @forelse ($conversations as $conversation)
                    <div class="ai-conversation-row {{ optional($activeConversation)->id === $conversation->id ? 'is-active' : '' }}" data-conversation-row="{{ $conversation->id }}">
                        <a href="{{ route('patient.ai-chat.show', $conversation->id) }}" class="ai-conversation-link">
                            <span class="ai-conversation-icon">
                                <i data-lucide="message-circle"></i>
                            </span>

                            <span class="ai-conversation-copy">
                                <strong>{{ $conversation->title ?: 'محادثة جديدة' }}</strong>
                                <small>{{ optional($conversation->last_message_at)->diffForHumans() ?? 'الآن' }}</small>
                            </span>
                        </a>

                        <form action="{{ route('patient.ai-chat.destroy', $conversation->id) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذه المحادثة؟');">
                            @csrf
                            @method('DELETE')

                            <button type="submit" aria-label="حذف المحادثة">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="ai-empty-conversations" data-ai-empty-conversations>
                        <i data-lucide="messages-square"></i>
                        <strong>لا توجد محادثات بعد</strong>
                        <span>ابدأ أول محادثة مع مساعد اتزان الذكي.</span>
                    </div>
                @endforelse
            </div>
        </aside>

        <article class="ai-chat-card">
            <header class="ai-chat-header">
                <div class="ai-bot-avatar">
                    <i data-lucide="bot"></i>
                </div>

                <div class="ai-chat-title">
                    <span>مساعد اتزان الذكي</span>
                    <h1>{{ optional($activeConversation)->title ?: 'اسأل عن التغذية والعادات الصحية' }}</h1>
                    <p>مساعد توعوي لا يغني عن الطبيب، لكنه يساعدك على فهم وجباتك وعاداتك اليومية.</p>
                </div>

                <div class="ai-chat-status">
                    <span></span>
                    متاح الآن
                </div>
            </header>

            <div class="ai-safety-note">
                <i data-lucide="shield-alert"></i>
                <span>
                    لا تستخدم المساعد لتشخيص الأمراض أو تعديل الأدوية. عند وجود أعراض قوية أو حالة طبية، تواصل مع الطبيب.
                </span>
            </div>

            <div class="ai-chat-body" data-ai-chat-body>
                @if ($messages->isNotEmpty())
                    @foreach ($messages as $message)
                        <div class="ai-message {{ $message->role === 'user' ? 'is-user' : 'is-assistant' }}">
                            <div class="ai-message-bubble">
                                <div class="ai-message-meta">
                                    <strong>{{ $message->role === 'user' ? 'أنت' : 'مساعد اتزان' }}</strong>
                                    <small>{{ optional($message->created_at)->format('H:i') }}</small>
                                </div>

                                <p>{!! nl2br(e($message->content)) !!}</p>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="ai-welcome-state">
                        <div class="ai-welcome-icon">
                            <i data-lucide="sparkles"></i>
                        </div>

                        <div>
                            <span>ابدأ المحادثة</span>
                            <h2>كيف أقدر أساعدك اليوم؟</h2>
                            <p>
                                اسأل عن تنظيم وجباتك، فهم السعرات، زيادة البروتين، قراءة نتائج تحليل الوجبة،
                                أو بناء عادة صحية بسيطة.
                            </p>
                        </div>
                    </div>

                    <div class="ai-suggestions">
                        <button type="button" data-ai-suggestion="كيف أرتب وجباتي اليوم بطريقة صحية؟">
                            كيف أرتب وجباتي اليوم؟
                        </button>

                        <button type="button" data-ai-suggestion="كيف أزيد البروتين في أكلي بدون سعرات عالية؟">
                            كيف أزيد البروتين؟
                        </button>

                        <button type="button" data-ai-suggestion="اشرح لي معنى السعرات والبروتين والكارب والدهون.">
                            اشرح لي الماكروز
                        </button>
                    </div>
                @endif
            </div>

            <form class="ai-chat-composer" data-ai-chat-form>
                @csrf

                <input
                    type="hidden"
                    name="conversation_id"
                    value="{{ optional($activeConversation)->id }}"
                    data-ai-conversation-id
                >

                <div class="ai-input-wrap">
                    <textarea
                        name="message"
                        rows="1"
                        placeholder="اكتب سؤالك هنا..."
                        data-ai-chat-input
                    ></textarea>
                </div>

                <button type="submit" class="ai-send-btn" aria-label="إرسال">
                    <i data-lucide="send"></i>
                </button>
            </form>
        </article>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('front/js/ai-chat.js') }}"></script>
@endpush
