(function () {
    "use strict";

    function ready(callback) {
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", callback);
        } else {
            callback();
        }
    }

    function refreshIcons() {
        if (window.lucide && typeof window.lucide.createIcons === "function") {
            window.lucide.createIcons();
        }
    }

    function showToast(message) {
        if (!message) return;

        var container = document.querySelector("[data-toast-container]");

        if (!container) {
            container = document.createElement("div");
            container.className = "patient-toast";
            container.setAttribute("data-toast-container", "");
            document.body.appendChild(container);
        }

        var toast = document.createElement("div");
        toast.className = "toast-item";
        toast.textContent = message;

        container.appendChild(toast);

        requestAnimationFrame(function () {
            toast.classList.add("is-visible");
        });

        setTimeout(function () {
            toast.classList.remove("is-visible");

            setTimeout(function () {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 260);
        }, 3500);
    }

    function initDoctorModal() {
        var modal = document.querySelector("[data-doctor-modal]");
        if (!modal) return;

        var detailButtons = Array.from(document.querySelectorAll("[data-doctor-detail]"));
        var closeButtons = Array.from(document.querySelectorAll("[data-doctor-modal-close]"));

        var modalAvatar = modal.querySelector("[data-modal-avatar]");
        var modalName = modal.querySelector("[data-modal-name]");
        var modalSpecialty = modal.querySelector("[data-modal-specialty]");
        var modalScore = modal.querySelector("[data-modal-score]");
        var modalReason = modal.querySelector("[data-modal-reason]");
        var modalBio = modal.querySelector("[data-modal-bio]");
        var modalRating = modal.querySelector("[data-modal-rating]");
        var modalExperience = modal.querySelector("[data-modal-experience]");
        var modalConsultation = modal.querySelector("[data-modal-consultation]");
        var modalArticles = modal.querySelector("[data-modal-articles]");

        function openModal(button) {
            if (modalAvatar) modalAvatar.src = button.dataset.avatar || "";
            if (modalName) modalName.textContent = button.dataset.name || "الطبيب";
            if (modalSpecialty) modalSpecialty.textContent = button.dataset.specialty || "استشاري صحي";
            if (modalScore) modalScore.textContent = (button.dataset.score || "—") + "% تطابق";
            if (modalReason) modalReason.textContent = button.dataset.reason || "مناسب لحالتك الصحية.";
            if (modalBio) modalBio.textContent = button.dataset.bio || "طبيب مختص في المتابعة الصحية.";
            if (modalRating) modalRating.textContent = button.dataset.rating || "—";
            if (modalExperience) modalExperience.textContent = (button.dataset.experience || "—") + " سنوات";
            if (modalConsultation) modalConsultation.textContent = button.dataset.consultation || "—";

            if (modalArticles) {
                modalArticles.innerHTML = "";

                var articles = [];

                try {
                    articles = JSON.parse(button.dataset.articles || "[]");
                } catch (error) {
                    articles = [];
                }

                if (!articles.length) {
                    var empty = document.createElement("div");
                    empty.textContent = "لا توجد مقالات منشورة لهذا الطبيب بعد.";
                    modalArticles.appendChild(empty);
                } else {
                    articles.forEach(function (article) {
                        var item = document.createElement("div");
                        item.textContent = article.title || "مقال صحي";
                        modalArticles.appendChild(item);
                    });
                }
            }

            modal.classList.add("is-open");
            modal.setAttribute("aria-hidden", "false");

            refreshIcons();
        }

        function closeModal() {
            modal.classList.remove("is-open");
            modal.setAttribute("aria-hidden", "true");
        }

        detailButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                openModal(button);
            });
        });

        closeButtons.forEach(function (button) {
            button.addEventListener("click", closeModal);
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && modal.classList.contains("is-open")) {
                closeModal();
            }
        });
    }

    ready(function () {
        initDoctorModal();

        var wizard = document.querySelector("[data-health-profile-wizard]");
        if (!wizard) return;

        var panels = Array.from(wizard.querySelectorAll("[data-pc-step]"));
        var tabs = Array.from(wizard.querySelectorAll("[data-pc-goto]"));
        var nextBtn = wizard.querySelector("[data-pc-next]");
        var backBtn = wizard.querySelector("[data-pc-back]");
        var submitBtn = wizard.querySelector("[data-pc-submit]");
        var progress = wizard.querySelector("[data-pc-progress]");
        var percent = wizard.querySelector("[data-pc-percent]");
        var heightInput = wizard.querySelector("[data-height-input]");
        var weightInput = wizard.querySelector("[data-weight-input]");
        var bmiValue = wizard.querySelector("[data-bmi-value]");
        var bmiNote = wizard.querySelector("[data-bmi-note]");
        var smartTitle = document.querySelector("[data-smart-title]");
        var smartCopy = document.querySelector("[data-smart-copy]");
        var avatarInput = wizard.querySelector("[data-avatar-input]");
        var avatarPreview = wizard.querySelector("[data-avatar-preview]");
        var avatarPanel = wizard.querySelector(".pc-avatar-panel");

        var current = 0;

        function setStep(index) {
            current = Math.max(0, Math.min(index, panels.length - 1));

            panels.forEach(function (panel, panelIndex) {
                panel.classList.toggle("is-active", panelIndex === current);
            });

            tabs.forEach(function (tab, tabIndex) {
                tab.classList.toggle("is-active", tabIndex === current);
                tab.classList.toggle("is-done", tabIndex < current);
            });

            var progressValue = Math.round(((current + 1) / panels.length) * 100);

            if (progress) {
                progress.style.width = progressValue + "%";
            }

            if (percent) {
                percent.textContent = progressValue + "%";
            }

            if (backBtn) {
                backBtn.disabled = current === 0;
            }

            if (nextBtn && submitBtn) {
                var isLast = current === panels.length - 1;
                nextBtn.classList.toggle("d-none", isLast);
                submitBtn.classList.toggle("d-none", !isLast);
            }

            refreshIcons();
        }

        function clearInvalid(panel) {
            panel.querySelectorAll(".pc-invalid, .pc-invalid-shake").forEach(function (element) {
                element.classList.remove("pc-invalid", "pc-invalid-shake");
            });
        }

        function markInvalid(element) {
            if (!element) return;

            element.classList.add("pc-invalid", "pc-invalid-shake");

            setTimeout(function () {
                element.classList.remove("pc-invalid-shake");
            }, 320);
        }

        function validateStep() {
            var panel = panels[current];
            if (!panel) return true;

            clearInvalid(panel);

            var required = Array.from(panel.querySelectorAll("[required]"));
            var valid = true;
            var firstInvalid = null;

            required.forEach(function (field) {
                if (field.type === "radio") {
                    var group = Array.from(panel.querySelectorAll('input[name="' + field.name + '"]'));
                    var checked = group.some(function (item) {
                        return item.checked;
                    });

                    if (!checked) {
                        valid = false;

                        group.forEach(function (item) {
                            var card = item.closest(".pc-goal-option");

                            if (card) {
                                markInvalid(card);

                                if (!firstInvalid) {
                                    firstInvalid = card;
                                }
                            }
                        });
                    }

                    return;
                }

                if (!field.value) {
                    valid = false;

                    var wrapper = field.closest(".pc-input") || field.closest(".pc-field") || field;

                    markInvalid(wrapper);

                    if (!firstInvalid) {
                        firstInvalid = wrapper;
                    }
                }
            });

            if (!valid) {
                showToast("أكمل الحقول المطلوبة أولاً");

                if (firstInvalid) {
                    firstInvalid.scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                    });
                }
            }

            return valid;
        }

        function updateBmi() {
            if (!heightInput || !weightInput || !bmiValue || !bmiNote) return;

            var height = parseFloat(heightInput.value);
            var weight = parseFloat(weightInput.value);

            if (!height || !weight) {
                bmiValue.textContent = "—";
                bmiNote.textContent = "أدخل الطول والوزن ليظهر المؤشر هنا.";
                return;
            }

            var bmi = weight / Math.pow(height / 100, 2);
            var rounded = Math.round(bmi * 10) / 10;

            bmiValue.textContent = rounded;

            if (rounded < 18.5) {
                bmiNote.textContent = "المؤشر أقل من الطبيعي وقد تحتاج إلى متابعة غذائية داعمة.";
            } else if (rounded < 25) {
                bmiNote.textContent = "المؤشر ضمن النطاق الطبيعي تقريباً.";
            } else if (rounded < 30) {
                bmiNote.textContent = "المؤشر أعلى من الطبيعي قليلاً ويمكن تنظيمه بخطة مناسبة.";
            } else {
                bmiNote.textContent = "المؤشر مرتفع، والمتابعة مع طبيب مختص ستفيدك بشكل أكبر.";
            }
        }

        function selectedGoal() {
            var goal = wizard.querySelector("[data-health-goal]:checked");
            return goal ? goal.value : "";
        }

        function selectedConditions() {
            return Array.from(wizard.querySelectorAll("[data-condition-input]:checked")).map(function (input) {
                return input.value;
            });
        }

        function updateSmartPreview() {
            if (!smartTitle || !smartCopy) return;

            var goal = selectedGoal();
            var conditions = selectedConditions();

            if (conditions.includes("diabetes") || goal === "diabetes_management") {
                smartTitle.textContent = "سيتم ترشيح مختص مناسب لتنظيم السكر";
                smartCopy.textContent = "سنرتب الأطباء أصحاب الخبرة في التغذية العلاجية للسكري وتنظيم الوجبات.";
                return;
            }

            if (conditions.includes("hypertension") || goal === "hypertension_management") {
                smartTitle.textContent = "سيتم ترشيح مختص مناسب لتنظيم الضغط";
                smartCopy.textContent = "سيتم تفضيل الأطباء المناسبين لمتابعة الضغط والعادات الغذائية المرتبطة به.";
                return;
            }

            if (conditions.includes("cholesterol") || goal === "cholesterol_management") {
                smartTitle.textContent = "سيتم ترشيح مختص مناسب لتحسين الكوليسترول";
                smartCopy.textContent = "سيتم ترتيب الأطباء حسب خبرتهم في تحسين صحة القلب وتنظيم الدهون.";
                return;
            }

            if (conditions.includes("pcos")) {
                smartTitle.textContent = "سيتم ترشيح مختص مناسب لحالتك";
                smartCopy.textContent = "سنراعي الحالة الصحية والأهداف للوصول إلى متابعة أكثر ملاءمة.";
                return;
            }

            if (conditions.includes("pregnancy")) {
                smartTitle.textContent = "سيتم ترشيح طبيب مناسب للحمل أو الرضاعة";
                smartCopy.textContent = "سنرتب الأطباء حسب ملاءمتهم للتغذية الآمنة خلال الحمل أو الرضاعة.";
                return;
            }

            if (goal === "weight_loss") {
                smartTitle.textContent = "سيتم ترشيح طبيب مناسب لخسارة الوزن";
                smartCopy.textContent = "سيتم اقتراح أطباء يساعدونك على الوصول لهدفك بطريقة صحية وآمنة.";
                return;
            }

            if (goal === "healthy_lifestyle") {
                smartTitle.textContent = "سيتم ترشيح طبيب مناسب لتحسين نمط الحياة";
                smartCopy.textContent = "سنقترح متابعة تساعدك في بناء عادات صحية قابلة للاستمرار.";
                return;
            }

            smartTitle.textContent = "سنقترح الأطباء الأنسب بعد الحفظ";
            smartCopy.textContent = "كلما كانت بياناتك الصحية أوضح، أصبحت التوصيات وترتيب الأطباء أدق وأكثر ملاءمة.";
        }

        function handleNoneCondition() {
            var inputs = Array.from(wizard.querySelectorAll("[data-condition-input]"));
            var noneInput = inputs.find(function (input) {
                return input.value === "none";
            });

            inputs.forEach(function (input) {
                input.addEventListener("change", function () {
                    if (!noneInput) return;

                    if (input.value === "none" && input.checked) {
                        inputs.forEach(function (other) {
                            if (other.value !== "none") {
                                other.checked = false;
                            }
                        });
                    }

                    if (input.value !== "none" && input.checked) {
                        noneInput.checked = false;
                    }

                    updateSmartPreview();
                });
            });
        }

        function initAvatarPreview() {
            if (!avatarInput || !avatarPreview) return;

            avatarInput.addEventListener("change", function () {
                var file = avatarInput.files && avatarInput.files[0];

                if (!file) return;

                if (!file.type.startsWith("image/")) {
                    showToast("الملف المختار يجب أن يكون صورة");
                    avatarInput.value = "";
                    return;
                }

                if (file.size > 2 * 1024 * 1024) {
                    showToast("حجم الصورة يجب أن يكون أقل من 2MB");
                    avatarInput.value = "";
                    return;
                }

                if (avatarPanel) {
                    avatarPanel.classList.remove("pc-invalid");
                }

                var reader = new FileReader();

                reader.onload = function (event) {
                    avatarPreview.innerHTML = "";

                    var img = document.createElement("img");
                    img.src = event.target.result;
                    img.alt = "صورة المريض";

                    avatarPreview.appendChild(img);
                };

                reader.readAsDataURL(file);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener("click", function () {
                if (validateStep()) {
                    setStep(current + 1);
                }
            });
        }

        if (backBtn) {
            backBtn.addEventListener("click", function () {
                setStep(current - 1);
            });
        }

        tabs.forEach(function (tab) {
            tab.addEventListener("click", function () {
                var target = parseInt(tab.getAttribute("data-pc-goto"), 10);

                if (target <= current || validateStep()) {
                    setStep(target);
                }
            });
        });

        wizard.querySelectorAll("[data-health-goal]").forEach(function (input) {
            input.addEventListener("change", updateSmartPreview);
        });

        if (heightInput) {
            heightInput.addEventListener("input", updateBmi);
        }

        if (weightInput) {
            weightInput.addEventListener("input", updateBmi);
        }

        handleNoneCondition();
        initAvatarPreview();
        updateBmi();
        updateSmartPreview();
        setStep(0);
    });
})();


document.addEventListener('DOMContentLoaded', function () {
    const modal = document.querySelector('[data-doctor-pending-modal]');
    const openButtons = document.querySelectorAll('[data-doctor-pending-open]');
    const closeButtons = document.querySelectorAll('[data-doctor-pending-close]');

    if (!modal) {
        return;
    }

    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    openButtons.forEach(function (button) {
        button.addEventListener('click', openModal);
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
});
