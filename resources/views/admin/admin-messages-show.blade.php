@extends('layouts.admin')

@section('title', 'محادثة مع {{ $conversation->user->name }} | اتزان')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/admin-messages.css') }}">
@endpush

@section('content')
<section class="chat-page">

    {{-- Chat Header --}}
    <header class="chat-head">
        <a href="{{ route('admin.messages.index') }}" class="chat-head__back">
            <i class="fa-solid fa-arrow-right"></i>
        </a>

        <div class="chat-head__avatar">
            @if ($conversation->user->profile_photo_path ?? null)
                <img src="{{ asset('storage/' . $conversation->user->profile_photo_path) }}" alt="{{ $conversation->user->name }}">
            @else
                <span>{{ mb_substr($conversation->user->name, 0, 1) }}</span>
            @endif
            <span class="chat-head__online-dot"></span>
        </div>

        <div class="chat-head__info">
            <strong>{{ $conversation->user->name }}</strong>
            <span>{{ $conversation->user->email }}</span>
        </div>

        <div class="chat-head__actions">
            {{-- تغيير الحالة --}}
            <div class="chat-status-wrap" id="chatStatusWrap">
                <button class="chat-head__btn" id="chatStatusToggle" type="button"
                        title="تغيير الحالة">
                    <i class="fa-regular fa-circle-dot"></i>
                    <span id="chatStatusLabel">
                        @if ($conversation->status === 'open') مفتوح
                        @elseif ($conversation->status === 'closed') مغلق
                        @else مؤرشف @endif
                    </span>
                </button>

                <div class="chat-status-popover" id="chatStatusPopover">
                    <button class="chat-status-option" data-status="open" type="button">
                        <i class="fa-regular fa-circle-dot"></i> مفتوح
                    </button>
                    <button class="chat-status-option" data-status="closed" type="button">
                        <i class="fa-regular fa-circle-check"></i> مغلق
                    </button>
                    <button class="chat-status-option" data-status="archived" type="button">
                        <i class="fa-regular fa-box-archive"></i> أرشيف
                    </button>
                </div>
            </div>
        </div>
    </header>

    {{-- Messages Area --}}
    <div class="chat-body" id="chatBody">

        @php
            $lastDate = null;
        @endphp

        @foreach ($messages as $msg)
            @php
                $msgDate = $msg->created_at->format('Y-m-d');
            @endphp

            {{-- Date divider --}}
            @if ($msgDate !== $lastDate)
                <div class="chat-date-divider">
                    <span>
                        @if ($msgDate === now()->format('Y-m-d'))
                            اليوم
                        @elseif ($msgDate === now()->subDay()->format('Y-m-d'))
                            أمس
                        @else
                            {{ $msg->created_at->format('d M Y') }}
                        @endif
                    </span>
                </div>
                @php $lastDate = $msgDate; @endphp
            @endif

            {{-- Message Bubble --}}
            <div class="chat-msg {{ $msg->sender_type === 'admin' ? 'chat-msg--admin' : 'chat-msg--user' }}"
                 data-id="{{ $msg->id }}">

                @if ($msg->sender_type === 'user')
                    <div class="chat-msg__avatar">
                        @if ($conversation->user->profile_photo_path ?? null)
                            <img src="{{ asset('storage/' . $conversation->user->profile_photo_path) }}"
                                 alt="{{ $conversation->user->name }}">
                        @else
                            <span>{{ mb_substr($conversation->user->name, 0, 1) }}</span>
                        @endif
                    </div>
                @endif

                <div class="chat-msg__bubble">
                    <p>{{ $msg->body }}</p>
                    <div class="chat-msg__meta">
                        <span class="chat-msg__time">{{ $msg->created_at->format('H:i') }}</span>
                        @if ($msg->sender_type === 'admin')
                            <span class="chat-msg__status">
                                <i class="fa-solid fa-check{{ $msg->is_read ? '-double' : '' }}"></i>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

        @endforeach

        {{-- Anchor للـ scroll --}}
        <div id="chatBottom"></div>
    </div>

    {{-- Input Area --}}
    <div class="chat-input-area">
        <div class="chat-input-wrap" id="chatInputWrap">
            <textarea
                id="chatInput"
                placeholder="اكتب رسالتك هنا..."
                rows="1"
                maxlength="5000"
            ></textarea>

            <button class="chat-send-btn" id="chatSendBtn" type="button" title="إرسال">
                <i class="fa-solid fa-paper-plane-top"></i>
            </button>
        </div>
        <p class="chat-input-hint">اضغط Enter للإرسال، Shift+Enter لسطر جديد</p>
    </div>

</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const conversationId = {{ $conversation->id }};
    const csrfToken      = document.querySelector('meta[name="csrf-token"]').content;
    const chatBody       = document.getElementById('chatBody');
    const chatInput      = document.getElementById('chatInput');
    const sendBtn        = document.getElementById('chatSendBtn');
    const chatBottom     = document.getElementById('chatBottom');

    let lastMessageId = {{ $messages->last()?->id ?? 0 }};
    let isSending     = false;
    let pollTimer     = null;

    // =====================
    //  Scroll to bottom
    // =====================
    function scrollToBottom(smooth = true) {
        chatBottom.scrollIntoView({ behavior: smooth ? 'smooth' : 'instant' });
    }

    scrollToBottom(false); // عند التحميل - فوري

    // =====================
    //  Auto-resize textarea
    // =====================
    chatInput.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 140) + 'px';
    });

    // =====================
    //  Enter to send
    // =====================
    chatInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    sendBtn.addEventListener('click', sendMessage);

    // =====================
    //  إرسال رسالة
    // =====================
    async function sendMessage() {
        const body = chatInput.value.trim();
        if (!body || isSending) return;

        isSending = true;
        sendBtn.disabled = true;
        chatInput.disabled = true;

        // عرض فوري (Optimistic UI)
        const tempId = 'temp-' + Date.now();
        appendMessage({
            id:          tempId,
            body:        body,
            sender_type: 'admin',
            time:        new Date().toLocaleTimeString('ar', { hour: '2-digit', minute: '2-digit' }),
            is_sending:  true,
        });

        chatInput.value = '';
        chatInput.style.height = 'auto';
        scrollToBottom();

        try {
            const res = await fetch(`/admin/messages/${conversationId}/send`, {
                method: 'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     csrfToken,
                },
                body: JSON.stringify({ body }),
            });

            const data = await res.json();

            if (data.success) {
                // استبدل الرسالة المؤقتة بالحقيقية
                const tempEl = document.querySelector(`[data-id="${tempId}"]`);
                if (tempEl) {
                    tempEl.dataset.id = data.message.id;
                    tempEl.querySelector('.chat-msg__bubble p').textContent = data.message.body;
                    const statusEl = tempEl.querySelector('.chat-msg__status');
                    if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-check"></i>';
                    tempEl.classList.remove('is-sending');
                }

                lastMessageId = data.message.id;
            }
        } catch (err) {
            // علّم الرسالة كفاشلة
            const tempEl = document.querySelector(`[data-id="${tempId}"]`);
            if (tempEl) tempEl.classList.add('is-failed');
        } finally {
            isSending = false;
            sendBtn.disabled = false;
            chatInput.disabled = false;
            chatInput.focus();
        }
    }

    // =====================
    //  إضافة فقاعة رسالة
    // =====================
    function appendMessage(msg) {
        const isAdmin = msg.sender_type === 'admin';

        const div = document.createElement('div');
        div.className = `chat-msg ${isAdmin ? 'chat-msg--admin' : 'chat-msg--user'} ${msg.is_sending ? 'is-sending' : ''}`;
        div.dataset.id = msg.id;

        div.innerHTML = `
            ${!isAdmin ? `
                <div class="chat-msg__avatar">
                    <span>{{ mb_substr($conversation->user->name, 0, 1) }}</span>
                </div>
            ` : ''}
            <div class="chat-msg__bubble">
                <p>${escHtml(msg.body)}</p>
                <div class="chat-msg__meta">
                    <span class="chat-msg__time">${msg.time}</span>
                    ${isAdmin ? `<span class="chat-msg__status"><i class="fa-solid fa-clock"></i></span>` : ''}
                </div>
            </div>
        `;

        // أدرجه قبل chatBottom
        chatBody.insertBefore(div, chatBottom);
    }

    // =====================
    //  Polling للرسائل الجديدة كل 5 ثوان
    // =====================
    async function pollMessages() {
        try {
            const res  = await fetch(
                `/admin/messages/${conversationId}/poll?last_id=${lastMessageId}`,
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
            );
            const data = await res.json();

            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    // لا تضيف رسائل الأدمن (هي موجودة)
                    if (msg.sender_type === 'user') {
                        appendMessage(msg);
                        scrollToBottom();
                    }
                    lastMessageId = Math.max(lastMessageId, msg.id);
                });
            }
        } catch (_) { /* صامت */ }
    }

    pollTimer = setInterval(pollMessages, 5000);

    // =====================
    //  تغيير حالة المحادثة
    // =====================
    const statusToggle  = document.getElementById('chatStatusToggle');
    const statusPopover = document.getElementById('chatStatusPopover');
    const statusLabel   = document.getElementById('chatStatusLabel');

    if (statusToggle && statusPopover) {
        statusToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            statusPopover.classList.toggle('is-open');
        });

        document.addEventListener('click', function () {
            statusPopover.classList.remove('is-open');
        });

        statusPopover.querySelectorAll('.chat-status-option').forEach(btn => {
            btn.addEventListener('click', async function (e) {
                e.stopPropagation();
                const status = this.dataset.status;
                const labels = { open: 'مفتوح', closed: 'مغلق', archived: 'أرشيف' };

                try {
                    await fetch(`/admin/messages/${conversationId}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type':     'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN':     csrfToken,
                        },
                        body: JSON.stringify({ status }),
                    });

                    statusLabel.textContent = labels[status] || status;
                    statusPopover.classList.remove('is-open');
                } catch (_) { /* صامت */ }
            });
        });
    }

    // =====================
    //  Helper: escape HTML
    // =====================
    function escHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str || ''));
        return div.innerHTML;
    }

    // Cleanup
    window.addEventListener('beforeunload', () => clearInterval(pollTimer));
});
</script>
@endpush
