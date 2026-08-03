// ================================================================
// 📅 نظام حجز المواعيد - نسخة نظيفة ومبسطة
// ================================================================

document.addEventListener('DOMContentLoaded', function () {

    // ========================================
    // 1. تشغيل التقويم
    // ========================================
    const dateTrigger = document.querySelector('[data-appt-date-trigger]');
    const calendarCard = document.querySelector('.appt-calendar-card');
    const closeBtn = calendarCard ? calendarCard.querySelector('[data-calendar-close]') : null;

    if (dateTrigger) {
        dateTrigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const form = this.closest('[data-appt-form]');
            if (!form) return;
            const isOpen = form.classList.contains('is-calendar-open');
            document.querySelectorAll('[data-appt-form].is-calendar-open').forEach(function (f) {
                if (f !== form) f.classList.remove('is-calendar-open');
            });
            form.classList.toggle('is-calendar-open', !isOpen);
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const form = this.closest('[data-appt-form]');
            if (form) form.classList.remove('is-calendar-open');
        });
    }

    document.addEventListener('click', function (e) {
        const calendar = e.target.closest('.appt-calendar-card');
        const trigger = e.target.closest('[data-appt-date-trigger]');
        if (!calendar && !trigger) {
            document.querySelectorAll('[data-appt-form].is-calendar-open').forEach(function (form) {
                form.classList.remove('is-calendar-open');
            });
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('[data-appt-form].is-calendar-open').forEach(function (form) {
                form.classList.remove('is-calendar-open');
            });
        }
    });

    // ========================================
    // 2. اختيار يوم من التقويم
    // ========================================
    document.querySelectorAll('[data-appt-calendar-day]').forEach(function (day) {
        day.addEventListener('click', function (e) {
            e.preventDefault();
            if (this.disabled) return;

            const form = this.closest('[data-appt-form]');
            if (!form) return;

            form.querySelectorAll('[data-appt-calendar-day]').forEach(function (btn) {
                btn.classList.remove('is-selected');
                btn.setAttribute('aria-pressed', 'false');
            });

            this.classList.add('is-selected');
            this.setAttribute('aria-pressed', 'true');

            const dateInput = form.querySelector('[data-appt-date-input]');
            if (dateInput) {
                dateInput.value = this.dataset.dateValue || '';
                dateInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            form.querySelectorAll('[data-calendar-selected-label]').forEach(function (label) {
                label.textContent = this.dataset.dateLabel || this.dataset.dateValue || '';
            }.bind(this));

            const message = form.querySelector('[data-date-message]');
            if (message) {
                message.textContent = '';
                message.classList.remove('is-visible');
            }

            form.classList.remove('is-calendar-open');

            // ✅ جلب الأوقات لهذا التاريخ
            const date = dateInput ? dateInput.value : '';
            if (date) {
                fetchAvailableSlots(date);
            }
        });
    });

    // ========================================
    // 3. جلب الأوقات المتاحة (بيانات وهمية)
    // ========================================
    function fetchAvailableSlots(date) {
        if (!date) return;
        console.log('📅 جلب الأوقات للتاريخ:', date);

        const slotsBox = document.querySelector('[data-slot-picker]');
        if (!slotsBox) return;

        // بيانات وهمية للاختبار
        const dummyData = {
            slots: [
                {
                    icon: 'sunrise',
                    label: 'الفترة الصباحية',
                    times: ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30']
                },
                {
                    icon: 'sunset',
                    label: 'الفترة المسائية',
                    times: ['16:00', '16:30', '17:00', '17:30', '18:00', '18:30']
                }
            ]
        };

        setTimeout(function() {
            updateSlotsUI(dummyData);
        }, 300);
    }

    // ========================================
    // 4. تحديث واجهة الأوقات
    // ========================================
    function updateSlotsUI(data) {
        const slotsBox = document.querySelector('[data-slot-picker]');
        if (!slotsBox) return;

        const emptySlots = slotsBox.querySelector('.appt-empty-slots');
        if (emptySlots) emptySlots.remove();

        const timeLaunchRow = slotsBox.querySelector('.appt-time-launch-row');
        if (timeLaunchRow) timeLaunchRow.style.display = 'flex';

        if (!data.slots || data.slots.length === 0) {
            showNoSlotsMessage('لا توجد أوقات متاحة لهذا التاريخ');
            return;
        }

        const periodTabs = slotsBox.querySelector('.appt-period-tabs');
        const orbitStage = slotsBox.querySelector('.appt-orbit-stage');

        if (periodTabs) {
            periodTabs.innerHTML = '';
            data.slots.forEach(function(period, index) {
                const tab = document.createElement('button');
                tab.type = 'button';
                tab.className = 'appt-period-tab' + (index === 0 ? ' is-active' : '');
                tab.dataset.periodKey = 'period-' + index;
                tab.innerHTML = `
                    <i data-lucide="${period.icon || 'clock-3'}"></i>
                    <span>${period.label || 'أوقات متاحة'}</span>
                `;
                periodTabs.appendChild(tab);

                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const slotPicker = this.closest('[data-slot-picker]');
                    if (!slotPicker) return;
                    const periodKey = this.dataset.periodKey;
                    slotPicker.querySelectorAll('.appt-period-tab').forEach(function(t) {
                        t.classList.remove('is-active');
                    });
                    this.classList.add('is-active');
                    slotPicker.querySelectorAll('.appt-orbit-panel').forEach(function(panel) {
                        panel.classList.toggle('is-active', panel.dataset.periodKey === periodKey);
                    });
                });
            });

            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        if (orbitStage) {
            orbitStage.innerHTML = '';
            data.slots.forEach(function(period, index) {
                const panel = document.createElement('section');
                panel.className = 'appt-orbit-panel' + (index === 0 ? ' is-active' : '');
                panel.dataset.periodKey = 'period-' + index;

                let timesHTML = '';
                if (period.times && period.times.length > 0) {
                    period.times.forEach(function(time) {
                        timesHTML += `
                            <button type="button" class="slot-btn orbit-slot" data-time-value="${time}">
                                <span class="slot-time">${time}</span>
                            </button>
                        `;
                    });
                }

                panel.innerHTML = `
                    <div class="appt-orbit-dial">
                        <div class="slot-buttons-grid">${timesHTML}</div>
                    </div>
                `;
                orbitStage.appendChild(panel);
            });

            document.querySelectorAll('[data-time-value]').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    document.querySelectorAll('[data-time-value]').forEach(function(btn) {
                        btn.classList.remove('is-active');
                    });
                    this.classList.add('is-active');
                    const selectedInput = document.querySelector('[data-selected-time]');
                    if (selectedInput) {
                        selectedInput.value = this.dataset.timeValue || '';
                    }
                    document.querySelectorAll('[data-selected-time-label]').forEach(function(label) {
                        label.textContent = selectedInput ? selectedInput.value : 'اختاري الوقت';
                    });
                    const timePopover = document.querySelector('[data-time-picker]');
                    if (timePopover) {
                        timePopover.setAttribute('aria-hidden', 'true');
                        const slotPicker = document.querySelector('[data-slot-picker]');
                        if (slotPicker) slotPicker.classList.remove('is-time-open');
                    }
                });
            });

            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function showNoSlotsMessage(message) {
        const slotsBox = document.querySelector('[data-slot-picker]');
        if (!slotsBox) return;
        const timeLaunchRow = slotsBox.querySelector('.appt-time-launch-row');
        if (timeLaunchRow) timeLaunchRow.style.display = 'none';
        const periodTabs = slotsBox.querySelector('.appt-period-tabs');
        if (periodTabs) periodTabs.innerHTML = '';
        const orbitStage = slotsBox.querySelector('.appt-orbit-stage');
        if (orbitStage) orbitStage.innerHTML = '';
        let emptySlots = slotsBox.querySelector('.appt-empty-slots');
        if (!emptySlots) {
            emptySlots = document.createElement('div');
            emptySlots.className = 'appt-empty-slots';
            slotsBox.appendChild(emptySlots);
        }
        emptySlots.style.display = 'flex';
        emptySlots.innerHTML = `
            <i data-lucide="calendar-x-2"></i>
            <div>
                <strong>${message || 'لا توجد أوقات متاحة لهذا التاريخ'}</strong>
                <span>اختاري يومًا آخر من التقويم، أو انتظري تحديث جدول الطبيب.</span>
            </div>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // ========================================
    // 5. فتح وإغلاق نافذة الوقت
    // ========================================
    const timeTrigger = document.querySelector('[data-time-picker-trigger]');
    const timeClose = document.querySelector('[data-time-picker-close]');
    const timePopover = document.querySelector('[data-time-picker]');

    if (timeTrigger) {
        timeTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const slotPicker = this.closest('[data-slot-picker]');
            if (!slotPicker) return;
            const isOpen = slotPicker.classList.contains('is-time-open');
            document.querySelectorAll('[data-slot-picker].is-time-open').forEach(function(picker) {
                if (picker !== slotPicker) {
                    picker.classList.remove('is-time-open');
                    const popover = picker.querySelector('[data-time-picker]');
                    if (popover) popover.setAttribute('aria-hidden', 'true');
                }
            });
            slotPicker.classList.toggle('is-time-open', !isOpen);
            if (timePopover) {
                timePopover.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
            }
        });
    }

    if (timeClose) {
        timeClose.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const slotPicker = this.closest('[data-slot-picker]');
            if (slotPicker) {
                slotPicker.classList.remove('is-time-open');
                if (timePopover) timePopover.setAttribute('aria-hidden', 'true');
            }
        });
    }

    document.addEventListener('click', function(e) {
        const timePicker = e.target.closest('[data-time-picker]');
        const trigger = e.target.closest('[data-time-picker-trigger]');
        if (!timePicker && !trigger) {
            document.querySelectorAll('[data-slot-picker].is-time-open').forEach(function(picker) {
                picker.classList.remove('is-time-open');
                const popover = picker.querySelector('[data-time-picker]');
                if (popover) popover.setAttribute('aria-hidden', 'true');
            });
        }
    });

    // ========================================
    // 6. التحقق من صحة النموذج
    // ========================================
    const form = document.querySelector('[data-appt-form]');
    const selectedTimeInput = document.querySelector('[data-selected-time]');

    if (form && selectedTimeInput) {
        form.addEventListener('submit', function(e) {
            const dateInput = this.querySelector('[data-appt-date-input]');
            if (dateInput && !dateInput.value) {
                showFormError('⚠️ اختاري تاريخ الموعد أولًا');
                e.preventDefault();
                return;
            }
            if (!selectedTimeInput.value) {
                showFormError('⚠️ اختاري وقت الموعد أولًا');
                e.preventDefault();
                return;
            }
        });
    }

    function showFormError(message) {
        const errorMsg = document.querySelector('[data-slot-unavailable-message]');
        if (errorMsg) {
            errorMsg.textContent = message;
            errorMsg.classList.add('is-visible');
        } else if (typeof window.showPatientToast === 'function') {
            window.showPatientToast(message);
        } else {
            alert(message);
        }
    }

    // ========================================
    // 7. جلب الأوقات عند تحميل الصفحة
    // ========================================
    const initialDateInput = document.querySelector('[data-appt-date-input]');
    if (initialDateInput && initialDateInput.value) {
        setTimeout(function() { fetchAvailableSlots(initialDateInput.value); }, 500);
    }

    console.log('✅ Appointments system initialized successfully');
});