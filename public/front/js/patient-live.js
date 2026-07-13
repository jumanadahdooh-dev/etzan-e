/* ==================================================
   ETZAN PATIENT THEME CONTROLLER
   ملف واحد فقط مسؤول عن الدارك / اللايت
   ================================================== */

(function () {
    "use strict";

    if (window.__ETZAN_PATIENT_THEME_READY === true) {
        return;
    }

    window.__ETZAN_PATIENT_THEME_READY = true;

    const THEME_KEY = "etzan-theme";

    const LEGACY_KEYS = [
        "theme",
        "site-theme",
        "patient-theme",
        "app-theme",
        "etzan_patient_theme"
    ];

    const TOGGLE_SELECTOR = [
        "[data-theme-toggle]",
        "#themeToggle",
        ".theme-toggle",
        ".js-theme-toggle"
    ].join(",");

    function normalizeTheme(value) {
        return value === "dark" ? "dark" : "light";
    }

    function readTheme() {
        const current = localStorage.getItem(THEME_KEY);

        if (current === "dark" || current === "light") {
            return current;
        }

        for (const key of LEGACY_KEYS) {
            const value = localStorage.getItem(key);

            if (value === "dark" || value === "light") {
                return value;
            }
        }

        return "light";
    }

    function saveTheme(theme) {
        const finalTheme = normalizeTheme(theme);

        localStorage.setItem(THEME_KEY, finalTheme);

        LEGACY_KEYS.forEach(function (key) {
            localStorage.setItem(key, finalTheme);
        });

        return finalTheme;
    }

    function syncPageTheme(theme) {
        document.querySelectorAll(
            ".journey-board-page, .journey-page, .pc-page, .ps-page-premium, [data-sync-theme]"
        ).forEach(function (element) {
            element.setAttribute("data-theme", theme);
        });
    }

    function refreshIcons() {
        if (window.refreshEtzanIcons) {
            window.refreshEtzanIcons();
            return;
        }

        if (window.lucide && typeof window.lucide.createIcons === "function") {
            window.lucide.createIcons();
        }
    }

    function updateThemeButtons(theme) {
        document.querySelectorAll(TOGGLE_SELECTOR).forEach(function (button) {
            button.setAttribute("aria-pressed", theme === "dark" ? "true" : "false");
            button.setAttribute("data-current-theme", theme);

            const label = button.querySelector("[data-theme-label]");

            if (label) {
                label.textContent = theme === "dark" ? "الوضع الفاتح" : "الوضع الداكن";
            }

            const icon = button.querySelector("[data-lucide]");

            if (icon) {
                icon.setAttribute("data-lucide", theme === "dark" ? "sun" : "moon");
            }
        });

        refreshIcons();
    }

    function applyTheme(theme) {
        const finalTheme = saveTheme(theme);
        const isDark = finalTheme === "dark";

        document.documentElement.setAttribute("data-theme", finalTheme);
        document.documentElement.classList.toggle("dark", isDark);
        document.documentElement.classList.toggle("dark-mode", isDark);
        document.documentElement.classList.toggle("light", !isDark);
        document.documentElement.classList.toggle("light-mode", !isDark);

        if (document.body) {
            document.body.setAttribute("data-theme", finalTheme);
            document.body.classList.toggle("dark", isDark);
            document.body.classList.toggle("dark-mode", isDark);
            document.body.classList.toggle("light", !isDark);
            document.body.classList.toggle("light-mode", !isDark);
        }

        syncPageTheme(finalTheme);
        updateThemeButtons(finalTheme);
    }

    function toggleTheme() {
        const currentTheme = normalizeTheme(
            document.documentElement.getAttribute("data-theme") || readTheme()
        );

        applyTheme(currentTheme === "dark" ? "light" : "dark");
    }

    window.EtzanTheme = {
        get: readTheme,
        apply: applyTheme,
        toggle: toggleTheme
    };

    applyTheme(readTheme());

    document.addEventListener("DOMContentLoaded", function () {
        applyTheme(readTheme());
    });

    document.addEventListener("click", function (event) {
        const button = event.target.closest(TOGGLE_SELECTOR);

        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        toggleTheme();
    }, true);

    window.addEventListener("storage", function () {
        applyTheme(readTheme());
    });
})();

(function () {
    "use strict";

    const POLL_INTERVAL = 15000;

    function refreshIcons() {
        if (window.refreshEtzanIcons) {
            window.refreshEtzanIcons();
            return;
        }

        if (window.lucide && typeof window.lucide.createIcons === "function") {
            window.lucide.createIcons();
        }
    }

    function escapeHTML(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function getNotificationIcon(type) {
        const icons = {
            appointment_pending: "clock-3",
            appointment_confirmed: "badge-check",
            appointment_rejected: "circle-alert",
            appointment_reschedule_requested: "calendar-clock",
            appointment_reminder_60: "alarm-clock",
            appointment_reminder_10: "timer",
            appointment_starting_now: "radio",

            doctor_request_pending: "stethoscope",
            doctor_request_approved: "badge-check",
            doctor_request_rejected: "circle-alert",

            message_received: "message-circle",
            support_reply: "life-buoy",
            support_message_sent: "life-buoy",

            task_reminder: "list-checks",
            task_due: "alarm-clock",
            task_late: "circle-alert",

            general: "bell-ring",
            system: "bell-ring"
        };

        return icons[type] || "bell-ring";
    }

    function normalizeCount(value) {
        const number = Number(value || 0);
        return Number.isFinite(number) && number > 0 ? number : 0;
    }

    function displayCount(count) {
        return count > 9 ? "9+" : String(count);
    }

    function updateBadge(selector, count) {
        const badge = document.querySelector(selector);

        if (!badge) {
            return;
        }

        const normalized = normalizeCount(count);

        badge.textContent = displayCount(normalized);
        badge.style.display = normalized > 0 ? "" : "none";
    }

    function renderEmptyNotifications(list) {
        if (!list) {
            return;
        }

        list.innerHTML = `
            <div class="notification-popover-empty">
                <i data-lucide="bell-off"></i>
                <strong>لا توجد إشعارات الآن</strong>
                <span>ستظهر هنا تذكيرات المواعيد وتحديثات الطبيب.</span>
            </div>
        `;

        refreshIcons();
    }

    function renderNotifications(notifications) {
        const list = document.querySelector("[data-live-notifications-list]");

        if (!list) {
            return;
        }

        const items = Array.isArray(notifications) ? notifications : [];

        if (!items.length) {
            renderEmptyNotifications(list);
            return;
        }

        list.innerHTML = items.slice(0, 8).map(function (notification) {
            const type = notification.type || "general";
            const icon = getNotificationIcon(type);
            const title = notification.title || "إشعار";
            const body = notification.body || "";
            const time = notification.time || notification.created_at || "";
            const url = notification.url || "#";
            const isUnread = notification.is_read === false ||
                notification.is_read === 0 ||
                notification.is_read === "0" ||
                notification.read_at === null;

            return `
                <a
                    href="${escapeHTML(url)}"
                    class="notification-popover-item ${isUnread ? "is-unread" : ""}"
                >
                    <span class="notification-popover-icon">
                        <i data-lucide="${escapeHTML(icon)}"></i>
                    </span>

                    <div>
                        <strong>${escapeHTML(title)}</strong>
                        <p>${escapeHTML(body)}</p>
                        <small>${escapeHTML(time)}</small>
                    </div>
                </a>
            `;
        }).join("");

        refreshIcons();
    }

    function extractPayload(data) {
        return {
            notificationsCount:
                data.notifications_count ??
                data.notificationsCount ??
                data.unread_notifications ??
                data.unreadNotifications ??
                data.count ??
                0,

            unreadMessages:
                data.unread_messages ??
                data.unreadMessages ??
                data.messages_count ??
                data.messagesCount ??
                0,

            notifications:
                data.notifications ??
                data.patientNotifications ??
                data.items ??
                []
        };
    }

    async function fetchLiveNotifications() {
        const meta = document.querySelector('meta[name="patient-live-notifications-url"]');
        const url = meta ? meta.getAttribute("content") : null;

        if (!url) {
            return;
        }

        try {
            const response = await fetch(url, {
                method: "GET",
                credentials: "same-origin",
                headers: {
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                cache: "no-store"
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const payload = extractPayload(data);

            updateBadge("[data-live-notifications-count]", payload.notificationsCount);
            updateBadge("[data-live-messages-count]", payload.unreadMessages);
            renderNotifications(payload.notifications);
        } catch (error) {
            // لا نكسر الصفحة إذا فشل التحديث الحي.
        }
    }

    function initNotificationDropdown() {
        const wrap = document.querySelector("[data-notification-wrap]");
        const toggle = document.querySelector("[data-notification-toggle]");
        const menu = document.querySelector("[data-notification-menu]");

        if (!wrap || !toggle || !menu) {
            return;
        }

        if (toggle.dataset.etzanNotificationReady === "1") {
            return;
        }

        toggle.dataset.etzanNotificationReady = "1";

        function openMenu() {
            wrap.classList.add("is-open");
            menu.classList.add("is-open");
            toggle.setAttribute("aria-expanded", "true");
            refreshIcons();
        }

        function closeMenu() {
            wrap.classList.remove("is-open");
            menu.classList.remove("is-open");
            toggle.setAttribute("aria-expanded", "false");
        }

        function toggleMenu(event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            const isOpen =
                wrap.classList.contains("is-open") ||
                menu.classList.contains("is-open");

            if (isOpen) {
                closeMenu();
            } else {
                openMenu();
            }
        }

        toggle.addEventListener("click", toggleMenu, true);

        menu.addEventListener("click", function (event) {
            event.stopPropagation();
        });

        document.addEventListener("click", function (event) {
            if (event.target.closest("[data-notification-wrap]")) {
                return;
            }

            closeMenu();
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeMenu();
            }
        });
    }

    function initProfileMenu() {
        const toggle = document.querySelector("[data-profile-toggle]");
        const menu = document.querySelector("[data-profile-menu]");

        if (!toggle || !menu) {
            return;
        }

        if (toggle.dataset.etzanProfileReady === "1") {
            return;
        }

        toggle.dataset.etzanProfileReady = "1";

        function openMenu() {
            menu.classList.add("is-open");
            toggle.setAttribute("aria-expanded", "true");
            refreshIcons();
        }

        function closeMenu() {
            menu.classList.remove("is-open");
            toggle.setAttribute("aria-expanded", "false");
        }

        toggle.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (menu.classList.contains("is-open")) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        menu.addEventListener("click", function (event) {
            event.stopPropagation();
        });

        document.addEventListener("click", function (event) {
            if (event.target.closest("[data-profile-toggle]") || event.target.closest("[data-profile-menu]")) {
                return;
            }

            closeMenu();
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeMenu();
            }
        });
    }

        function initThemeToggle(){
            const THEME_KEY = "etzan-theme";

            const toggleSelector = [
                "[data-theme-toggle]",
                "#themeToggle",
                ".theme-toggle",
                ".js-theme-toggle"
            ].join(",");

            function normalizeTheme(value) {
                return value === "dark" ? "dark" : "light";
            }

            function getTheme() {
                return normalizeTheme(localStorage.getItem(THEME_KEY));
            }

            function syncPageTheme(theme) {
                document.querySelectorAll(
                    ".journey-board-page, .journey-page, .ps-page-premium, .pc-page, [data-sync-theme]"
                ).forEach(function (element) {
                    element.setAttribute("data-theme", theme);
                });
            }

            function updateButtons(theme) {
                document.querySelectorAll(toggleSelector).forEach(function (button) {
                    button.setAttribute("aria-pressed", theme === "dark" ? "true" : "false");
                    button.setAttribute("data-current-theme", theme);

                    const label = button.querySelector("[data-theme-label]");
                    if (label) {
                        label.textContent = theme === "dark" ? "الوضع الفاتح" : "الوضع الداكن";
                    }

                    const icon = button.querySelector("[data-lucide]");
                    if (icon) {
                        icon.setAttribute("data-lucide", theme === "dark" ? "sun" : "moon");
                    }
                });

                if (window.lucide && typeof window.lucide.createIcons === "function") {
                    window.lucide.createIcons();
                }
            }

            function applyTheme(theme) {
                const finalTheme = normalizeTheme(theme);

                localStorage.setItem(THEME_KEY, finalTheme);

                document.documentElement.setAttribute("data-theme", finalTheme);
                document.documentElement.classList.toggle("dark", finalTheme === "dark");
                document.documentElement.classList.toggle("light", finalTheme !== "dark");

                if (document.body) {
                    document.body.setAttribute("data-theme", finalTheme);
                    document.body.classList.toggle("dark", finalTheme === "dark");
                    document.body.classList.toggle("dark-mode", finalTheme === "dark");
                    document.body.classList.toggle("light", finalTheme !== "dark");
                    document.body.classList.toggle("light-mode", finalTheme !== "dark");
                }

                syncPageTheme(finalTheme);
                updateButtons(finalTheme);
            }

            function toggleTheme() {
                const currentTheme = normalizeTheme(
                    document.documentElement.getAttribute("data-theme") || getTheme()
                );

                applyTheme(currentTheme === "dark" ? "light" : "dark");
            }

            window.EtzanTheme = {
                get: getTheme,
                apply: applyTheme,
                toggle: toggleTheme
            };

            applyTheme(getTheme());

            document.addEventListener("click", function (event) {
                const button = event.target.closest(toggleSelector);

                if (!button) {
                    return;
                }

                event.preventDefault();
                toggleTheme();
            });
        }

    function initPatientLive() {
        initNotificationDropdown();
        initProfileMenu();
        initThemeToggle();
        refreshIcons();

        fetchLiveNotifications();

        window.clearInterval(window.__etzanPatientLiveInterval);
        window.__etzanPatientLiveInterval = window.setInterval(fetchLiveNotifications, POLL_INTERVAL);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initPatientLive);
    } else {
        initPatientLive();
    }
})();


/* ==================================================
   Patient Live Toast Notification
   يظهر تنبيه منبثق عند وصول إشعار جديد
   ================================================== */
(function () {
    "use strict";

    let initialized = false;
    let knownNotificationIds = new Set();

    function escapeHTML(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function ensureToastContainer() {
        let container = document.getElementById("patientLiveToastContainer");

        if (!container) {
            container = document.createElement("div");
            container.id = "patientLiveToastContainer";
            container.className = "patient-live-toast-container";
            document.body.appendChild(container);
        }

        return container;
    }

    function showPatientLiveToast(notification) {
        const container = ensureToastContainer();

        const toast = document.createElement("a");
        toast.className = "patient-live-toast";
        toast.href = notification.url || "#";

        toast.innerHTML = `
            <span class="patient-live-toast-icon">
                <i data-lucide="bell-ring"></i>
            </span>

            <span class="patient-live-toast-copy">
                <strong>${escapeHTML(notification.title || "إشعار جديد")}</strong>
                <small>${escapeHTML(notification.body || "")}</small>
            </span>
        `;

        container.prepend(toast);

        if (window.refreshEtzanIcons) {
            window.refreshEtzanIcons();
        } else if (window.lucide && typeof window.lucide.createIcons === "function") {
            window.lucide.createIcons();
        }

        setTimeout(function () {
            toast.classList.add("is-visible");
        }, 40);

        setTimeout(function () {
            toast.classList.remove("is-visible");

            setTimeout(function () {
                toast.remove();
            }, 250);
        }, 6000);
    }

    function getLiveNotificationsUrl() {
        const meta = document.querySelector('meta[name="patient-live-notifications-url"]');
        return meta ? meta.getAttribute("content") : null;
    }

    async function checkNewPatientNotifications() {
        const url = getLiveNotificationsUrl();

        if (!url) {
            return;
        }

        try {
            const response = await fetch(url, {
                method: "GET",
                credentials: "same-origin",
                headers: {
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                cache: "no-store"
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            const notifications =
                data.notifications ||
                data.patientNotifications ||
                data.items ||
                [];

            if (!Array.isArray(notifications)) {
                return;
            }

            const currentIds = new Set();

            notifications.forEach(function (notification) {
                if (!notification.id) {
                    return;
                }

                currentIds.add(String(notification.id));
            });

            /*
             | أول مرة لا نعرض تنبيه لكل الإشعارات القديمة.
             | فقط نحفظها كمعروفة.
             */
            if (!initialized) {
                knownNotificationIds = currentIds;
                initialized = true;
                return;
            }

            const newNotifications = notifications.filter(function (notification) {
                if (!notification.id) {
                    return false;
                }

                return !knownNotificationIds.has(String(notification.id));
            });

            newNotifications.reverse().forEach(function (notification) {
                showPatientLiveToast(notification);
                knownNotificationIds.add(String(notification.id));
            });
        } catch (error) {
            // لا نكسر الصفحة لو فشل الاتصال.
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function () {
            checkNewPatientNotifications();
            setInterval(checkNewPatientNotifications, 10000);
        });
    } else {
        checkNewPatientNotifications();
        setInterval(checkNewPatientNotifications, 10000);
    }
})();

/* ==================================================
   FINAL FIX - Patient Profile Dropdown
   يفتح قائمة صورة الحساب في كل صفحات المريض
   ================================================== */
(function () {
    "use strict";

    function refreshIcons() {
        if (window.refreshEtzanIcons) {
            window.refreshEtzanIcons();
            return;
        }

        if (window.lucide && typeof window.lucide.createIcons === "function") {
            window.lucide.createIcons();
        }
    }

    function initProfileDropdownFinalFix() {
        const profileWrap = document.querySelector(".topbar-profile");
        const toggle = document.querySelector("[data-profile-toggle]");
        const menu = document.querySelector("[data-profile-menu]");

        if (!profileWrap || !toggle || !menu) {
            return;
        }

        if (toggle.dataset.profileFinalFix === "1") {
            return;
        }

        toggle.dataset.profileFinalFix = "1";

        function openMenu() {
            profileWrap.classList.add("is-open");
            menu.classList.add("is-open");
            toggle.setAttribute("aria-expanded", "true");
            refreshIcons();
        }

        function closeMenu() {
            profileWrap.classList.remove("is-open");
            menu.classList.remove("is-open");
            toggle.setAttribute("aria-expanded", "false");
        }

        toggle.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            const isOpen =
                profileWrap.classList.contains("is-open") ||
                menu.classList.contains("is-open");

            if (isOpen) {
                closeMenu();
            } else {
                openMenu();
            }
        }, true);

        menu.addEventListener("click", function (event) {
            event.stopPropagation();
        });

        document.addEventListener("click", function (event) {
            if (
                event.target.closest(".topbar-profile") ||
                event.target.closest("[data-profile-menu]") ||
                event.target.closest("[data-profile-toggle]")
            ) {
                return;
            }

            closeMenu();
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeMenu();
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initProfileDropdownFinalFix);
    } else {
        initProfileDropdownFinalFix();
    }
})();


document.addEventListener("DOMContentLoaded", function () {
    if (typeof initThemeToggle === "function") {
        initThemeToggle();
    }
});
