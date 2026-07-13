document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("notifBtn");
    const dropdown = document.getElementById("notifDropdown");
    const badge = document.getElementById("notifBadge");
    const list = document.getElementById("notifList");
    const markAllBtn = document.getElementById("markAllBtn");

    if (!btn || !dropdown || !badge || !list) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function isUnread(item) {
        if (item.is_unread === true) return true;
        if (item.unread === true) return true;
        if (item.is_read === false) return true;
        if (item.read_at === null) return true;

        return false;
    }

    function setBadge(count) {
        const safeCount = Number(count || 0);

        if (safeCount > 0) {
            badge.textContent = safeCount > 99 ? "99+" : safeCount;
            badge.classList.remove("is-hidden");
            btn.classList.add("has-unread");
        } else {
            badge.textContent = "0";
            badge.classList.add("is-hidden");
            btn.classList.remove("has-unread");
        }
    }

    function renderEmpty(message = "لا توجد إشعارات غير مقروءة") {
        list.innerHTML = `
            <div class="admin-notif-empty">
                <i class="fa-regular fa-bell-slash"></i>
                <span>${escapeHtml(message)}</span>
            </div>
        `;
    }

    function loadCount() {
        fetch("/admin/notifications/unread-count", {
            headers: {
                "Accept": "application/json",
            },
        })
            .then((res) => res.json())
            .then((data) => setBadge(data.count))
            .catch(() => setBadge(0));
    }

    function renderNotifications(notifications) {
        const unreadNotifications = (notifications || []).filter(isUnread);

        if (!unreadNotifications.length) {
            renderEmpty();
            return;
        }

        list.innerHTML = unreadNotifications.map((item) => {
            const url = item.url || "/admin/notifications";
            const title = escapeHtml(item.title || "إشعار جديد");
            const body = escapeHtml(item.body || "");
            const time = escapeHtml(item.time || item.created_at || "");
            const icon = escapeHtml(item.icon || item.type_icon || "fa-regular fa-bell");

            return `
                <a href="${url}" class="admin-notif-item">
                    <span class="admin-notif-item__icon">
                        <i class="${icon}"></i>
                    </span>

                    <span class="admin-notif-item__content">
                        <strong>${title}</strong>
                        ${body ? `<small>${body}</small>` : ""}
                        ${time ? `<em>${time}</em>` : ""}
                    </span>
                </a>
            `;
        }).join("");
    }

    function loadNotifications() {
        list.innerHTML = `
            <div class="admin-notif-empty">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>جاري التحميل...</span>
            </div>
        `;

        fetch("/admin/notifications/dropdown", {
            headers: {
                "Accept": "application/json",
            },
        })
            .then((res) => res.json())
            .then((data) => renderNotifications(data.notifications || []))
            .catch(() => {
                list.innerHTML = `
                    <div class="admin-notif-empty">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>تعذر تحميل الإشعارات</span>
                    </div>
                `;
            });
    }

    btn.addEventListener("click", function (event) {
        event.stopPropagation();
        dropdown.classList.toggle("is-open");

        if (dropdown.classList.contains("is-open")) {
            loadNotifications();
        }
    });

    dropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });

    document.addEventListener("click", function () {
        dropdown.classList.remove("is-open");
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            dropdown.classList.remove("is-open");
        }
    });

    markAllBtn?.addEventListener("click", function () {
        markAllBtn.disabled = true;
        markAllBtn.classList.add("is-loading");

        fetch("/admin/notifications/mark-all-read", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrf,
                "Accept": "application/json",
            },
        })
            .then(() => {
                setBadge(0);
                renderEmpty("تم تعليم كل الإشعارات كمقروءة");
            })
            .finally(() => {
                markAllBtn.disabled = false;
                markAllBtn.classList.remove("is-loading");
            });
    });

    loadCount();
    setInterval(loadCount, 30000);
});
