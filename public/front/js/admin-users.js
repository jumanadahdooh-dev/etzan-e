/* ============================================================================
   Etzan Admin - Users JavaScript Logic
   Handles Modals, AJAX Requests, and UI State
============================================================================ */

// ==========================================
// 0. تبديل تبويبات مودال التعديل (عام / الأمان)
// ==========================================
function upSwitchTab(event, tabName) {
    document.querySelectorAll('.up-modal__tab').forEach(t => t.classList.remove('is-active'));
    document.querySelectorAll('.up-tab-panel').forEach(p => p.classList.remove('is-active'));
    event.currentTarget.classList.add('is-active');
    document.querySelector(`.up-tab-panel[data-panel="${tabName}"]`)?.classList.add('is-active');
}

function upToggleFiltersPanel() {
    document.getElementById('upFiltersPanel')?.classList.toggle('active');
}

function upToggleSearchClear() {
    const input = document.getElementById('upSearchInput');
    const clearBtn = document.getElementById('upSearchClear');
    if (clearBtn) clearBtn.style.display = input.value ? 'grid' : 'none';
}

function upClearSearch() {
    const input = document.getElementById('upSearchInput');
    input.value = '';
    document.getElementById('upSearchBox').submit();
}

// ==========================================
// إظهار/إخفاء صندوق البحث (أيقونة بس افتراضياً)
// ==========================================
function upToggleSearch() {
    const wrap = document.getElementById('upSearchWrap');
    const input = document.getElementById('upSearchInput');
    const isOpen = wrap.classList.toggle('active');
    if (isOpen) {
        setTimeout(() => input.focus(), 150);
    }
}

// ==========================================
// Ripple عند الضغط على الأزرار الرئيسية
// ==========================================
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.up-btn-apply, .up-btn-save, .up-btn-danger, .up-btn-cancel, .up-tip-btn');
    if (!btn) return;

    const rect = btn.getBoundingClientRect();
    const ripple = document.createElement('span');
    const size = Math.max(rect.width, rect.height);
    ripple.className = 'up-ripple';
    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
    ripple.style.top = `${e.clientY - rect.top - size / 2}px`;
    btn.appendChild(ripple);
    setTimeout(() => ripple.remove(), 550);
});

let currentUserId = null;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// ==========================================
// 1. نظام التعديل (Edit Flow)
// ==========================================

// فتح نافذة التعديل وجلب البيانات
async function upOpenEdit(id) {
    currentUserId = id;

    // إظهار الـ Modal فارغاً أولاً (لتحسين تجربة المستخدم)
    document.getElementById('upEditOverlay').classList.add('active');

    // رجّعي التبويب الأول (عام) كل ما نفتح المودال من جديد
    document.querySelectorAll('.up-modal__tab').forEach(t => t.classList.remove('is-active'));
    document.querySelectorAll('.up-tab-panel').forEach(p => p.classList.remove('is-active'));
    document.querySelector('.up-modal__tab[data-tab="general"]')?.classList.add('is-active');
    document.querySelector('.up-tab-panel[data-panel="general"]')?.classList.add('is-active');

    // تصفير الحقول وإخفاء الأخطاء
    clearErrors();
    document.getElementById('upEditName').value = 'جاري التحميل...';
    document.getElementById('upEditEmail').value = 'جاري التحميل...';

    try {
        // طلب البيانات من السيرفر (دالة show في الكنترولر)
        const response = await fetch(`/admin/users/${id}`);
        const data = await response.json();

        if(data.success) {
            document.getElementById('upEditName').value = data.name;
            document.getElementById('upEditEmail').value = data.email;
            document.getElementById('upEditRole').value = data.role;
            document.getElementById('upEditPass').value = '';
            document.getElementById('upEditPassConf').value = '';
        } else {
            upShowToast('خطأ', data.message || 'تعذر جلب البيانات', 'danger');
            upCloseEdit();
        }
    } catch (error) {
        upShowToast('خطأ بالاتصال', 'تأكد من اتصالك بالإنترنت', 'danger');
        upCloseEdit();
    }
}

// إغلاق نافذة التعديل
function upCloseEdit() {
    document.getElementById('upEditOverlay').classList.remove('active');
    currentUserId = null;
}

// حفظ التعديلات (إرسال AJAX)
async function upSaveUser() {
    if(!currentUserId) return;

    const btn = document.getElementById('upSaveBtn');
    const spinner = document.getElementById('upSaveSpinner');
    const txt = document.getElementById('upSaveTxt');

    // تشغيل الأنيميشن (Loading)
    btn.disabled = true;
    spinner.classList.add('active');
    txt.innerText = 'جاري الحفظ...';
    clearErrors();

    // تجهيز البيانات
    const payload = {
        name: document.getElementById('upEditName').value,
        email: document.getElementById('upEditEmail').value,
        role: document.getElementById('upEditRole').value,
        password: document.getElementById('upEditPass').value,
        password_confirmation: document.getElementById('upEditPassConf').value,
    };

    try {
        const response = await fetch(`/admin/users/${currentUserId}`, {
            method: 'PUT', // كما طلبنا في الكنترولر
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok && data.success) {
            upShowToast('نجاح', data.message, 'success');
            upCloseEdit();
            // تحديث البيانات في الجدول برمجياً بدون ريفريش
            updateTableRow(data.user);
        } else if (response.status === 422) {
            // عرض أخطاء الـ Validation
            showErrors(data.errors);
        } else {
            upShowToast('خطأ', data.message || 'حدث خطأ أثناء الحفظ', 'danger');
        }
    } catch (error) {
        upShowToast('خطأ', 'انقطع الاتصال بالخادم', 'danger');
    } finally {
        // إيقاف الأنيميشن
        btn.disabled = false;
        spinner.classList.remove('active');
        txt.innerText = 'حفظ التغييرات';
    }
}

// ==========================================
// 2. نظام الحذف (Delete Flow)
// ==========================================

function upOpenDelete(id, name) {
    currentUserId = id;
    document.getElementById('upDeleteName').innerText = name;
    document.getElementById('upDeleteOverlay').classList.add('active');
}

function upCloseDelete() {
    document.getElementById('upDeleteOverlay').classList.remove('active');
    currentUserId = null;
}

async function upConfirmDelete() {
    if(!currentUserId) return;

    const btn = document.getElementById('upDeleteBtn');
    const spinner = document.getElementById('upDeleteSpinner');
    const txt = document.getElementById('upDeleteTxt');

    btn.disabled = true;
    spinner.classList.add('active');
    txt.innerText = 'جاري الحذف...';

    try {
        const response = await fetch(`/admin/users/${currentUserId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            upShowToast('نجاح', data.message, 'success');
            upCloseDelete();
            // إخفاء السطر من الجدول بتأثير ناعم
            const row = document.getElementById(`up-row-${currentUserId}`);
            if(row) {
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
        } else {
            upShowToast('خطأ', data.message || 'لا يمكنك حذف هذا المستخدم', 'danger');
            upCloseDelete();
        }
    } catch (error) {
        upShowToast('خطأ', 'حدث خطأ بالاتصال', 'danger');
    } finally {
        btn.disabled = false;
        spinner.classList.remove('active');
        txt.innerText = 'نعم، احذف الحساب';
    }
}

// ==========================================
// 4. العرض السريع (Quick View)
// ==========================================

async function upOpenView(id) {
    document.getElementById('upViewOverlay').classList.add('active');
    const body = document.getElementById('upViewBody');
    body.innerHTML = `
        <div class="up-view-skeleton">
            <div class="up-view-skeleton__header">
                <div class="up-skeleton up-view-skeleton__avatar"></div>
                <div class="up-view-skeleton__lines">
                    <div class="up-skeleton up-view-skeleton__line" style="width:60%"></div>
                    <div class="up-skeleton up-view-skeleton__line" style="width:40%"></div>
                </div>
            </div>
            <div class="up-skeleton up-view-skeleton__block"></div>
            <div class="up-skeleton up-view-skeleton__block"></div>
        </div>
    `;

    const editBtn = document.getElementById('upViewEditBtn');
    if (editBtn) {
        editBtn.onclick = function () {
            upCloseView();
            upOpenEdit(id);
        };
    }

    try {
        const response = await fetch(`/admin/users/${id}`, { headers: { 'Accept': 'application/json' } });
        const data = await response.json();

        if (!data.success) {
            body.innerHTML = '<div class="up-view-loading">تعذر جلب البيانات.</div>';
            return;
        }

        const roleLabel = data.role === 'admin' ? 'مدير' : (data.role === 'doctor' ? 'طبيب' : 'مريض');
        const statusLabel = data.status === 'suspended' ? 'موقوف' : 'نشط';
        const ins = data.insights || {};

        let insightsHtml = '<p class="up-view-empty">ما في مؤشرات إضافية لهاد الدور.</p>';
        if (data.role === 'doctor') {
            insightsHtml = `
                <div class="up-view-grid">
                    <div class="up-view-stat"><strong>${ins.doctor_patients ?? 0}</strong><span>مرضاه</span></div>
                    <div class="up-view-stat"><strong>${ins.doctor_appointments ?? 0}</strong><span>مواعيده</span></div>
                    <div class="up-view-stat"><strong>${ins.doctor_articles ?? 0}</strong><span>مقالاته</span></div>
                    <div class="up-view-stat"><strong>${ins.doctor_rating ?? 0}</strong><span>تقييمه (${ins.doctor_reviews ?? 0})</span></div>
                </div>`;
        } else if (data.role === 'patient') {
            insightsHtml = `
                <div class="up-view-grid">
                    <div class="up-view-stat"><strong>${ins.patient_appointments ?? 0}</strong><span>مواعيده</span></div>
                    <div class="up-view-stat"><strong>${ins.patient_meals ?? 0}</strong><span>وجباته</span></div>
                    <div class="up-view-stat"><strong>${ins.patient_tasks ?? 0}</strong><span>مهامه</span></div>
                    <div class="up-view-stat"><strong>${ins.patient_profile_completed ? 'مكتمل' : 'غير مكتمل'}</strong><span>ملفه الصحي</span></div>
                </div>`;
        }

        const activity = data.activity || [];
        const activityHtml = activity.length
            ? activity.map(a => `
                <div class="up-view-activity-item">
                    <span class="up-view-activity-dot"></span>
                    <div>
                        <p>${a.description}</p>
                        <span>${a.time}</span>
                    </div>
                </div>`).join('')
            : '<p class="up-view-empty">ما في إجراءات مسجلة على هاد الحساب.</p>';

        body.innerHTML = `
            <div class="up-view-header">
                <div class="up-avatar up-avatar--lg up-avatar--${data.role === 'admin' ? 'purple' : (data.role === 'doctor' ? 'blue' : 'orange')}">${(data.name || '').charAt(0).toUpperCase()}</div>
                <div>
                    <div class="up-view-name">${data.name}</div>
                    <div class="up-view-email">${data.email}</div>
                    <div class="up-view-meta">
                        <span class="up-role-badge up-role-badge--${data.role}"><span class="up-role-dot"></span>${roleLabel}</span>
                        <span class="up-status up-status--${data.status}"><span class="up-status__dot"></span>${statusLabel}</span>
                    </div>
                </div>
            </div>

            <div class="up-view-section">
                <h6 class="up-view-section__title"><i class="fa-solid fa-id-card"></i> معلومات شخصية</h6>
                <div class="up-view-rows">
                    <div><span>الاسم الكامل</span><strong>${data.name}</strong></div>
                    <div><span>البريد الإلكتروني</span><strong>${data.email}</strong></div>
                    <div><span>رقم الهاتف</span><strong>${data.phone ?? 'غير مسجل'}</strong></div>
                </div>
            </div>

            <div class="up-view-section">
                <h6 class="up-view-section__title"><i class="fa-solid fa-user-gear"></i> معلومات الحساب</h6>
                <div class="up-view-rows">
                    <div><span>الدور</span><strong>${roleLabel}</strong></div>
                    <div><span>حالة الحساب</span><strong>${statusLabel}</strong></div>
                    <div><span>تاريخ الانضمام</span><strong>${data.created_at ?? '—'}</strong></div>
                    <div><span>آخر دخول</span><strong>${data.last_login_at ?? '—'}</strong></div>
                </div>
            </div>

            <div class="up-view-section">
                <h6 class="up-view-section__title"><i class="fa-solid fa-shield-check"></i> التحقق</h6>
                ${data.verified
                    ? '<span class="up-view-verified"><i class="fa-solid fa-circle-check"></i> البريد الإلكتروني موثّق</span>'
                    : '<span class="up-view-verified up-view-verified--no"><i class="fa-solid fa-circle-xmark"></i> البريد الإلكتروني غير موثّق</span>'}
            </div>

            <div class="up-view-section">
                <h6 class="up-view-section__title"><i class="fa-solid fa-chart-simple"></i> النشاط والمؤشرات</h6>
                ${insightsHtml}
            </div>

            <div class="up-view-section">
                <h6 class="up-view-section__title"><i class="fa-solid fa-clock-rotate-left"></i> آخر الإجراءات على الحساب</h6>
                <div class="up-view-activity-list">${activityHtml}</div>
            </div>
        `;
    } catch (error) {
        body.innerHTML = '<div class="up-view-loading">انقطع الاتصال بالخادم.</div>';
    }
}

function upCloseView() {
    document.getElementById('upViewOverlay').classList.remove('active');
}
function upCloseViewOverlay(e) { if (e.target.id === 'upViewOverlay') upCloseView(); }

// ==========================================
// 5. تعليق / تفعيل الحساب
// ==========================================

async function upToggleStatus(id) {
    try {
        const response = await fetch(`/admin/users/${id}/status`, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        const data = await response.json();

        if (data.success) {
            upShowToast('تم', data.message, 'success');
            const badge = document.getElementById(`up-status-${id}`);
            if (badge) {
                badge.className = `up-status up-status--${data.status}`;
                badge.innerHTML = `<span class="up-status__dot"></span>${data.status === 'suspended' ? 'موقوف' : 'نشط'}`;
            }
            const row = document.getElementById(`up-row-${id}`);
            const suspendBtn = row?.querySelector('.up-icon-btn--suspend i');
            if (suspendBtn) {
                suspendBtn.className = `fa-solid ${data.status === 'suspended' ? 'fa-play' : 'fa-pause'}`;
            }
        } else {
            upShowToast('خطأ', data.message || 'تعذر تنفيذ الإجراء', 'danger');
        }
    } catch (error) {
        upShowToast('خطأ', 'انقطع الاتصال بالخادم', 'danger');
    }
}

// ==========================================
// 6. التحديد الجماعي (Bulk Actions)
// ==========================================

function upToggleSelectAll(checkbox) {
    document.querySelectorAll('.up-row-checkbox').forEach(cb => { cb.checked = checkbox.checked; });
    upUpdateBulkBar();
}

function upUpdateBulkBar() {
    const all = document.querySelectorAll('.up-row-checkbox');
    const checked = document.querySelectorAll('.up-row-checkbox:checked');
    const bar = document.getElementById('upBulkBar');
    const selectAllPrompt = document.getElementById('upSelectAllPrompt');

    document.getElementById('upBulkCount').innerText = checked.length;
    bar.classList.toggle('active', checked.length > 0);

    if (selectAllPrompt) {
        const showPrompt = checked.length > 0 && checked.length < all.length;
        selectAllPrompt.style.display = showPrompt ? 'grid' : 'none';
        selectAllPrompt.setAttribute('data-tip', `تحديد الكل (${all.length})`);
    }
}

function upSelectAllUsers() {
    document.querySelectorAll('.up-row-checkbox').forEach(cb => { cb.checked = true; });
    upUpdateBulkBar();
}

function upBulkClear() {
    document.querySelectorAll('.up-row-checkbox').forEach(cb => { cb.checked = false; });
    const selectAll = document.getElementById('upSelectAll');
    if (selectAll) selectAll.checked = false;
    upUpdateBulkBar();
}

async function upBulkDelete() {
    const ids = Array.from(document.querySelectorAll('.up-row-checkbox:checked')).map(cb => cb.value);
    if (!ids.length) return;
    if (!confirm(`هل أنت متأكد من حذف ${ids.length} مستخدم؟ هذا الإجراء لا يمكن التراجع عنه.`)) return;

    try {
        const response = await fetch('/admin/users/bulk', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ ids })
        });
        const data = await response.json();

        if (data.success) {
            upShowToast('تم', data.message, 'success');
            ids.forEach(id => {
                const row = document.getElementById(`up-row-${id}`);
                if (row) { row.style.opacity = '0'; setTimeout(() => row.remove(), 300); }
            });
            upBulkClear();
        } else {
            upShowToast('خطأ', data.message || 'تعذر تنفيذ الحذف الجماعي', 'danger');
        }
    } catch (error) {
        upShowToast('خطأ', 'انقطع الاتصال بالخادم', 'danger');
    }
}

// ==========================================
// 7. دوال مساعدة (Helpers)
// ==========================================

// إغلاق المودال عند الضغط على الخلفية السوداء
function upCloseEditOverlay(e) { if(e.target.id === 'upEditOverlay') upCloseEdit(); }
function upCloseDeleteOverlay(e) { if(e.target.id === 'upDeleteOverlay') upCloseDelete(); }

// عرض الأخطاء تحت الحقول
function showErrors(errors) {
    if(errors.name) { document.getElementById('upEditNameErr').classList.add('active'); document.querySelector('#upEditNameErr span').innerText = errors.name[0]; }
    if(errors.email) { document.getElementById('upEditEmailErr').classList.add('active'); document.querySelector('#upEditEmailErr span').innerText = errors.email[0]; }
    if(errors.role) { document.getElementById('upEditRoleErr').classList.add('active'); document.querySelector('#upEditRoleErr span').innerText = errors.role[0]; }
    if(errors.password) { document.getElementById('upEditPassErr').classList.add('active'); document.querySelector('#upEditPassErr span').innerText = errors.password[0]; }
}

function clearErrors() {
    document.querySelectorAll('.up-field-error').forEach(el => el.classList.remove('active'));
}

// تحديث سطر الجدول بعد التعديل لكي لا نحتاج لعمل Refresh
function updateTableRow(user) {
    const nameEl = document.getElementById(`up-name-${user.id}`);
    const emailEl = document.getElementById(`up-email-${user.id}`);
    const roleTxt = document.getElementById(`up-role-txt-${user.id}`);
    const roleBadge = document.getElementById(`up-badge-${user.id}`);

    if(nameEl) nameEl.innerText = user.name;
    if(emailEl) emailEl.innerText = user.email;
    if(roleTxt) roleTxt.innerText = user.role === 'admin' ? 'مدير' : (user.role === 'doctor' ? 'طبيب' : 'مريض');

    if(roleBadge) {
        roleBadge.className = `up-role-badge up-role-badge--${user.role}`;
    }
}

// نظام الإشعارات (Toasts)
function upShowToast(title, message, type = 'success') {
    const container = document.getElementById('upToasts');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `up-toast ${type === 'danger' ? 'up-toast--danger' : ''}`;
    toast.innerHTML = `<strong>${title}</strong><p>${message}</p>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('leaving');
        setTimeout(() => toast.remove(), 250);
    }, 3200);
}
