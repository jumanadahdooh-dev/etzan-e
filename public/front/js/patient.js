(function () {
    "use strict";

    /* ==================================================
       1) Helpers
    ================================================== */
    function ready(callback) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", callback);
        } else {
            callback();
        }
    }

    function qs(selector, scope) {
        return (scope || document).querySelector(selector);
    }

    function qsa(selector, scope) {
        return Array.from((scope || document).querySelectorAll(selector));
    }

    function refreshIcons() {
        if (window.lucide && typeof window.lucide.createIcons === "function") {
            window.lucide.createIcons();
        }
    }

    /* ==================================================
       2) Toasts
    ================================================== */
    function ensureToastContainer() {
        var container = qs("[data-toast-container]");

        if (!container) {
            container = document.createElement("div");
            container.className = "patient-toast";
            container.setAttribute("data-toast-container", "");
            document.body.appendChild(container);
        }

        return container;
    }

    function showToast(message) {
        if (!message) {
            return;
        }

        var container = ensureToastContainer();
        var toast = document.createElement("div");

        toast.className = "toast-item";
        toast.textContent = message;

        container.appendChild(toast);

        requestAnimationFrame(function () {
            toast.classList.add("is-visible");
        });

        window.setTimeout(function () {
            toast.classList.remove("is-visible");

            window.setTimeout(function () {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 260);
        }, 3500);
    }

    function initToasts() {
        qsa("[data-toast]").forEach(function (button) {
            button.addEventListener("click", function () {
                showToast(button.getAttribute("data-toast"));
            });
        });

        qsa("[data-auto-toast]").forEach(function (item) {
            var message = item.getAttribute("data-auto-toast");

            if (message) {
                window.setTimeout(function () {
                    showToast(message);
                }, 700);
            }
        });

        window.showPatientToast = window.showPatientToast || showToast;
    }

    /* ==================================================
       3) Profile Dropdown
    ================================================== */
    function initProfileDropdown() {
        var profileWrap = qs(".topbar-profile");
        var profileButton = qs(".profile-avatar-btn, .simple-avatar-btn, [data-profile-toggle]");
        var profileMenu = qs(".profile-menu");

        if (!profileWrap || !profileButton || !profileMenu) {
            return;
        }

        function openProfileMenu() {
            profileMenu.classList.add("is-open");
            profileButton.setAttribute("aria-expanded", "true");
        }

        function closeProfileMenu() {
            profileMenu.classList.remove("is-open");
            profileButton.setAttribute("aria-expanded", "false");
        }

        profileButton.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (profileMenu.classList.contains("is-open")) {
                closeProfileMenu();
            } else {
                openProfileMenu();
            }
        });

        profileMenu.addEventListener("click", function (event) {
            event.stopPropagation();
        });

        document.addEventListener("click", closeProfileMenu);

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeProfileMenu();
            }
        });
    }

    /* ==================================================
       4) Notification Dropdown
    ================================================== */
    function initNotificationDropdown() {
        var wrap = qs("[data-notification-wrap]");
        var toggle = qs("[data-notification-toggle]");
        var menu = qs("[data-notification-menu]");

        if (!wrap || !toggle || !menu) {
            return;
        }

        if (toggle.dataset.dropdownReady === "1") {
            return;
        }

        toggle.dataset.dropdownReady = "1";

        function openMenu() {
            wrap.classList.add("is-open");
            menu.classList.add("is-open");
            toggle.setAttribute("aria-expanded", "true");
        }

        function closeMenu() {
            wrap.classList.remove("is-open");
            menu.classList.remove("is-open");
            toggle.setAttribute("aria-expanded", "false");
        }

        toggle.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (menu.classList.contains("is-open") || wrap.classList.contains("is-open")) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        menu.addEventListener("click", function (event) {
            event.stopPropagation();
        });

        document.addEventListener("click", closeMenu);

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeMenu();
            }
        });
    }

    /* ==================================================
       5) Profile Reason Modal
    ================================================== */
    function initProfileReasonModal() {
        var modal = qs("[data-profile-reason-modal]");
        var openButtons = qsa("[data-profile-reason-open]");
        var closeButtons = qsa("[data-profile-reason-close]");

        if (!modal || openButtons.length === 0) {
            return;
        }

        function openModal() {
            modal.classList.add("is-open");
            modal.setAttribute("aria-hidden", "false");
            document.body.style.overflow = "hidden";
            refreshIcons();
        }

        function closeModal() {
            modal.classList.remove("is-open");
            modal.setAttribute("aria-hidden", "true");
            document.body.style.overflow = "";
        }

        openButtons.forEach(function (button) {
            button.addEventListener("click", function (event) {
                event.preventDefault();
                openModal();
            });
        });

        closeButtons.forEach(function (button) {
            button.addEventListener("click", closeModal);
        });

        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && modal.classList.contains("is-open")) {
                closeModal();
            }
        });
    }

    /* ==================================================
       6) Tabs
    ================================================== */
    function initTabs() {
        qsa("[data-home-tabs]").forEach(function (tabs) {
            qsa("button", tabs).forEach(function (button) {
                button.addEventListener("click", function () {
                    qsa("button", tabs).forEach(function (item) {
                        item.classList.remove("is-active");
                    });

                    button.classList.add("is-active");
                });
            });
        });
    }

    /* ==================================================
       7) Smooth Local Links
    ================================================== */
    function initLocalLinks() {
        qsa('a[href^="#"]').forEach(function (link) {
            link.addEventListener("click", function (event) {
                var targetId = link.getAttribute("href");

                if (!targetId || targetId === "#") {
                    return;
                }

                var target = qs(targetId);

                if (!target) {
                    return;
                }

                event.preventDefault();

                target.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            });
        });
    }

    /* ==================================================
       8) Patient Sidebar Collapse
       سهم طي / فتح القائمة الجانبية
    ================================================== */
    function initPatientSidebarCollapse() {
        var sidebar = qs("[data-patient-sidebar], .patient-sidebar");
        var shell = qs(".patient-shell");
        var collapseButton = qs("[data-sidebar-collapse]");
        var closeButton = qs("[data-sidebar-close]");
        var backdrop = qs("[data-sidebar-backdrop]");
        var storageKey = "etzan_patient_sidebar_collapsed";

        if (!sidebar || !collapseButton) {
            return;
        }

        function applyCollapsedState(isCollapsed) {
            sidebar.classList.toggle("is-collapsed", isCollapsed);
            document.body.classList.toggle("sidebar-collapsed", isCollapsed);
            document.body.classList.toggle("patient-sidebar-collapsed", isCollapsed);

            if (shell) {
                shell.classList.toggle("sidebar-is-collapsed", isCollapsed);
            }

            collapseButton.setAttribute("aria-expanded", isCollapsed ? "false" : "true");
            collapseButton.setAttribute("aria-label", isCollapsed ? "فتح القائمة" : "طي القائمة");

            localStorage.setItem(storageKey, isCollapsed ? "1" : "0");
            refreshIcons();
        }

        applyCollapsedState(localStorage.getItem(storageKey) === "1");

        collapseButton.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            var nextState = !sidebar.classList.contains("is-collapsed");
            applyCollapsedState(nextState);
        });

        /* لما القائمة مسكّرة: الضغط عاللوجو يفتحها (نفس الأدمن بالضبط) */
        var brandToggle = qs("[data-brand-toggle]", sidebar);
        var originalBrandIcon = null;

        /* نقرأ اسم الأيقونة الأصلية من الصفحة قبل أي تبديل (سمّاعة للطبيب، قلب للمريض) */
        if (brandToggle) {
            var initialIcon = brandToggle.querySelector(".brand-mark i[data-lucide], .brand-mark svg[data-lucide]");
            originalBrandIcon = (initialIcon && initialIcon.getAttribute("data-lucide")) || "heart-pulse";
        }

        function setBrandIcon(iconName) {
            if (!brandToggle) return;
            var mark = brandToggle.querySelector(".brand-mark");
            if (!mark) return;

            /* Lucide بيستبدل <i> بـ <svg> — فلازم نبني عنصر جديد كل مرة */
            mark.innerHTML = '<i data-lucide="' + iconName + '"></i>';
            refreshIcons();
        }

        if (brandToggle) {
            brandToggle.addEventListener("click", function (event) {
                event.preventDefault();
                event.stopPropagation();
                var nextState = !sidebar.classList.contains("is-collapsed");
                applyCollapsedState(nextState);
                /* بعد الضغط: اللوجو يرجع أصلي دايماً (الهوفر هو يلي بيبدّله) */
                setBrandIcon(originalBrandIcon || "heart-pulse");
            });

            /* لما القائمة مسكّرة وتمرّري الماوس عاللوجو → يتحوّل لـ 3 خطوط (تلميح: اضغطي لفتح) */
            brandToggle.addEventListener("mouseenter", function () {
                if (sidebar.classList.contains("is-collapsed")) {
                    setBrandIcon("menu");
                }
            });
            brandToggle.addEventListener("mouseleave", function () {
                if (sidebar.classList.contains("is-collapsed")) {
                    setBrandIcon(originalBrandIcon || "heart-pulse");
                }
            });
        }

        if (closeButton) {
            closeButton.addEventListener("click", function (event) {
                event.preventDefault();

                sidebar.classList.remove("is-open");
                document.body.classList.remove("sidebar-open");
                document.body.classList.remove("patient-sidebar-open");
            });
        }

        if (backdrop) {
            backdrop.addEventListener("click", function (event) {
                event.preventDefault();

                sidebar.classList.remove("is-open");
                document.body.classList.remove("sidebar-open");
                document.body.classList.remove("patient-sidebar-open");
            });
        }
    }

    /* ==================================================
       9) Mobile Sidebar Open
       زر فتح القائمة في الموبايل إن وجد
    ================================================== */
    function initMobileSidebar() {
        var sidebar = qs("[data-patient-sidebar], .patient-sidebar");
        var buttons = qsa("[data-sidebar-open], [data-mobile-menu], .mobile-menu-btn");

        if (!sidebar || buttons.length === 0) {
            return;
        }

        buttons.forEach(function (button) {
            button.addEventListener("click", function (event) {
                event.preventDefault();

                sidebar.classList.add("is-open");
                document.body.classList.add("sidebar-open");
                document.body.classList.add("patient-sidebar-open");
            });
        });
    }

    /* ==================================================
       9b) Sidebar Tooltip (نفس آلية الأدمن بالضبط)
       لما القائمة مسكّرة وتحوّم عأيقونة → اسم الصفحة يطلع جنبها
    ================================================== */
    function initSidebarTooltip() {
        var sidebar = qs("[data-patient-sidebar], .patient-sidebar");
        if (!sidebar) return;

        var tip = document.createElement("div");
        tip.className = "patient-sidebar-tooltip";
        document.body.appendChild(tip);

        function isCollapsed() {
            return document.body.classList.contains("sidebar-collapsed")
                || document.body.classList.contains("patient-sidebar-collapsed");
        }

        function isDesktop() {
            return window.innerWidth > 1180;
        }

        function showTip(el) {
            if (!isCollapsed() || !isDesktop()) return;
            var text = el.dataset.tooltip || el.getAttribute("aria-label") || "";
            if (!text) return;
            var rect = el.getBoundingClientRect();
            tip.textContent = text;
            tip.style.top = (rect.top + rect.height / 2) + "px";
            tip.classList.add("is-visible");
        }

        function hideTip() {
            tip.classList.remove("is-visible");
        }

        sidebar.querySelectorAll(".sidebar-link[data-tooltip]").forEach(function (link) {
            link.addEventListener("mouseenter", function () { showTip(link); });
            link.addEventListener("mouseleave", hideTip);
        });
    }

    /* ==================================================
       10) Init
    ================================================== */
    ready(function () {
        initToasts();
        initProfileDropdown();
        initNotificationDropdown();
        initProfileReasonModal();
        initTabs();
        initLocalLinks();
        initPatientSidebarCollapse();
        initMobileSidebar();
        initSidebarTooltip();

        window.setTimeout(refreshIcons, 50);
        window.setTimeout(refreshIcons, 300);
    });
})();
