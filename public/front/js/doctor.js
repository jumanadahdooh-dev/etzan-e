// ============================================================================
// Etzan Doctor Module — Layout Interactions
// السايدبار/التوب بار/الوضع الغامق كلها بيديرها patient.js نفسه
// هاد الملف فيه بس الميزات الخاصة بالطبيب: الساعة الحية + نظام الإشعارات الحقيقي
//
// ملاحظة مهمة: استخدمنا أسماء data-* مختلفة تماماً عن patient.js
// (data-doctor-notif-* بدل data-notification-*) لأنه صفحات الطبيب بتحمّل
// patient.js كمان، وكان عندها دالة قديمة بتتحكم بنفس الأسماء وتتعارض مع
// الكود هون (كانت تفتح/تسكر القائمة قبل ما توصل بيانات الإشعارات الحقيقية).
// ============================================================================

document.addEventListener('DOMContentLoaded', function () {
    var clockEl = document.querySelector('[data-live-clock]');
    if (clockEl) {
        var updateClock = function () {
            clockEl.textContent = new Date().toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
        };
        updateClock();
        setInterval(updateClock, 30000);
    }

    var notifWrap = document.querySelector('[data-doctor-notif-wrap]');
    var notifToggle = document.querySelector('[data-doctor-notif-toggle]');
    var notifMenu = document.querySelector('[data-doctor-notif-menu]');
    var notifList = document.querySelector('[data-doctor-notif-list]');
    var notifDot = document.querySelector('[data-doctor-notif-dot]');
    var notifMarkAllBtn = document.querySelector('[data-doctor-notif-mark-all]');

    if (notifWrap && notifToggle && notifMenu) {
        var dropdownUrl = notifWrap.getAttribute('data-dropdown-url');
        var unreadUrl = notifWrap.getAttribute('data-unread-url');
        var markAllUrl = notifWrap.getAttribute('data-mark-all-url');
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        function refreshIcons() {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        }

        function loadDropdown() {
            if (!dropdownUrl || !notifList) return;

            fetch(dropdownUrl)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var items = data.notifications || [];

                    if (notifDot) {
                        notifDot.style.display = items.some(function (n) { return n.is_unread; }) ? '' : 'none';
                    }

                    if (items.length === 0) {
                        notifList.innerHTML = '<div class="ddash-empty" style="padding:24px 16px"><i data-lucide="bell-off"></i><p>ما في إشعارات جديدة.</p></div>';
                    } else {
                        notifList.innerHTML = items.map(function (n) {
                            return '<a href="' + n.url + '" class="topbar-notif__item">' +
                                '<span class="topbar-notif__icon"><i data-lucide="' + n.icon + '"></i></span>' +
                                '<div>' +
                                    '<strong>' + n.title + '</strong>' +
                                    (n.body ? '<small>' + n.body + '</small>' : '') +
                                    '<small class="topbar-notif__time">' + (n.time || '') + '</small>' +
                                '</div>' +
                            '</a>';
                        }).join('');
                    }

                    refreshIcons();
                })
                .catch(function () {});
        }

        function checkUnread() {
            if (!unreadUrl || !notifDot) return;

            fetch(unreadUrl)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var count = data.count || 0;
                    notifDot.style.display = count > 0 ? '' : 'none';
                    notifDot.textContent = count > 9 ? '9+' : (count > 0 ? String(count) : '');
                })
                .catch(function () {});
        }

        notifToggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var isOpen = notifMenu.classList.contains('is-open');
            if (isOpen) {
                notifMenu.classList.remove('is-open');
                notifToggle.setAttribute('aria-expanded', 'false');
            } else {
                notifMenu.classList.add('is-open');
                notifToggle.setAttribute('aria-expanded', 'true');
                loadDropdown();
            }
        });

        notifMenu.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        document.addEventListener('click', function () {
            notifMenu.classList.remove('is-open');
            notifToggle.setAttribute('aria-expanded', 'false');
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                notifMenu.classList.remove('is-open');
                notifToggle.setAttribute('aria-expanded', 'false');
            }
        });

        if (notifMarkAllBtn && markAllUrl) {
            notifMarkAllBtn.addEventListener('click', function () {
                fetch(markAllUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken || '',
                        'Accept': 'application/json'
                    }
                }).then(loadDropdown);
            });
        }

        checkUnread();
        setInterval(checkUnread, 30000);
    }
});