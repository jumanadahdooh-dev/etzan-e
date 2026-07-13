(function () {
    "use strict";

    const page = document.querySelector(".journey-board-page");

    if (!page) {
        return;
    }

    const tabs = Array.from(page.querySelectorAll(".journey-tab"));
    const taskGrid = page.querySelector("#journeyTaskGrid");
    const emptyState = page.querySelector("#journeyEmptyState");
    const filterEmptyState = page.querySelector("#missionFilterEmpty");
    const filterEmptyTitle = page.querySelector("#missionFilterEmptyTitle");
    const filterEmptyText = page.querySelector("#missionFilterEmptyText");
    const showAllTasksFromEmpty = page.querySelector("#showAllTasksFromEmpty");

    const modal = page.querySelector("#journeyModal");
    const form = page.querySelector("#journeyTaskForm");
    const taskDateInput = page.querySelector("#taskDateInput");
    const toast = page.querySelector("#journeyToast");

    const noteModal = document.getElementById("journeyNoteModal");
    const noteForm = document.getElementById("journeyNoteForm");
    const noteInput = document.getElementById("taskNoteInput");
    const noteTaskTitle = document.getElementById("journeyNoteTaskTitle");

    const openModalButtons = [
        page.querySelector("#openJourneyModal"),
        page.querySelector("#openJourneyModalEmpty"),
        page.querySelector("#addTaskForSelectedDay")
    ].filter(Boolean);

    const progressRing = page.querySelector("#journeyProgressRing");
    const progressPercent = page.querySelector("#journeyProgressPercent");
    const completedCount = page.querySelector("#completedCount");
    const pendingCount = page.querySelector("#pendingCount");
    const lateCount = page.querySelector("#lateCount");
    const scheduleCount = page.querySelector(".side-count");

    const calendarView = page.querySelector("#journeyCalendarView");
    const calendarModes = Array.from(page.querySelectorAll(".calendar-mode"));
    const calendarPrevBtn = page.querySelector("#calendarPrevBtn");
    const calendarNextBtn = page.querySelector("#calendarNextBtn");
    const calendarCurrentRange = page.querySelector("#calendarCurrentRange");
    const calendarSelectedLabel = page.querySelector("#calendarSelectedLabel");

    const selectedDayTitle = page.querySelector("#selectedDayTitle");
    const selectedDayTotal = page.querySelector("#selectedDayTotal");
    const selectedDayCompleted = page.querySelector("#selectedDayCompleted");
    const selectedDayLate = page.querySelector("#selectedDayLate");
    const selectedDayTasks = page.querySelector("#selectedDayTasks");

    let activeFilter = "all";
    let toastTimer = null;

    let calendarMode = "day";
    let selectedDate = normalizeDate(page.dataset.today) || toYMD(new Date());
    let calendarCursor = fromYMD(selectedDate);

    let tasksData = readTasksSeed();

    const arabicMonths = [
        "يناير",
        "فبراير",
        "مارس",
        "أبريل",
        "مايو",
        "يونيو",
        "يوليو",
        "أغسطس",
        "سبتمبر",
        "أكتوبر",
        "نوفمبر",
        "ديسمبر"
    ];

    const arabicWeekDays = [
        "الأحد",
        "الإثنين",
        "الثلاثاء",
        "الأربعاء",
        "الخميس",
        "الجمعة",
        "السبت"
    ];

    const weekHeaderDays = [
        "السبت",
        "الأحد",
        "الإثنين",
        "الثلاثاء",
        "الأربعاء",
        "الخميس",
        "الجمعة"
    ];

    function normalizeDate(value) {
        if (!value) {
            return "";
        }

        return String(value).trim().slice(0, 10);
    }

    function getArabicDayName(date) {
        return arabicWeekDays[date.getDay()];
    }

    function readTasksSeed() {
        const seed = page.querySelector("#journeyTasksSeed");

        if (!seed) {
            return [];
        }

        try {
            const data = JSON.parse(seed.textContent || "[]");
            return Array.isArray(data) ? data : [];
        } catch (error) {
            return [];
        }
    }

    function detectCurrentTheme() {
        const html = document.documentElement;
        const body = document.body;

        const htmlTheme = html.getAttribute("data-theme");
        const bodyTheme = body.getAttribute("data-theme");

        const storedTheme =
            localStorage.getItem("theme") ||
            localStorage.getItem("site-theme") ||
            localStorage.getItem("etzan-theme");

        const hasDarkClass =
            html.classList.contains("dark") ||
            body.classList.contains("dark") ||
            html.classList.contains("dark-mode") ||
            body.classList.contains("dark-mode");

        if (
            htmlTheme === "dark" ||
            bodyTheme === "dark" ||
            storedTheme === "dark" ||
            hasDarkClass
        ) {
            return "dark";
        }

        return "light";
    }

    function syncPageThemeWithMainTheme() {
        const html = document.documentElement;
        const body = document.body;

        function applyTheme() {
            const theme = detectCurrentTheme();

            page.dataset.theme = theme;

            if (modal) {
                modal.dataset.theme = theme;
            }

            if (noteModal) {
                noteModal.dataset.theme = theme;
            }
        }

        applyTheme();

        const observer = new MutationObserver(applyTheme);

        observer.observe(html, {
            attributes: true,
            attributeFilter: ["class", "data-theme"]
        });

        observer.observe(body, {
            attributes: true,
            attributeFilter: ["class", "data-theme"]
        });

        window.addEventListener("storage", applyTheme);

        document.addEventListener("click", function () {
            setTimeout(applyTheme, 50);
        });
    }

    function escapeHTML(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function toYMD(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");

        return `${year}-${month}-${day}`;
    }

    function fromYMD(value) {
        const parts = String(value || "").split("-");
        const year = Number(parts[0]);
        const month = Number(parts[1]);
        const day = Number(parts[2]);

        if (!year || !month || !day) {
            return new Date();
        }

        return new Date(year, month - 1, day);
    }

    function addDays(date, amount) {
        const next = new Date(date);
        next.setDate(next.getDate() + amount);
        return next;
    }

    function addMonths(date, amount) {
        const next = new Date(date);
        next.setMonth(next.getMonth() + amount);
        return next;
    }

    function startOfWeek(date) {
        const next = new Date(date);
        const day = next.getDay();
        const diff = (day + 1) % 7;

        next.setHours(0, 0, 0, 0);
        next.setDate(next.getDate() - diff);

        return next;
    }

    function endOfWeek(date) {
        return addDays(startOfWeek(date), 6);
    }

    function startOfMonth(date) {
        return new Date(date.getFullYear(), date.getMonth(), 1);
    }

    function getMonthGridDates(date) {
        const monthStart = startOfMonth(date);
        const gridStart = startOfWeek(monthStart);

        return Array.from({ length: 42 }, function (_, index) {
            return addDays(gridStart, index);
        });
    }

    function formatDateLabel(dateValue) {
        const date = typeof dateValue === "string" ? fromYMD(dateValue) : dateValue;
        return `${date.getDate()} ${arabicMonths[date.getMonth()]}، ${date.getFullYear()}`;
    }

    function formatShortRange(startDate, endDate) {
        return `${startDate.getDate()} ${arabicMonths[startDate.getMonth()]} - ${endDate.getDate()} ${arabicMonths[endDate.getMonth()]}، ${endDate.getFullYear()}`;
    }

    function showToast(message) {
        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.classList.add("is-visible");

        clearTimeout(toastTimer);

        toastTimer = setTimeout(function () {
            toast.classList.remove("is-visible");
        }, 2600);
    }

    function openModal() {
        if (!modal) {
            return;
        }

        if (taskDateInput) {
            taskDateInput.value = selectedDate;
        }

        modal.dataset.theme = page.dataset.theme || detectCurrentTheme();

        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");

        document.documentElement.classList.add("journey-modal-open");
        document.body.classList.add("journey-modal-open");

        const firstInput = modal.querySelector("input");

        if (firstInput) {
            setTimeout(function () {
                firstInput.focus();
            }, 80);
        }
    }

    function closeModal() {
        if (!modal) {
            return;
        }

        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");

        if (!noteModal || !noteModal.classList.contains("is-open")) {
            document.documentElement.classList.remove("journey-modal-open");
            document.body.classList.remove("journey-modal-open");
        }

        resetFormErrors();
    }

    function openNoteModal(button) {
        if (!noteModal || !noteForm || !noteInput) {
            showToast("نافذة الملاحظة غير موجودة في الصفحة.");
            return;
        }

        const action = button.getAttribute("data-note-action") || "";
        const title = button.getAttribute("data-note-title") || "مهمة يومية";
        const currentNote = button.getAttribute("data-note-current") || "";

        if (!action) {
            showToast("رابط حفظ الملاحظة غير موجود.");
            return;
        }

        noteForm.setAttribute("action", action);
        noteInput.value = currentNote;

        if (noteTaskTitle) {
            noteTaskTitle.textContent = title;
        }

        noteModal.dataset.theme = page.dataset.theme || detectCurrentTheme();

        noteModal.classList.add("is-open");
        noteModal.setAttribute("aria-hidden", "false");

        document.documentElement.classList.add("journey-modal-open");
        document.body.classList.add("journey-modal-open");

        setTimeout(function () {
            noteInput.focus();
        }, 80);
    }

    function closeNoteModal() {
        if (!noteModal) {
            return;
        }

        noteModal.classList.remove("is-open");
        noteModal.setAttribute("aria-hidden", "true");

        if (!modal || !modal.classList.contains("is-open")) {
            document.documentElement.classList.remove("journey-modal-open");
            document.body.classList.remove("journey-modal-open");
        }
    }

    function getCards() {
        if (!taskGrid) {
            return [];
        }

        return Array.from(taskGrid.querySelectorAll(".mission-task-item, .journey-task-card"));
    }

    function shouldShowCard(card, filter) {
        const source = card.dataset.source;
        const status = card.dataset.status;

        if (filter === "all") return true;
        if (filter === "doctor") return source === "doctor";
        if (filter === "self") return source === "self";
        if (filter === "completed") return status === "completed";
        if (filter === "late") return status === "late";

        return true;
    }

    function applyFilter(filter) {
        activeFilter = filter;

        const cards = getCards();
        let visibleCount = 0;

        cards.forEach(function (card) {
            const isVisible = shouldShowCard(card, filter);

            card.classList.toggle("is-hidden", !isVisible);

            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (emptyState) {
            emptyState.hidden = cards.length !== 0;
        }

        if (filterEmptyState) {
            const shouldShowFilterEmpty = cards.length > 0 && visibleCount === 0;

            filterEmptyState.hidden = !shouldShowFilterEmpty;

            if (shouldShowFilterEmpty) {
                const labels = {
                    doctor: {
                        title: "لا توجد مهام مضافة من الطبيب هذا اليوم",
                        text: "لم يضف الطبيب أي مهمة لهذا اليوم حتى الآن. يمكنك متابعة مهامك الشخصية أو الرجوع إلى تبويب الكل."
                    },
                    self: {
                        title: "لا توجد مهام شخصية هذا اليوم",
                        text: "لم تضف أي مهمة شخصية لهذا اليوم بعد."
                    },
                    completed: {
                        title: "لا توجد مهام مكتملة بعد",
                        text: "عند إنجاز أي مهمة ستظهر هنا مباشرة."
                    },
                    late: {
                        title: "لا توجد مهام متأخرة",
                        text: "ممتاز، لا توجد مهام متأخرة هذا اليوم."
                    }
                };

                const message = labels[filter] || {
                    title: "لا توجد مهام ضمن هذا التصنيف",
                    text: "جرّب اختيار تبويب الكل لعرض كل مهام اليوم."
                };

                if (filterEmptyTitle) {
                    filterEmptyTitle.textContent = message.title;
                }

                if (filterEmptyText) {
                    filterEmptyText.textContent = message.text;
                }
            }
        }
    }

    function updateTabs(clickedTab) {
        tabs.forEach(function (tab) {
            tab.classList.toggle("is-active", tab === clickedTab);
        });
    }

    function updateProgress() {
        const cards = getCards();
        const total = cards.length;

        const completed = cards.filter(function (card) {
            return card.dataset.status === "completed";
        }).length;

        const late = cards.filter(function (card) {
            return card.dataset.status === "late";
        }).length;

        const remaining = Math.max(total - completed - late, 0);
        const percent = total === 0 ? 0 : Math.round((completed / total) * 100);

        if (progressRing) {
            progressRing.style.setProperty("--progress", `${percent}%`);
        }

        if (progressPercent) {
            progressPercent.textContent = `${percent}%`;
        }

        if (completedCount) {
            completedCount.textContent = String(completed);
        }

        if (pendingCount) {
            pendingCount.textContent = String(remaining);
        }

        if (lateCount) {
            lateCount.textContent = String(late);
        }

        if (scheduleCount) {
            scheduleCount.textContent = `${total} مهام`;
        }
    }

    function taskTimeSortValue(task) {
        if (!task.time) {
            return 9999;
        }

        const parts = String(task.time).split(":");
        return Number(parts[0] || 0) * 60 + Number(parts[1] || 0);
    }

    function getTasksByDate(dateValue) {
        return tasksData
            .filter(function (task) {
                return normalizeDate(task.date) === normalizeDate(dateValue);
            })
            .sort(function (a, b) {
                return taskTimeSortValue(a) - taskTimeSortValue(b);
            });
    }

    function getDayStatus(tasks) {
        if (tasks.some(function (task) { return task.status === "late"; })) return "late";
        if (tasks.some(function (task) { return task.status === "now"; })) return "now";
        if (tasks.some(function (task) { return task.status === "pending"; })) return "pending";
        if (tasks.some(function (task) { return task.status === "completed"; })) return "completed";

        return "empty";
    }

    function renderMiniTasks(tasks) {
        if (!tasks.length) {
            return `<span class="calendar-empty-text">لا مهام</span>`;
        }

        const visible = tasks.slice(0, 3).map(function (task) {
            const statusClass = task.status === "pending" && task.source === "doctor"
                ? "doctor"
                : task.status;

            return `<span class="calendar-task-dot ${escapeHTML(statusClass)}"></span>`;
        }).join("");

        const more = tasks.length > 3
            ? `<span class="calendar-more-dot">+${tasks.length - 3}</span>`
            : "";

        return `
            <div class="calendar-task-dots">
                ${visible}
                ${more}
            </div>
        `;
    }

    function renderCalendarDay(date, options) {
        const dateValue = toYMD(date);
        const tasks = getTasksByDate(dateValue);
        const isToday = dateValue === normalizeDate(page.dataset.today);
        const isSelected = dateValue === selectedDate;
        const isOutside = options && options.outside;
        const dayStatus = getDayStatus(tasks);

        return `
            <button
                type="button"
                class="calendar-day ${isToday ? "is-today today" : ""} ${isSelected ? "is-selected" : ""} ${isOutside ? "is-outside" : ""} has-${dayStatus}"
                data-calendar-date="${dateValue}"
                data-date="${dateValue}"
                aria-selected="${isSelected ? "true" : "false"}"
            >
                <strong class="calendar-date-number">${date.getDate()}</strong>

                <span class="calendar-day-title">${getArabicDayName(date)}</span>

                <div class="calendar-mini-tasks">
                    ${renderMiniTasks(tasks)}
                </div>
            </button>
        `;
    }

    function renderDayView() {
        const date = fromYMD(selectedDate);
        const tasks = getTasksByDate(selectedDate);

        if (calendarCurrentRange) {
            calendarCurrentRange.textContent = "اليوم";
        }

        if (calendarSelectedLabel) {
            calendarSelectedLabel.textContent = formatDateLabel(date);
        }

        if (!calendarView) {
            return;
        }

        if (!tasks.length) {
            calendarView.innerHTML = `
                <div class="calendar-empty-box">
                    <div>
                        <strong>لا توجد مهام لهذا اليوم</strong>
                        <span>اضغط على زر إضافة مهمة لهذا اليوم لبدء رحلتك.</span>
                    </div>
                </div>
            `;
            return;
        }

        calendarView.innerHTML = `
            <div class="calendar-day-timeline">
                ${tasks.map(function (task) {
                    const noteHtml = task.patientNote
                        ? `<div class="selected-task-note">ملاحظة: ${escapeHTML(task.patientNote)}</div>`
                        : "";

                    const attachmentHtml = task.hasAttachment && task.attachmentUrl
                        ? `<a class="selected-task-attachment" href="${escapeHTML(task.attachmentUrl)}" target="_blank">عرض المرفق</a>`
                        : "";

                    return `
                        <div class="day-timeline-row">
                            <div class="day-timeline-time">${escapeHTML(task.timeLabel || "بدون وقت")}</div>

                            <div class="day-timeline-card ${escapeHTML(task.status)}">
                                <h4>${escapeHTML(task.title)}</h4>
                                <p>${escapeHTML(task.description)}</p>

                                ${noteHtml}
                                ${attachmentHtml}

                                <div class="selected-task-badges">
                                    <span class="source-badge ${task.source === "doctor" ? "doctor" : "self"}">${escapeHTML(task.sourceLabel)}</span>
                                    <span class="status-badge ${escapeHTML(task.status)}">${escapeHTML(task.statusLabel)}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join("")}
            </div>
        `;
    }

    function renderWeekView() {
        const cursor = fromYMD(selectedDate);
        const weekStart = startOfWeek(cursor);
        const weekEnd = endOfWeek(cursor);

        const dates = Array.from({ length: 7 }, function (_, index) {
            return addDays(weekStart, index);
        });

        const weekTasks = dates.flatMap(function (date) {
            return getTasksByDate(toYMD(date));
        });

        const completedWeekTasks = weekTasks.filter(function (task) {
            return task.status === "completed";
        }).length;

        const lateWeekTasks = weekTasks.filter(function (task) {
            return task.status === "late";
        }).length;

        if (calendarCurrentRange) {
            calendarCurrentRange.textContent = "الأسبوع";
        }

        if (calendarSelectedLabel) {
            calendarSelectedLabel.textContent = formatShortRange(weekStart, weekEnd);
        }

        if (!calendarView) {
            return;
        }

        calendarView.innerHTML = `
            <div class="week-compact-view">
                <div class="week-compact-head">
                    <div>
                        <span>نظرة الأسبوع</span>
                        <strong>${formatShortRange(weekStart, weekEnd)}</strong>
                    </div>

                    <div class="week-mini-stats">
                        <span>${weekTasks.length} مهام</span>
                        <span>${completedWeekTasks} مكتملة</span>
                        <span>${lateWeekTasks} متأخرة</span>
                    </div>
                </div>

                <div class="week-strip">
                    ${dates.map(function (date) {
                        const dateValue = toYMD(date);
                        const tasks = getTasksByDate(dateValue);
                        const dayStatus = getDayStatus(tasks);
                        const isToday = dateValue === normalizeDate(page.dataset.today);
                        const isSelected = dateValue === selectedDate;

                        const dots = tasks.slice(0, 4).map(function (task) {
                            const statusClass = task.status === "pending" && task.source === "doctor"
                                ? "doctor"
                                : task.status;

                            return `<i class="${escapeHTML(statusClass)}"></i>`;
                        }).join("");

                        return `
                            <button
                                type="button"
                                class="week-strip-day ${isToday ? "is-today today" : ""} ${isSelected ? "is-selected" : ""} has-${dayStatus}"
                                data-calendar-date="${dateValue}"
                                data-date="${dateValue}"
                                aria-selected="${isSelected ? "true" : "false"}"
                            >
                                <span class="week-day-name">${getArabicDayName(date)}</span>

                                <strong class="week-date-number">${date.getDate()}</strong>

                                <span class="week-empty-text ${tasks.length ? "has-tasks" : ""}">
                                    ${tasks.length ? tasks.length + " مهام" : "لا مهام"}
                                </span>

                                <div class="week-task-indicators">
                                    ${tasks.length ? dots : "<em></em>"}
                                </div>
                            </button>
                        `;
                    }).join("")}
                </div>
            </div>
        `;
    }

    function renderMonthView() {
        const cursor = calendarCursor;
        const monthStart = startOfMonth(cursor);
        const dates = getMonthGridDates(cursor);

        if (calendarCurrentRange) {
            calendarCurrentRange.textContent = "الشهر";
        }

        if (calendarSelectedLabel) {
            calendarSelectedLabel.textContent = `${arabicMonths[cursor.getMonth()]}، ${cursor.getFullYear()}`;
        }

        if (!calendarView) {
            return;
        }

        calendarView.innerHTML = `
            <div class="calendar-week-header">
                ${weekHeaderDays.map(function (day) {
                    return `<span>${day}</span>`;
                }).join("")}
            </div>

            <div class="calendar-month-grid">
                ${dates.map(function (date) {
                    return renderCalendarDay(date, {
                        outside: date.getMonth() !== monthStart.getMonth()
                    });
                }).join("")}
            </div>
        `;
    }

    function renderSelectedDayDetails() {
        const tasks = getTasksByDate(selectedDate);

        const completed = tasks.filter(function (task) {
            return task.status === "completed";
        }).length;

        const late = tasks.filter(function (task) {
            return task.status === "late";
        }).length;

        if (selectedDayTitle) {
            selectedDayTitle.textContent = formatDateLabel(selectedDate);
        }

        if (selectedDayTotal) {
            selectedDayTotal.textContent = String(tasks.length);
        }

        if (selectedDayCompleted) {
            selectedDayCompleted.textContent = String(completed);
        }

        if (selectedDayLate) {
            selectedDayLate.textContent = String(late);
        }

        if (!selectedDayTasks) {
            return;
        }

        if (!tasks.length) {
            selectedDayTasks.innerHTML = `
                <div class="selected-task-empty">
                    لا توجد مهام في هذا اليوم.<br>
                    يمكنك إضافة مهمة شخصية لهذا التاريخ.
                </div>
            `;
            return;
        }

        selectedDayTasks.innerHTML = tasks.map(function (task) {
            const noteHtml = task.patientNote
                ? `<div class="selected-task-note">ملاحظة: ${escapeHTML(task.patientNote)}</div>`
                : "";

            const attachmentHtml = task.hasAttachment && task.attachmentUrl
                ? `<a class="selected-task-attachment" href="${escapeHTML(task.attachmentUrl)}" target="_blank">عرض المرفق</a>`
                : "";

            return `
                <article class="selected-task-item">
                    <div class="selected-task-top">
                        <h4>${escapeHTML(task.title)}</h4>
                        <span class="selected-task-time">${escapeHTML(task.timeLabel || "بدون وقت")}</span>
                    </div>

                    <p>${escapeHTML(task.description)}</p>

                    ${noteHtml}
                    ${attachmentHtml}

                    <div class="selected-task-badges">
                        <span class="source-badge ${task.source === "doctor" ? "doctor" : "self"}">${escapeHTML(task.sourceLabel)}</span>
                        <span class="status-badge ${escapeHTML(task.status)}">${escapeHTML(task.statusLabel)}</span>
                    </div>
                </article>
            `;
        }).join("");
    }

    function renderCalendar() {
        if (calendarMode === "day") {
            renderDayView();
        }

        if (calendarMode === "week") {
            renderWeekView();
        }

        if (calendarMode === "month") {
            renderMonthView();
        }

        renderSelectedDayDetails();
    }

    function setSelectedDate(dateValue) {
        selectedDate = normalizeDate(dateValue);
        calendarCursor = fromYMD(selectedDate);

        if (taskDateInput) {
            taskDateInput.value = selectedDate;
        }

        renderCalendar();
    }

    function initCalendar() {
        calendarModes.forEach(function (button) {
            button.addEventListener("click", function () {
                calendarModes.forEach(function (item) {
                    item.classList.remove("is-active");
                });

                button.classList.add("is-active");

                calendarMode = button.dataset.calendarMode || "day";
                calendarCursor = fromYMD(selectedDate);

                renderCalendar();
            });
        });

        if (calendarView) {
            calendarView.addEventListener("click", function (event) {
                const dayButton = event.target.closest("[data-calendar-date], [data-date]");

                if (!dayButton) {
                    return;
                }

                const dateValue = dayButton.dataset.calendarDate || dayButton.dataset.date;

                if (!dateValue) {
                    return;
                }

                setSelectedDate(dateValue);
            });
        }

        if (calendarPrevBtn) {
            calendarPrevBtn.addEventListener("click", function () {
                if (calendarMode === "day") {
                    setSelectedDate(toYMD(addDays(fromYMD(selectedDate), -1)));
                    return;
                }

                if (calendarMode === "week") {
                    setSelectedDate(toYMD(addDays(fromYMD(selectedDate), -7)));
                    return;
                }

                calendarCursor = addMonths(calendarCursor, -1);
                selectedDate = toYMD(calendarCursor);
                renderCalendar();
            });
        }

        if (calendarNextBtn) {
            calendarNextBtn.addEventListener("click", function () {
                if (calendarMode === "day") {
                    setSelectedDate(toYMD(addDays(fromYMD(selectedDate), 1)));
                    return;
                }

                if (calendarMode === "week") {
                    setSelectedDate(toYMD(addDays(fromYMD(selectedDate), 7)));
                    return;
                }

                calendarCursor = addMonths(calendarCursor, 1);
                selectedDate = toYMD(calendarCursor);
                renderCalendar();
            });
        }

        renderCalendar();
    }

    function clearFieldError(field) {
        if (!field) {
            return;
        }

        const label = field.closest(".journey-field") || field.closest("label");

        if (!label) {
            return;
        }

        label.classList.remove("field-invalid");

        const oldMessage = label.querySelector(".field-error-message");

        if (oldMessage) {
            oldMessage.remove();
        }
    }

    function setFieldError(field, message) {
        if (!field) {
            return;
        }

        const label = field.closest(".journey-field") || field.closest("label");

        if (!label) {
            return;
        }

        label.classList.add("field-invalid");

        let errorMessage = label.querySelector(".field-error-message");

        if (!errorMessage) {
            errorMessage = document.createElement("small");
            errorMessage.className = "field-error-message";
            label.appendChild(errorMessage);
        }

        errorMessage.textContent = message || "هذا الحقل مطلوب.";
    }

    function validateSingleField(field) {
        clearFieldError(field);

        const label = field.closest(".journey-field") || field.closest("label");
        const customError = label ? label.dataset.error : "هذا الحقل مطلوب.";

        if (field.hasAttribute("required") && !String(field.value || "").trim()) {
            setFieldError(field, customError);
            return false;
        }

        if (field.hasAttribute("minlength")) {
            const minLength = Number(field.getAttribute("minlength"));

            if (String(field.value || "").trim().length < minLength) {
                setFieldError(field, `يجب أن يحتوي الحقل على ${minLength} أحرف على الأقل.`);
                return false;
            }
        }

        return true;
    }

    function validateJourneyForm() {
        if (!form) {
            return true;
        }

        const requiredFields = Array.from(form.querySelectorAll("[required]"));

        let isValid = true;
        let firstInvalidField = null;

        requiredFields.forEach(function (field) {
            const fieldIsValid = validateSingleField(field);

            if (!fieldIsValid) {
                isValid = false;

                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            }
        });

        if (firstInvalidField) {
            firstInvalidField.focus();
        }

        return isValid;
    }

    function resetFormErrors() {
        if (!form) {
            return;
        }

        Array.from(form.querySelectorAll("input, textarea, select")).forEach(function (field) {
            clearFieldError(field);
        });
    }

    function initTabs() {
        tabs.forEach(function (tab) {
            tab.addEventListener("click", function () {
                updateTabs(tab);
                applyFilter(tab.dataset.filter || "all");
            });
        });

        if (showAllTasksFromEmpty) {
            showAllTasksFromEmpty.addEventListener("click", function () {
                const allTab = tabs.find(function (tab) {
                    return tab.dataset.filter === "all";
                });

                if (allTab) {
                    updateTabs(allTab);
                }

                applyFilter("all");
            });
        }
    }

    function initModal() {
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        if (noteModal && noteModal.parentElement !== document.body) {
            document.body.appendChild(noteModal);
        }

        if (modal) {
            openModalButtons.forEach(function (button) {
                button.addEventListener("click", openModal);
            });

            Array.from(modal.querySelectorAll("[data-close-modal]")).forEach(function (button) {
                button.addEventListener("click", closeModal);
            });
        }

        if (noteModal) {
            Array.from(noteModal.querySelectorAll("[data-close-note-modal]")).forEach(function (button) {
                button.addEventListener("click", closeNoteModal);
            });
        }

        document.addEventListener("keydown", function (event) {
            if (event.key !== "Escape") {
                return;
            }

            if (noteModal && noteModal.classList.contains("is-open")) {
                closeNoteModal();
            }

            if (modal && modal.classList.contains("is-open")) {
                closeModal();
            }

            closeMissionMenus();
        });
    }

    function initForm() {
        if (!form) {
            return;
        }

        form.setAttribute("novalidate", "novalidate");

        const fields = Array.from(form.querySelectorAll("input, textarea, select"));

        fields.forEach(function (field) {
            field.addEventListener("input", function () {
                if (field.closest(".field-invalid")) {
                    validateSingleField(field);
                }
            });

            field.addEventListener("change", function () {
                if (field.closest(".field-invalid")) {
                    validateSingleField(field);
                }
            });
        });

        form.addEventListener("submit", function (event) {
            if (!validateJourneyForm()) {
                event.preventDefault();
                showToast("راجع الحقول المحددة بالأحمر.");
            }
        });
    }

    function closeMissionMenus(exceptWrap = null) {
        document.querySelectorAll(".mission-menu-wrap.is-open").forEach(function (wrap) {
            if (wrap !== exceptWrap) {
                wrap.classList.remove("is-open");
            }
        });
    }

    function initTaskActions() {
        document.addEventListener("click", function (event) {
            const menuToggle = event.target.closest(".mission-menu-toggle");

            if (menuToggle) {
                event.preventDefault();
                event.stopPropagation();

                const currentMenu = menuToggle.closest(".mission-menu-wrap");

                if (!currentMenu) {
                    return;
                }

                const shouldOpen = !currentMenu.classList.contains("is-open");

                closeMissionMenus(currentMenu);
                currentMenu.classList.toggle("is-open", shouldOpen);

                return;
            }

            const noteButton = event.target.closest("[data-note-task]");

            if (noteButton) {
                event.preventDefault();
                event.stopPropagation();

                closeMissionMenus();
                openNoteModal(noteButton);

                return;
            }

            const uploadButton = event.target.closest(".upload-task");

            if (uploadButton) {
                event.preventDefault();
                event.stopPropagation();

                const uploadForm = uploadButton.closest(".task-upload-form");
                const fileInput = uploadForm ? uploadForm.querySelector(".task-attachment-input") : null;

                if (fileInput) {
                    fileInput.click();
                } else {
                    showToast("لم يتم العثور على حقل رفع الملف.");
                }

                closeMissionMenus();

                return;
            }

            if (!event.target.closest(".mission-menu-wrap") && !event.target.closest(".journey-note-modal")) {
                closeMissionMenus();
            }
        });

        document.addEventListener("change", function (event) {
            const fileInput = event.target.closest(".task-attachment-input");

            if (!fileInput) {
                return;
            }

            const uploadForm = fileInput.closest(".task-upload-form");

            if (!uploadForm) {
                return;
            }

            if (!fileInput.files || !fileInput.files.length) {
                return;
            }

            uploadForm.submit();
        });
    }

    syncPageThemeWithMainTheme();
    initTabs();
    initCalendar();
    initModal();
    initForm();
    initTaskActions();
    updateProgress();

    const defaultTab = tabs.find(function (tab) {
        return tab.dataset.filter === "all";
    });

    if (defaultTab) {
        updateTabs(defaultTab);
    }

    applyFilter(activeFilter);
})();