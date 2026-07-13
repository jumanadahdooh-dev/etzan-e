/* =========================================================
   Etzan Admin UI
   File: public/front/js/admin.js
   ========================================================= */
(function () {
    'use strict';

    const doc = document;
    const html = doc.documentElement;
    const body = doc.body;

    const shell = doc.getElementById('adminShell');
    const sidebar = doc.getElementById('adminSidebar');
    const sidebarToggle = doc.getElementById('adminSidebarToggle');
    const brandToggle = doc.getElementById('adminBrandToggle');
    const mobileBtn = doc.getElementById('adminMobileMenuBtn');
    const overlay = doc.getElementById('adminMobileOverlay');

    /* Sidebar accordion (submenus like الإعدادات) */
    doc.querySelectorAll('[data-nav-accordion-trigger]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            const accordion = trigger.closest('.admin-nav__accordion');
            if (!accordion) return;
            const willOpen = !accordion.classList.contains('is-open');
            accordion.classList.toggle('is-open', willOpen);
            trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

    /* تمييز الصفحة الفرعية النشطة حالياً داخل القائمة الفرعية (زي إعدادات المظهر مثلاً) */
    (function highlightActiveSublink() {
        const currentHash = window.location.hash;
        if (!currentHash) return;

        doc.querySelectorAll('.admin-nav__sublink').forEach(function (link) {
            const linkHash = '#' + link.href.split('#')[1];
            if (linkHash === currentHash && link.pathname === window.location.pathname) {
                link.classList.add('is-current');
                const accordion = link.closest('.admin-nav__accordion');
                if (accordion) accordion.classList.add('is-open');
            }
        });
    })();


    const storageGet = (key, fallback = null) => {
        try { return localStorage.getItem(key) ?? fallback; } catch (_) { return fallback; }
    };

    const storageSet = (key, value) => {
        try { localStorage.setItem(key, value); } catch (_) {}
    };

    const isDesktop = () => window.matchMedia('(min-width: 1181px)').matches;

    function setCollapsed(collapsed) {
        if (!sidebar || !shell) return;

        sidebar.classList.toggle('is-collapsed', collapsed);
        shell.classList.toggle('is-sidebar-collapsed', collapsed);
        body.classList.toggle('admin-sidebar-collapsed', collapsed);
        storageSet('etzan.admin.sidebar.collapsed', collapsed ? '1' : '0');

        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-label', collapsed ? 'فتح القائمة' : 'طي القائمة');
            sidebarToggle.dataset.tooltip = collapsed ? 'فتح القائمة' : 'طي القائمة';
        }

        if (brandToggle) {
            brandToggle.setAttribute('aria-label', collapsed ? 'فتح القائمة' : 'إتزان');
            brandToggle.dataset.tooltip = collapsed ? 'فتح القائمة' : 'إتزان';
        }
    }

    function openSidebar() {
        if (!sidebar || !shell) return;
        sidebar.classList.add('is-open');
        shell.classList.add('is-sidebar-open');
        body.classList.add('admin-sidebar-open');
        overlay?.classList.add('is-active');
    }

    function closeSidebar() {
        if (!sidebar || !shell) return;
        sidebar.classList.remove('is-open');
        shell.classList.remove('is-sidebar-open');
        body.classList.remove('admin-sidebar-open');
        overlay?.classList.remove('is-active');
    }

    if (sidebar && shell) {
        const collapsed = storageGet('etzan.admin.sidebar.collapsed', '0') === '1';
        if (isDesktop()) setCollapsed(collapsed);

        sidebarToggle?.addEventListener('click', function () {
            if (isDesktop()) {
                setCollapsed(true);
            } else {
                closeSidebar();
            }
        });

        brandToggle?.addEventListener('click', function (event) {
            if (isDesktop() && sidebar.classList.contains('is-collapsed')) {
                event.preventDefault();
                setCollapsed(false);
            }
        });

        mobileBtn?.addEventListener('click', openSidebar);
        overlay?.addEventListener('click', closeSidebar);

        window.addEventListener('resize', function () {
            if (isDesktop()) {
                closeSidebar();
                setCollapsed(storageGet('etzan.admin.sidebar.collapsed', '0') === '1');
            } else {
                sidebar.classList.remove('is-collapsed');
                shell.classList.remove('is-sidebar-collapsed');
                body.classList.remove('admin-sidebar-collapsed');
            }
        });
    }

    /* Tooltip */
    let tooltip = doc.querySelector('.admin-sidebar-tooltip');
    if (!tooltip) {
        tooltip = doc.createElement('div');
        tooltip.className = 'admin-sidebar-tooltip';
        body.appendChild(tooltip);
    }

    function showTooltip(element) {
        if (!sidebar || !sidebar.classList.contains('is-collapsed') || !isDesktop()) return;
        const text = element.dataset.tooltip || element.getAttribute('aria-label') || '';
        if (!text) return;
        const rect = element.getBoundingClientRect();
        tooltip.textContent = text;
        tooltip.style.top = `${rect.top + rect.height / 2}px`;
        tooltip.classList.add('is-visible');
    }

    function hideTooltip() {
        tooltip.classList.remove('is-visible');
    }

    doc.querySelectorAll('.admin-nav__link, .admin-nav__sublink, #adminBrandToggle, #adminSidebarToggle').forEach(function (element) {
        element.addEventListener('mouseenter', () => showTooltip(element));
        element.addEventListener('mouseleave', hideTooltip);
        element.addEventListener('focus', () => showTooltip(element));
        element.addEventListener('blur', hideTooltip);
    });

    /* Dropdowns */
    function closeDropdowns(except = null) {
        doc.querySelectorAll('.admin-profile-menu.is-open, .admin-notif-wrap.is-open, .admin-global-search.is-open').forEach(function (el) {
            if (el !== except) el.classList.remove('is-open');
        });
        doc.querySelectorAll('.admin-profile-dropdown.is-open, .admin-notif-dropdown.is-open, .admin-search-panel.is-open').forEach(function (el) {
            if (!except || !except.contains(el)) el.classList.remove('is-open');
        });
    }

    const profileMenu = doc.querySelector('.admin-profile-menu');
    const profileTrigger = doc.querySelector('[data-admin-profile-trigger]');
    profileTrigger?.addEventListener('click', function (event) {
        event.stopPropagation();
        const willOpen = !profileMenu?.classList.contains('is-open');
        closeDropdowns(profileMenu);
        profileMenu?.classList.toggle('is-open', willOpen);
    });

    const notifWrap = doc.querySelector('.admin-notif-wrap');
    const notifTrigger = doc.querySelector('[data-admin-notifications-trigger]');
    const notifBody = doc.querySelector('.admin-notif-dropdown__body');
    let notifLoaded = false;

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function loadNotificationsDropdown() {
        if (!notifBody) return;
        notifBody.innerHTML = '<div class="admin-notif-loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div>';

        fetch(notifTrigger.dataset.notificationsUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const items = data.notifications || [];
                if (!items.length) {
                    notifBody.innerHTML = '<div class="admin-notif-empty"><i class="fa-regular fa-bell-slash"></i><span>لا توجد إشعارات جديدة</span></div>';
                    return;
                }
                notifBody.innerHTML = items.map(function (n) {
                    return '<a href="' + n.url + '" class="admin-notif-item ' + (n.is_unread ? 'is-unread' : '') + '">' +
                        '<span class="admin-notif-item__icon"><i class="' + escapeHtml(n.icon) + '"></i></span>' +
                        '<span class="admin-notif-item__body"><strong>' + escapeHtml(n.title) + '</strong>' +
                        '<small>' + escapeHtml(n.body) + '</small>' +
                        '<em>' + escapeHtml(n.time) + '</em></span>' +
                        '</a>';
                }).join('');
            })
            .catch(function () {
                notifBody.innerHTML = '<div class="admin-notif-empty"><i class="fa-solid fa-triangle-exclamation"></i><span>تعذّر تحميل الإشعارات</span></div>';
            });
    }

    notifTrigger?.addEventListener('click', function (event) {
        event.stopPropagation();
        const willOpen = !notifWrap?.classList.contains('is-open');
        closeDropdowns(notifWrap);
        notifWrap?.classList.toggle('is-open', willOpen);
        if (willOpen) {
            loadNotificationsDropdown();
        }
    });

    doc.addEventListener('click', function (event) {
        if (!event.target.closest('.admin-profile-menu, .admin-notif-wrap, .admin-global-search')) {
            closeDropdowns();
        }
    });

    doc.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDropdowns();
            closeSidebar();
        }
    });

    /* Search */
const searchWrap = doc.querySelector('[data-admin-search]');
const searchInput = doc.querySelector('[data-admin-search-input]');
const searchPanel = doc.querySelector('[data-admin-search-panel]');
const searchItems = Array.from(doc.querySelectorAll('[data-search-text]'));
const searchEmpty = doc.querySelector('[data-search-empty]');

function normalizeSearchText(value) {
    return String(value || '')
        .toLowerCase()
        .trim()
        .replace(/[أإآا]/g, 'ا')
        .replace(/ى/g, 'ي')
        .replace(/ة/g, 'ه')
        .replace(/[ًٌٍَُِّْ]/g, '');
}

function openSearchPanel() {
    if (!searchWrap || !searchPanel) return;

    closeDropdowns(searchWrap);

    searchWrap.classList.add('is-open');
    searchPanel.classList.add('is-open');
}

function closeSearchPanel() {
    if (!searchWrap || !searchPanel) return;

    searchWrap.classList.remove('is-open');
    searchPanel.classList.remove('is-open');
}

function resetSearchItems() {
    searchItems.forEach((item) => {
        item.classList.remove('is-search-hidden');
        item.hidden = false;
    });

    if (searchEmpty) {
        searchEmpty.classList.remove('is-visible');
        searchEmpty.hidden = true;
    }
}

function escapeSearchHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}

const searchLiveContainer = doc.querySelector('[data-search-live]');
let searchDebounceTimer = null;

function fetchLiveSearchResults(term) {
    if (!searchLiveContainer) return;

    if (term.length < 2) {
        searchLiveContainer.innerHTML = '';
        return;
    }

    searchLiveContainer.innerHTML = '<div class="admin-search-live__loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div>';

    fetch(searchInput.dataset.searchUrl + '?q=' + encodeURIComponent(term), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    })
        .then(function (res) {
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            return res.json();
        })
        .then(function (data) {
            const items = data.results || [];
            if (!items.length) {
                searchLiveContainer.innerHTML = '<div class="admin-search-live__label">لا توجد نتائج مطابقة بقاعدة البيانات</div>';
                return;
            }
            searchLiveContainer.innerHTML =
                '<div class="admin-search-live__label">نتائج من قاعدة البيانات</div>' +
                items.map(function (r) {
                    return '<a href="' + r.url + '" class="admin-search-live__item">' +
                        '<i class="' + escapeSearchHtml(r.icon) + '"></i>' +
                        '<span><strong>' + escapeSearchHtml(r.title) + '</strong>' +
                        '<small>' + escapeSearchHtml(r.type) + ' · ' + escapeSearchHtml(r.subtitle) + '</small></span>' +
                        '</a>';
                }).join('');
        })
        .catch(function (err) {
            console.error('Etzan search error:', err);
            searchLiveContainer.innerHTML = '<div class="admin-search-live__label" style="color:#ef4444">خطأ بالبحث: ' + escapeSearchHtml(err.message) + '</div>';
        });
}

function filterAdminSearch() {
    if (!searchInput || !searchWrap || !searchPanel) return;

    const query = normalizeSearchText(searchInput.value);
    let visibleCount = 0;

    if (!query) {
        resetSearchItems();
        closeSearchPanel();
        return;
    }

    openSearchPanel();

    searchItems.forEach((item) => {
        const itemText = normalizeSearchText(
            `${item.dataset.searchText || ''} ${item.textContent || ''}`
        );

        const isMatch = itemText.includes(query);

        item.classList.toggle('is-search-hidden', !isMatch);
        item.hidden = !isMatch;

        if (isMatch) {
            visibleCount++;
        }
    });

    if (searchEmpty) {
        const noResults = visibleCount === 0;

        searchEmpty.classList.toggle('is-visible', noResults);
        searchEmpty.hidden = !noResults;
    }
}

searchInput?.addEventListener('input', function () {
    filterAdminSearch();

    const rawTerm = searchInput.value.trim();
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(function () {
        fetchLiveSearchResults(rawTerm);
    }, 300);
});

searchInput?.addEventListener('focus', function () {
    if (searchInput.value.trim().length > 0) {
        filterAdminSearch();
    }
});

searchInput?.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        searchInput.value = '';
        resetSearchItems();
        closeSearchPanel();
        searchInput.blur();
    }
});

doc.addEventListener('keydown', function (event) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        searchInput?.focus();
    }
});

    /* Theme */
    const themeBtn = doc.getElementById('adminThemeToggle');
    const themeIcon = doc.getElementById('adminThemeIcon');
    const themeText = doc.getElementById('adminThemeText');

    function applyTheme(theme) {
        const isDark = theme === 'dark';
        html.dataset.theme = theme;
        body.dataset.theme = theme;
        body.classList.toggle('dark', isDark);
        body.classList.toggle('theme-dark', isDark);
        body.classList.toggle('theme-light', !isDark);

        if (themeIcon) themeIcon.className = isDark ? 'fa-regular fa-sun' : 'fa-regular fa-moon';
        if (themeText) themeText.textContent = isDark ? 'تفعيل الوضع الفاتح' : 'تفعيل الوضع الداكن';
        themeBtn?.classList.toggle('is-on', isDark);
        storageSet('etzan.admin.theme', theme);
    }

    const storedTheme = storageGet('etzan.admin.theme');
    if (storedTheme === 'dark' || storedTheme === 'light') {
        applyTheme(storedTheme);
    } else {
        applyTheme(body.dataset.theme === 'dark' || body.classList.contains('dark') ? 'dark' : 'light');
    }

    themeBtn?.addEventListener('click', function () {
        const next = (body.dataset.theme === 'dark' || body.classList.contains('dark')) ? 'light' : 'dark';
        applyTheme(next);
    });
})();
