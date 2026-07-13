{{--
    =====================================================
     COMPONENT: Admin Notifications Bell
     الملف: resources/views/components/admin/notifications-bell.blade.php

     أو يمكنك نسخ هذا الكود مباشرة داخل الـ navbar في layouts/admin.blade.php
    =====================================================
--}}

<div class="admin-notif-wrap" id="adminNotifWrap">

    {{-- زر الجرس --}}
    <button
        class="admin-notif-btn"
        id="adminNotifToggle"
        aria-label="الإشعارات"
        type="button"
    >
        <i class="fa-regular fa-bell"></i>
        <span class="admin-notif-badge" id="adminNotifBadge" style="display:none;">0</span>
    </button>

    {{-- Dropdown الإشعارات --}}
    <div class="admin-notif-dropdown" id="adminNotifDropdown">

        {{-- Header --}}
        <div class="admin-notif-dropdown__head">
            <div class="admin-notif-dropdown__title">
                <i class="fa-regular fa-bell"></i>
                <span>الإشعارات</span>
                <span class="admin-notif-dropdown__count" id="adminNotifCount">0</span>
            </div>
            <button
                class="admin-notif-dropdown__read-all"
                id="adminNotifReadAll"
                type="button"
            >
                تحديد الكل كمقروء
            </button>
        </div>

        {{-- قائمة الإشعارات --}}
        <div class="admin-notif-list" id="adminNotifList">
            <div class="admin-notif-loading" id="adminNotifLoading">
                <div class="admin-notif-spinner"></div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="admin-notif-dropdown__foot">
            <a href="{{ route('admin.notifications.index') }}" class="admin-notif-dropdown__view-all">
                عرض كل الإشعارات
                <i class="fa-solid fa-arrow-left"></i>
            </a>
        </div>
    </div>
</div>

{{-- =====================================================
     CSS الإشعارات - أضفه في ملف admin-notifications.css
     أو inline في الـ layout
===================================================== --}}
<style>
/* ==============================
   NOTIFICATIONS BELL & DROPDOWN
============================== */

.admin-notif-wrap {
    position: relative;
    z-index: 1000;
}

.admin-notif-btn {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(160deg, rgba(255,255,255,0.07) 0%, rgba(255,255,255,0.03) 100%);
    border: 1px solid var(--border);
    color: var(--text-2);
    font-size: 17px;
    position: relative;
    transition: .2s ease;
    cursor: pointer;
}

.admin-notif-btn:hover,
.admin-notif-btn.is-active {
    color: var(--teal);
    border-color: rgba(62, 207, 191, 0.28);
    background: rgba(62, 207, 191, 0.07);
}

/* Badge العداد */
.admin-notif-badge {
    position: absolute;
    top: -6px;
    left: -6px;
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    border-radius: 999px;
    background: linear-gradient(135deg, #f87171, #ef4444);
    color: #fff;
    font-size: 10px;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--bg, #0d1117);
    animation: notif-pulse 2s infinite;
}

@keyframes notif-pulse {
    0%, 100% { transform: scale(1); }
    50%       { transform: scale(1.1); }
}

/* Dropdown */
.admin-notif-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    right: auto;
    width: 360px;
    max-width: calc(100vw - 24px);
    border-radius: 22px;
    background: linear-gradient(160deg, rgba(18,26,38,0.98) 0%, rgba(12,20,30,0.98) 100%);
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow: 0 20px 50px rgba(0,0,0,0.35), 0 0 0 1px rgba(62,207,191,0.06);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    opacity: 0;
    visibility: hidden;
    transform: translateY(8px) scale(0.97);
    transform-origin: top left;
    transition: .22s cubic-bezier(.4,0,.2,1);
    overflow: hidden;
}

.admin-notif-dropdown.is-open {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}

.admin-notif-dropdown__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 18px 12px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.admin-notif-dropdown__title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text, #e2eaf3);
    font-size: 14px;
    font-weight: 800;
}

.admin-notif-dropdown__title i {
    color: var(--teal, #3ecfbf);
    font-size: 15px;
}

.admin-notif-dropdown__count {
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 999px;
    background: rgba(62,207,191,0.15);
    color: var(--teal, #3ecfbf);
    font-size: 11px;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
}

.admin-notif-dropdown__read-all {
    font-size: 11px;
    font-weight: 700;
    color: var(--teal, #3ecfbf);
    background: none;
    border: 0;
    cursor: pointer;
    opacity: .8;
    transition: opacity .2s;
}

.admin-notif-dropdown__read-all:hover { opacity: 1; }

/* قائمة الإشعارات */
.admin-notif-list {
    max-height: 360px;
    overflow-y: auto;
    padding: 8px;
    display: grid;
    gap: 4px;
}

.admin-notif-list::-webkit-scrollbar { width: 4px; }
.admin-notif-list::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.08);
    border-radius: 999px;
}

/* عنصر إشعار واحد */
.admin-notif-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 12px;
    align-items: flex-start;
    padding: 12px;
    border-radius: 14px;
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: .18s ease;
}

.admin-notif-item:hover {
    background: rgba(255,255,255,0.04);
    border-color: rgba(255,255,255,0.06);
}

.admin-notif-item.is-unread {
    background: rgba(62,207,191,0.04);
    border-color: rgba(62,207,191,0.10);
}

.admin-notif-item.is-unread:hover {
    background: rgba(62,207,191,0.07);
}

/* أيقونة الإشعار */
.admin-notif-item__icon {
    width: 40px;
    height: 40px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.admin-notif-item__icon--teal  { background: rgba(62,207,191,0.12); color: #3ecfbf; border: 1px solid rgba(62,207,191,0.18); }
.admin-notif-item__icon--blue  { background: rgba(96,165,250,0.12);  color: #60a5fa; border: 1px solid rgba(96,165,250,0.18); }
.admin-notif-item__icon--green { background: rgba(74,222,128,0.12);  color: #4ade80; border: 1px solid rgba(74,222,128,0.18); }
.admin-notif-item__icon--amber { background: rgba(245,166,35,0.12);  color: #f5a623; border: 1px solid rgba(245,166,35,0.18); }
.admin-notif-item__icon--red   { background: rgba(248,113,113,0.12); color: #f87171; border: 1px solid rgba(248,113,113,0.18); }

/* نص الإشعار */
.admin-notif-item__body {
    min-width: 0;
    display: grid;
    gap: 3px;
}

.admin-notif-item__title {
    color: var(--text, #e2eaf3);
    font-size: 13px;
    font-weight: 700;
    line-height: 1.4;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.admin-notif-item__desc {
    color: var(--text-2, #7a94aa);
    font-size: 12px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.admin-notif-item__time {
    color: var(--text-3, #4d6070);
    font-size: 11px;
    margin-top: 2px;
}

/* نقطة غير مقروء */
.admin-notif-item__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--teal, #3ecfbf);
    flex-shrink: 0;
    margin-top: 4px;
}

/* Loading spinner */
.admin-notif-loading {
    padding: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.admin-notif-spinner {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid rgba(62,207,191,0.15);
    border-top-color: var(--teal, #3ecfbf);
    animation: spin .7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Empty state */
.admin-notif-empty {
    padding: 28px 16px;
    text-align: center;
    display: grid;
    gap: 8px;
}

.admin-notif-empty i {
    font-size: 32px;
    color: var(--text-3, #4d6070);
}

.admin-notif-empty p {
    margin: 0;
    color: var(--text-3, #4d6070);
    font-size: 13px;
}

/* Footer */
.admin-notif-dropdown__foot {
    padding: 10px 18px 14px;
    border-top: 1px solid rgba(255,255,255,0.06);
}

.admin-notif-dropdown__view-all {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--teal, #3ecfbf);
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    opacity: .85;
    transition: opacity .2s;
}

.admin-notif-dropdown__view-all:hover { opacity: 1; }

/* ======== LIGHT MODE ======== */
body.admin-body[data-theme="light"] .admin-notif-btn {
    background: rgba(255,255,255,0.85);
    border-color: rgba(24,52,76,0.10);
    color: #5f7387;
}

body.admin-body[data-theme="light"] .admin-notif-badge {
    border-color: #f0f4f8;
}

body.admin-body[data-theme="light"] .admin-notif-dropdown {
    background: #ffffff;
    border-color: rgba(24,52,76,0.08);
    box-shadow: 0 20px 50px rgba(27,58,84,0.12);
}

body.admin-body[data-theme="light"] .admin-notif-dropdown__head,
body.admin-body[data-theme="light"] .admin-notif-dropdown__foot {
    border-color: rgba(24,52,76,0.07);
}

body.admin-body[data-theme="light"] .admin-notif-dropdown__title {
    color: #18344c;
}

body.admin-body[data-theme="light"] .admin-notif-item__title {
    color: #18344c;
}

body.admin-body[data-theme="light"] .admin-notif-item__desc {
    color: #72879a;
}

body.admin-body[data-theme="light"] .admin-notif-item__time {
    color: #96a8b8;
}

body.admin-body[data-theme="light"] .admin-notif-item:hover {
    background: rgba(24,52,76,0.03);
    border-color: rgba(24,52,76,0.07);
}

body.admin-body[data-theme="light"] .admin-notif-item.is-unread {
    background: rgba(62,207,191,0.05);
    border-color: rgba(62,207,191,0.12);
}

body.admin-body[data-theme="light"] .admin-notif-list::-webkit-scrollbar-thumb {
    background: rgba(24,52,76,0.10);
}

body.admin-body[data-theme="light"] .admin-notif-empty i,
body.admin-body[data-theme="light"] .admin-notif-empty p {
    color: #96a8b8;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-notif-dropdown {
        width: calc(100vw - 24px);
        left: auto;
        right: 0;
    }
}
</style>

{{-- =====================================================
     JavaScript الإشعارات
     أضفه في نهاية الـ layout قبل </body>
===================================================== --}}
<script>
(function () {
    const toggle      = document.getElementById('adminNotifToggle');
    const dropdown    = document.getElementById('adminNotifDropdown');
    const badge       = document.getElementById('adminNotifBadge');
    const countEl     = document.getElementById('adminNotifCount');
    const list        = document.getElementById('adminNotifList');
    const loadingEl   = document.getElementById('adminNotifLoading');
    const readAllBtn  = document.getElementById('adminNotifReadAll');

    let isLoaded      = false;
    let pollInterval  = null;

    // =====================
    //  فتح / إغلاق الـ Dropdown
    // =====================
    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle('is-open');
        toggle.classList.toggle('is-active', isOpen);

        if (isOpen && !isLoaded) {
            loadNotifications();
            isLoaded = true;
        }
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && !toggle.contains(e.target)) {
            dropdown.classList.remove('is-open');
            toggle.classList.remove('is-active');
        }
    });

    // =====================
    //  جلب الإشعارات من الـ API
    // =====================
    async function loadNotifications() {
        try {
            const res  = await fetch('{{ route('admin.notifications.dropdown') }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();

            renderNotifications(data.notifications);
            updateBadge(data.unread_count);
        } catch (err) {
            list.innerHTML = `
                <div class="admin-notif-empty">
                    <i class="fa-regular fa-wifi-slash"></i>
                    <p>تعذّر تحميل الإشعارات</p>
                </div>`;
        }
    }

    // =====================
    //  رسم الإشعارات
    // =====================
    function renderNotifications(notifications) {
        if (!notifications || notifications.length === 0) {
            list.innerHTML = `
                <div class="admin-notif-empty">
                    <i class="fa-regular fa-bell-slash"></i>
                    <p>لا توجد إشعارات حتى الآن</p>
                </div>`;
            return;
        }

        list.innerHTML = notifications.map(n => `
            <a
                href="${n.url || '#'}"
                class="admin-notif-item ${!n.is_read ? 'is-unread' : ''}"
                data-id="${n.id}"
                onclick="markRead(${n.id}, event)"
            >
                <div class="admin-notif-item__icon admin-notif-item__icon--${n.color}">
                    <i class="fa-solid ${n.icon}"></i>
                </div>

                <div class="admin-notif-item__body">
                    <div class="admin-notif-item__title">${escHtml(n.title)}</div>
                    ${n.body ? `<div class="admin-notif-item__desc">${escHtml(n.body)}</div>` : ''}
                    <div class="admin-notif-item__time">${n.time_ago}</div>
                </div>

                ${!n.is_read ? '<div class="admin-notif-item__dot"></div>' : '<div></div>'}
            </a>
        `).join('');
    }

    // =====================
    //  تحديث الـ Badge
    // =====================
    function updateBadge(count) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
        if (countEl) countEl.textContent = count;
    }

    // =====================
    //  Polling كل 30 ثانية للـ Badge فقط
    // =====================
    async function pollUnreadCount() {
        try {
            const res  = await fetch('{{ route('admin.notifications.unread-count') }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            updateBadge(data.count);
        } catch (_) { /* صامت */ }
    }

    // شغّل الـ polling
    pollUnreadCount();
    pollInterval = setInterval(pollUnreadCount, 30000);

    // =====================
    //  علّم مقروء عند الضغط
    // =====================
    window.markRead = async function (id, e) {
        const item = document.querySelector(`.admin-notif-item[data-id="${id}"]`);
        if (!item || !item.classList.contains('is-unread')) return;

        try {
            const res = await fetch(`/admin/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            });
            const data = await res.json();

            item.classList.remove('is-unread');
            const dot = item.querySelector('.admin-notif-item__dot');
            if (dot) dot.remove();

            updateBadge(data.unread_count);
        } catch (_) { /* صامت */ }
    };

    // =====================
    //  تحديد الكل كمقروء
    // =====================
    readAllBtn.addEventListener('click', async function () {
        try {
            await fetch('{{ route('admin.notifications.mark-all-read') }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            });

            document.querySelectorAll('.admin-notif-item.is-unread').forEach(item => {
                item.classList.remove('is-unread');
                const dot = item.querySelector('.admin-notif-item__dot');
                if (dot) dot.remove();
            });

            updateBadge(0);
        } catch (_) { /* صامت */ }
    });

    // =====================
    //  Helper: escape HTML
    // =====================
    function escHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str || ''));
        return div.innerHTML;
    }

})();
</script>
