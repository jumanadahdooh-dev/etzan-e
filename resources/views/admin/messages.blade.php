@extends('layouts.admin')

@section('title', 'الرسائل')

@push('styles')
<link rel="stylesheet" href="{{ asset('front/css/admin/admin-messages.css') }}">

<style>
/* =========================================================
   MSG_FORCE_FIX
   تعديل نهائي فقط لحجم بوكس الرسالة وبادجات الإيميل
   بدون تغيير عرض الصفحة
========================================================= */

/* لا نلمس عرض الصفحة، فقط نرتب الرسائل */
.msg-page-v2 .msg-chat-body-v2 {
    display: block !important;
    align-content: flex-start !important;
    justify-content: flex-start !important;
}

/* صف الرسالة */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2 {
    width: 100% !important;
    min-height: 0 !important;
    height: auto !important;
    display: flex !important;
    align-items: flex-start !important;
    margin: 0 0 12px 0 !important;
    padding: 0 !important;
}

/* رسالة الزائر / المستخدم */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2--user {
    justify-content: flex-start !important;
}

/* رسالة الأدمن */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2--admin {
    justify-content: flex-end !important;
}

/* أهم جزء: بوكس الرسالة على قد الكلام */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2 > .msg-bubble-content-v2 {
    width: auto !important;
    inline-size: fit-content !important;

    min-width: 0 !important;
    min-inline-size: 0 !important;

    max-width: 360px !important;
    max-inline-size: 360px !important;

    height: auto !important;
    min-height: 0 !important;
    max-height: none !important;

    padding: 10px 14px !important;
    margin: 0 !important;

    display: inline-flex !important;
    flex-direction: column !important;
    justify-content: flex-start !important;
    align-items: flex-start !important;

    border-radius: 18px !important;
    line-height: 1.7 !important;
    font-size: 13.5px !important;
    font-weight: 800 !important;

    white-space: pre-wrap !important;
    word-break: break-word !important;
    overflow-wrap: anywhere !important;

    box-sizing: border-box !important;
}

/* النص لا يمدد البوكس */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-content-v2 p {
    margin: 0 !important;
    padding: 0 !important;

    width: auto !important;
    min-width: 0 !important;
    max-width: 100% !important;

    height: auto !important;
    min-height: 0 !important;
    max-height: none !important;

    display: block !important;
    line-height: 1.7 !important;
    white-space: pre-wrap !important;
    word-break: break-word !important;
    overflow-wrap: anywhere !important;
}

/* الوقت تحت النص مباشرة، مش آخر كرت كبير */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-content-v2 span {
    display: block !important;
    margin: 6px 0 0 !important;
    padding: 0 !important;

    width: auto !important;
    min-width: 0 !important;

    height: auto !important;
    min-height: 0 !important;

    font-size: 10px !important;
    line-height: 1.2 !important;
    opacity: 0.65 !important;
}

/* شكل فقاعة الزائر */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2--user > .msg-bubble-content-v2 {
    background: var(--msg-card-solid, #ffffff) !important;
    color: var(--msg-text, #18333B) !important;
    border: 1px solid var(--msg-border, rgba(29, 158, 117, 0.15)) !important;
    border-bottom-right-radius: 7px !important;
    box-shadow: 0 8px 20px rgba(24, 51, 59, 0.06) !important;
}

/* شكل فقاعة الأدمن */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2--admin > .msg-bubble-content-v2 {
    background: linear-gradient(135deg, #1D9E75, #4DA8DA) !important;
    color: #ffffff !important;
    border-bottom-left-radius: 7px !important;
    box-shadow: 0 12px 24px rgba(29, 158, 117, 0.20) !important;
}

/* منع أي ستايل قديم يخلي البوكس كرت كبير */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2,
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-content-v2,
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-content-v2 * {
    box-sizing: border-box !important;
}

/* =========================================================
   إصلاح كروت قائمة المحادثات
========================================================= */

.msg-page-v2 .msg-conv-item-v2 {
    overflow: hidden !important;
}

.msg-page-v2 .msg-conv-body-v2 {
    min-width: 0 !important;
    overflow: hidden !important;
}

/* النصوص داخل كرت المحادثة ما تطلع برا */
.msg-page-v2 .msg-conv-top-v2 strong,
.msg-page-v2 .msg-conv-preview-v2 span {
    min-width: 0 !important;
    max-width: 100% !important;
    overflow: hidden !important;
    white-space: nowrap !important;
    text-overflow: ellipsis !important;
}

/* الإيميل داخل البوكس وما يطلع برا */
.msg-page-v2 .msg-conv-meta-v2 {
    width: 100% !important;
    min-width: 0 !important;
    overflow: hidden !important;
    display: flex !important;
    align-items: center !important;
    gap: 5px !important;
    flex-wrap: nowrap !important;
}

.msg-page-v2 .msg-conv-meta-v2 em {
    min-width: 0 !important;
    flex: 0 1 auto !important;
}

.msg-page-v2 .msg-conv-meta-v2 em.is-email {
    max-width: 125px !important;
    overflow: hidden !important;
    white-space: nowrap !important;
    text-overflow: ellipsis !important;
    display: inline-block !important;
    vertical-align: middle !important;
}

/* البادج تبع زائر صغير وما يدفع الإيميل برا */
.msg-page-v2 .msg-conv-meta-v2 em.is-guest,
.msg-page-v2 .msg-conv-meta-v2 em.is-user {
    flex: 0 0 auto !important;
}

/* لو النص طويل جدًا في الرسائل */
@media (max-width: 1200px) {
    .msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2 > .msg-bubble-content-v2 {
        max-width: 330px !important;
        max-inline-size: 330px !important;
    }

    .msg-page-v2 .msg-conv-meta-v2 em.is-email {
        max-width: 105px !important;
    }
}

@media (max-width: 768px) {
    .msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2 > .msg-bubble-content-v2 {
        max-width: 85% !important;
        max-inline-size: 85% !important;
    }

    .msg-page-v2 .msg-conv-meta-v2 {
        flex-wrap: wrap !important;
    }

    .msg-page-v2 .msg-conv-meta-v2 em.is-email {
        max-width: 160px !important;
    }
}
/* =========================================================
   Final Bubble Polish
   تصغير بوكس الرسالة + تحريك الوقت لليسار
========================================================= */

.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2 > .msg-bubble-content-v2 {
    width: fit-content !important;
    max-width: 340px !important;

    padding: 10px 16px 9px !important;
    border-radius: 17px !important;

    min-height: 0 !important;
    height: auto !important;

    display: inline-block !important;
}

/* نص الرسالة */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-content-v2 p {
    margin: 0 !important;
    padding: 0 !important;

    line-height: 1.65 !important;
    font-size: 13.5px !important;
    font-weight: 850 !important;

    text-align: right !important;
}

/* وقت الرسالة يكون يسار */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-content-v2 span {
    display: block !important;

    width: 100% !important;
    margin-top: 9px !important;

    text-align: left !important;
    direction: ltr !important;

    font-size: 10px !important;
    line-height: 1 !important;
    font-weight: 850 !important;
    opacity: 0.65 !important;
}

/* تصغير الارتفاع الزائد */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2 {
    margin-bottom: 10px !important;
    min-height: 0 !important;
}

/* للرسائل الطويلة فقط */
@media (max-width: 1200px) {
    .msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2 > .msg-bubble-content-v2 {
        max-width: 320px !important;
    }
}

/* =========================================================
   HARD RESET FOR MESSAGE BUBBLES
   يكسر أي ستايل قديم عامل البوكس كبير
========================================================= */

/* صف الرسالة */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2 {
    all: unset !important;
    box-sizing: border-box !important;

    width: 100% !important;
    display: flex !important;
    margin-bottom: 12px !important;
}

/* رسالة الزائر */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2--user {
    justify-content: flex-start !important;
}

/* رد الأدمن */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2--admin {
    justify-content: flex-end !important;
}

/* البوكس نفسه: على قد الكلام فقط */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2 > .msg-bubble-content-v2 {
    all: unset !important;
    box-sizing: border-box !important;

    display: inline-flex !important;
    flex-direction: column !important;
    align-items: stretch !important;

    width: auto !important;
    min-width: 0 !important;
    max-width: 360px !important;

    height: auto !important;
    min-height: 0 !important;
    max-height: none !important;

    padding: 10px 14px !important;
    margin: 0 !important;

    border-radius: 18px !important;
    line-height: 1.7 !important;
    font-size: 13.5px !important;
    font-weight: 850 !important;

    white-space: pre-wrap !important;
    word-break: break-word !important;
    overflow-wrap: anywhere !important;
}

/* نص الرسالة */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-content-v2 p {
    all: unset !important;
    box-sizing: border-box !important;

    display: block !important;
    width: auto !important;
    min-width: 0 !important;
    max-width: 100% !important;

    height: auto !important;
    min-height: 0 !important;

    margin: 0 !important;
    padding: 0 !important;

    color: inherit !important;
    line-height: 1.7 !important;
    font-size: 13.5px !important;
    font-weight: 850 !important;
    text-align: right !important;

    white-space: pre-wrap !important;
    word-break: break-word !important;
    overflow-wrap: anywhere !important;
}

/* الوقت: تحت ويسار */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-content-v2 span {
    all: unset !important;
    box-sizing: border-box !important;

    display: block !important;
    width: 100% !important;

    margin-top: 7px !important;
    padding: 0 !important;

    direction: ltr !important;
    text-align: left !important;

    color: inherit !important;
    opacity: 0.62 !important;

    font-size: 10px !important;
    line-height: 1.2 !important;
    font-weight: 800 !important;
}

/* شكل رسالة الزائر */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2--user > .msg-bubble-content-v2 {
    background: rgba(255, 255, 255, 0.06) !important;
    color: #EAF7F4 !important;
    border: 1px solid rgba(29, 158, 117, 0.28) !important;
    border-bottom-right-radius: 7px !important;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.10) !important;
}

/* شكل رد الأدمن */
.msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2--admin > .msg-bubble-content-v2 {
    background: linear-gradient(135deg, #1D9E75, #4DA8DA) !important;
    color: #ffffff !important;
    border: none !important;
    border-bottom-left-radius: 7px !important;
    box-shadow: 0 12px 24px rgba(29, 158, 117, 0.22) !important;
}

/* لو النص طويل */
@media (max-width: 1200px) {
    .msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2 > .msg-bubble-content-v2 {
        max-width: 330px !important;
    }
}

@media (max-width: 768px) {
    .msg-page-v2 .msg-chat-body-v2 .msg-bubble-v2 > .msg-bubble-content-v2 {
        max-width: 85% !important;
    }
}
</style>
@endpush

@section('content')

<div class="msg-page-v2">

    {{-- Conversations Sidebar --}}
    <aside class="msg-sidebar-v2">
        <div class="msg-sidebar-head-v2">
            <span class="msg-kicker-v2">
                <i class="fa-regular fa-comments"></i>
                مركز الرسائل
            </span>

            <h1>المحادثات</h1>
            <p>تابع رسائل الزوار والمستخدمين ورد عليهم من لوحة الإدارة.</p>
        </div>

        <div class="msg-conversations-v2">
            @forelse($conversations as $conv)
                @php
                    $isActive = isset($conversation) && $conversation && $conversation->id === $conv->id;

                    $senderName = $conv->user?->name
                        ?? $conv->guest_name
                        ?? 'زائر';

                    $senderEmail = $conv->user?->email
                        ?? $conv->guest_email
                        ?? null;

                    $senderType = $conv->user_id ? 'مستخدم' : 'زائر';

                    $latest = $conv->latestMessage;

                    $latestText = $latest?->body
                        ?? $latest?->message
                        ?? $latest?->content
                        ?? $latest?->text
                        ?? 'لا توجد رسائل بعد';

                    $latestTime = $latest?->created_at
                        ? $latest->created_at->format('H:i')
                        : null;
                @endphp

                <a
                    href="{{ route('admin.messages.show', $conv) }}"
                    class="msg-conv-item-v2 {{ $isActive ? 'is-active' : '' }} {{ ($conv->unread_by_admin ?? 0) > 0 ? 'has-unread' : '' }}"
                >
                    <div class="msg-conv-avatar-v2">
                        {{ mb_substr($senderName, 0, 1, 'UTF-8') }}

                        @if($conv->status === 'open')
                            <span class="msg-conv-online-v2"></span>
                        @endif
                    </div>

                    <div class="msg-conv-body-v2">
                        <div class="msg-conv-top-v2">
                            <strong>{{ $senderName }}</strong>

                            @if($latestTime)
                                <small>{{ $latestTime }}</small>
                            @endif
                        </div>

                        <div class="msg-conv-preview-v2">
                            <span>{{ \Illuminate\Support\Str::limit($latestText, 48) }}</span>

                            @if(($conv->unread_by_admin ?? 0) > 0)
                                <b>{{ $conv->unread_by_admin }}</b>
                            @endif
                        </div>

                        <div class="msg-conv-meta-v2">
                            <em class="{{ $conv->user_id ? 'is-user' : 'is-guest' }}">
                                {{ $senderType }}
                            </em>

                            @if($senderEmail)
                                <em class="is-email">
                                    {{ \Illuminate\Support\Str::limit($senderEmail, 26) }}
                                </em>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="msg-conv-empty-v2">
                    <div>
                        <i class="fa-regular fa-message"></i>
                    </div>

                    <h3>لا توجد محادثات</h3>
                    <p>عند وصول رسالة جديدة ستظهر هنا.</p>
                </div>
            @endforelse
        </div>
    </aside>

    {{-- Chat Area --}}
    <main class="msg-chat-v2">
        @if($conversation)
            @php
                $activeName = $conversation->user?->name
                    ?? $conversation->guest_name
                    ?? 'زائر';

                $activeEmail = $conversation->user?->email
                    ?? $conversation->guest_email
                    ?? null;

                $activeType = $conversation->user_id ? 'مستخدم مسجل' : 'زائر';

                $activeSubject = $conversation->subject ?? null;
            @endphp

            <header class="msg-chat-head-v2">
                <div class="msg-chat-user-v2">
                    <div class="msg-chat-avatar-v2">
                        {{ mb_substr($activeName, 0, 1, 'UTF-8') }}
                    </div>

                    <div>
                        <span>محادثة مع</span>
                        <strong>{{ $activeName }}</strong>

                        <small>
                            @if($activeEmail)
                                {{ $activeEmail }}
                            @else
                                لا يوجد بريد إلكتروني
                            @endif

                            @if($activeSubject)
                                <br>
                                الموضوع: {{ $activeSubject }}
                            @endif
                        </small>
                    </div>
                </div>

                <div class="msg-chat-head-actions-v2">
                    <span class="msg-type-pill-v2 {{ $conversation->user_id ? 'is-user' : 'is-guest' }}">
                        {{ $activeType }}
                    </span>

                    <form method="POST" action="{{ route('admin.messages.status', $conversation) }}">
                        @csrf
                        @method('PATCH')

                        <input
                            type="hidden"
                            name="status"
                            value="{{ $conversation->status === 'open' ? 'closed' : 'open' }}"
                        >

                        <button
                            type="submit"
                            class="msg-status-btn-v2 {{ $conversation->status === 'open' ? 'is-open' : 'is-closed' }}"
                        >
                            @if($conversation->status === 'open')
                                <i class="fa-solid fa-lock-open"></i>
                                مفتوحة
                            @else
                                <i class="fa-solid fa-lock"></i>
                                مغلقة
                            @endif
                        </button>
                    </form>
                </div>
            </header>

            <section class="msg-chat-body-v2" id="adminMessagesBox">
                @forelse($messages as $message)
                    @php
                        $isAdminMessage =
                            ($message->sender_type ?? null) === 'admin'
                            || ($message->sender_role ?? null) === 'admin'
                            || ($message->direction ?? null) === 'outgoing';

                        $messageText = $message->body
                            ?? $message->message
                            ?? $message->content
                            ?? $message->text
                            ?? '';
                    @endphp

                    <div class="msg-bubble-v2 {{ $isAdminMessage ? 'msg-bubble-v2--admin' : 'msg-bubble-v2--user' }}">
                        <div class="msg-bubble-content-v2">
                            <p>{{ $messageText }}</p>

                            <span>
                                {{ optional($message->created_at)->format('Y-m-d H:i') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="msg-chat-empty-v2">
                        <div class="msg-chat-empty-watermark-v2">اتزان</div>

                        <div class="msg-chat-empty-icon-v2">
                            <i class="fa-regular fa-comments"></i>
                        </div>

                        <h2>لا توجد رسائل بعد</h2>
                        <p>عند وصول رسالة داخل هذه المحادثة ستظهر هنا.</p>
                    </div>
                @endforelse
            </section>

            <footer class="msg-chat-footer-v2">
                <form
                    method="POST"
                    action="{{ route('admin.messages.send', $conversation) }}"
                    class="msg-input-form-v2"
                >
                    @csrf

                    <div class="msg-input-wrap-v2">
                        <textarea
                            name="message"
                            rows="1"
                            placeholder="اكتب ردك هنا..."
                            required
                        ></textarea>
                    </div>

                    <button type="submit" class="msg-send-btn-v2" aria-label="إرسال الرد">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>

                <div class="msg-reply-note-v2">
                    @if(!$conversation->user_id && $conversation->guest_email)
                        سيتم إرسال الرد تلقائيًا إلى بريد الزائر:
                        <strong>{{ $conversation->guest_email }}</strong>
                    @elseif($conversation->user_id)
                        هذه محادثة مستخدم مسجل، وسيظهر الرد داخل رسائله في النظام.
                    @else
                        لا يوجد بريد إلكتروني محفوظ لهذه المحادثة.
                    @endif
                </div>
            </footer>
        @else
            <section class="msg-chat-empty-v2">
                <div class="msg-chat-empty-watermark-v2">اتزان</div>

                <div class="msg-chat-empty-icon-v2">
                    <i class="fa-regular fa-comments"></i>
                </div>

                <h2>اختاري محادثة</h2>
                <p>اختاري محادثة من القائمة لعرض الرسائل والرد عليها.</p>
            </section>
        @endif
    </main>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const box = document.getElementById('adminMessagesBox');

    if (box) {
        box.scrollTop = box.scrollHeight;
    }

    document.querySelectorAll('.msg-input-wrap-v2 textarea').forEach((textarea) => {
        const resize = () => {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 140) + 'px';
        };

        textarea.addEventListener('input', resize);
        resize();
    });
});
</script>
@endpush
