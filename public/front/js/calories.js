document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("[data-cal-form]");
    const fileInput = document.querySelector("[data-cal-file]");
    const fileLabel = document.querySelector("[data-cal-file-label]");
    const preview = document.querySelector("[data-cal-preview]");
    const tabButtons = document.querySelectorAll("[data-cal-tab]");
    const panels = document.querySelectorAll("[data-cal-panel]");
    const textareas = document.querySelectorAll("[data-cal-autogrow]");

    tabButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const target = button.getAttribute("data-cal-tab");

            tabButtons.forEach(function (item) {
                item.classList.remove("is-active");
            });

            panels.forEach(function (panel) {
                panel.classList.toggle("is-active", panel.getAttribute("data-cal-panel") === target);
            });

            button.classList.add("is-active");
        });
    });

    if (fileInput && fileLabel) {
        fileInput.addEventListener("change", function () {
            const file = fileInput.files && fileInput.files[0];

            if (!file) {
                fileLabel.textContent = "ارفع صورة الوجبة";

                if (preview) {
                    preview.hidden = true;
                    preview.style.backgroundImage = "";
                }

                return;
            }

            fileLabel.textContent = file.name.length > 24
                ? file.name.slice(0, 24) + "..."
                : file.name;

            if (preview && file.type && file.type.startsWith("image/")) {
                const reader = new FileReader();

                reader.onload = function (event) {
                    preview.hidden = false;
                    preview.style.backgroundImage = "url('" + event.target.result + "')";
                };

                reader.readAsDataURL(file);
            }
        });
    }

    textareas.forEach(function (textarea) {
        function resizeTextarea() {
            textarea.style.height = "auto";
            textarea.style.height = Math.min(textarea.scrollHeight, 180) + "px";
        }

        textarea.addEventListener("input", resizeTextarea);
        resizeTextarea();
    });

    const loadingOverlay = document.querySelector("[data-cal-loading]");
    const loadingText = document.querySelector("[data-cal-loading-text]");
    const loadingStages = [
        "جاري تحليل الصورة...",
        "التعرف على المكوّنات...",
        "تقدير الكميات...",
        "حساب القيم الغذائية...",
        "توليد التحليل الصحي...",
        "تجهيز التوصيات...",
    ];

    if (form) {
        form.addEventListener("submit", function (event) {
            const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
            const text = form.querySelector("textarea[name='meal_text']");
            const hasText = text && text.value.trim().length > 0;

            if (!hasFile && !hasText) {
                event.preventDefault();

                if (window.showPatientToast) {
                    window.showPatientToast("ارفع صورة الوجبة أو اكتب وصفًا قبل التحليل.");
                } else {
                    alert("ارفع صورة الوجبة أو اكتب وصفًا قبل التحليل.");
                }

                return;
            }

            const submitButton = form.querySelector("button[type='submit']");

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add("is-loading");
            }

            if (loadingOverlay) {
                loadingOverlay.hidden = false;

                let stageIndex = 0;

                if (loadingText) {
                    loadingText.textContent = loadingStages[0];
                }

                setInterval(function () {
                    stageIndex = (stageIndex + 1) % loadingStages.length;

                    if (loadingText) {
                        loadingText.textContent = loadingStages[stageIndex];
                    }
                }, 1800);
            }
        });
    }

    document.querySelectorAll("[data-meal-edit-toggle]").forEach(function (button) {
        button.addEventListener("click", function () {
            const row = button.closest(".cal-meal-row");
            const editForm = row ? row.nextElementSibling : null;

            if (editForm && editForm.hasAttribute("data-meal-edit-form")) {
                editForm.hidden = !editForm.hidden;
            }
        });
    });

    if (window.lucide && typeof window.lucide.createIcons === "function") {
        window.lucide.createIcons();
    }
});
