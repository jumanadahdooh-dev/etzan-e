document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("doctorApplyForm");
  const panes = document.querySelectorAll(".doctor-pane");
  const steps = document.querySelectorAll(".doctor-step");
  const nextBtn = document.getElementById("nextDoctorBtn");
  const prevBtn = document.getElementById("prevDoctorBtn");
  const progressFill = document.getElementById("doctorProgressFill");

  const reviewName = document.getElementById("reviewName");
  const reviewEmail = document.getElementById("reviewEmail");
  const reviewPhone = document.getElementById("reviewPhone");
  const reviewWorkplace = document.getElementById("reviewWorkplace");
  const reviewSpecialty = document.getElementById("reviewSpecialty");
  const reviewExperience = document.getElementById("reviewExperience");
  const reviewLicense = document.getElementById("reviewLicense");

  const docsProgressFill = document.getElementById("docsProgressFill");
  const docsProgressText = document.getElementById("docsProgressText");

  const emailInput = document.getElementById("doctorEmail");
  let currentStep = 1;
  const totalSteps = 4;
  let emailCheckTimer = null;
  let emailAvailability = {
    checked: false,
    valid: false,
    value: "",
    message: "",
  };

  function updateStepper() {
    steps.forEach((step) => {
      const stepNumber = Number(step.dataset.step);
      step.classList.remove("is-active", "is-done");

      if (stepNumber === currentStep) {
        step.classList.add("is-active");
      } else if (stepNumber < currentStep) {
        step.classList.add("is-done");
      }
    });

    if (progressFill) {
      const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
      progressFill.style.width = `${progress}%`;
    }

    if (prevBtn) prevBtn.disabled = currentStep === 1;
    if (nextBtn) nextBtn.textContent = currentStep === totalSteps ? "إرسال الطلب" : "التالي";
  }

  function showCurrentPane() {
    panes.forEach((pane) => {
      pane.classList.remove("is-active");
      if (Number(pane.dataset.pane) === currentStep) {
        pane.classList.add("is-active");
      }
    });
  }

  function clearErrors() {
    document.querySelectorAll(".field-error-js").forEach((el) => el.remove());
    document.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
  }

  function clearFieldError(targetElement) {
    if (!targetElement) return;

    const formField =
      targetElement.closest(".form-field") ||
      targetElement.closest("[data-upload-tile]") ||
      targetElement.closest(".agreement-check") ||
      targetElement;

    const visualTargets = [
      formField.querySelector(".input-shell"),
      formField.querySelector(".textarea-shell"),
      formField.querySelector(".specialty-pills"),
      formField.querySelector(".upload-tile"),
      formField.querySelector(".agreement-check"),
    ].filter(Boolean);

    visualTargets.forEach((el) => el.classList.remove("is-invalid"));
    formField.querySelector(".field-error-js")?.remove();
  }

  function showError(targetElement, message) {
    if (!targetElement) return;

    const formField =
      targetElement.closest(".form-field") ||
      targetElement.closest("[data-upload-tile]") ||
      targetElement.closest(".agreement-check") ||
      targetElement;

    let visualTarget = null;

    if (targetElement.classList.contains("specialty-pills")) {
      visualTarget = targetElement;
    } else {
      visualTarget =
        formField.querySelector(".input-shell") ||
        formField.querySelector(".textarea-shell") ||
        formField.querySelector(".upload-tile") ||
        formField.querySelector(".agreement-check") ||
        formField;
    }

    visualTarget.classList.add("is-invalid");
    formField.querySelector(".field-error-js")?.remove();

    const error = document.createElement("small");
    error.className = "field-error field-error-js";
    error.textContent = message;
    formField.appendChild(error);
  }

  function validateEmailFormat(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  async function checkEmailAvailability(email) {
    try {
      const response = await fetch("/join-doctor/check-email", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
          "Accept": "application/json",
        },
        body: JSON.stringify({ email }),
      });

      const data = await response.json();

      if (!response.ok) {
        return {
          valid: false,
          message: "تعذر التحقق من البريد الإلكتروني الآن.",
        };
      }

      return data;
    } catch (error) {
      return {
        valid: false,
        message: "حدث خطأ أثناء التحقق من البريد الإلكتروني.",
      };
    }
  }

  async function validateEmailLive() {
    const value = emailInput.value.trim();
    clearFieldError(emailInput);

    emailAvailability = {
      checked: false,
      valid: false,
      value,
      message: "",
    };

    if (value === "") {
      showError(emailInput, "البريد الإلكتروني مطلوب.");
      return false;
    }

    if (!validateEmailFormat(value)) {
      showError(emailInput, "صيغة البريد الإلكتروني غير صحيحة.");
      return false;
    }

    const result = await checkEmailAvailability(value);

    emailAvailability = {
      checked: true,
      valid: result.valid,
      value,
      message: result.message || "",
    };

    if (!result.valid) {
      showError(emailInput, result.message || "هذا البريد غير متاح.");
      return false;
    }

    clearFieldError(emailInput);
    return true;
  }

function updateReview() {
  const specialty = document.querySelector('input[name="specialty"]:checked');

  const profilePhotoInput = document.querySelector('input[name="profile_photo"]');
  const licenseFileInput = document.querySelector('input[name="license_file"]');
  const cvFileInput = document.querySelector('input[name="cv_file"]');

  document.getElementById("reviewName").textContent =
    document.getElementById("doctorName")?.value.trim() || "—";

  document.getElementById("reviewEmail").textContent =
    document.getElementById("doctorEmail")?.value.trim() || "—";

  document.getElementById("reviewPhone").textContent =
    document.getElementById("doctorPhone")?.value.trim() || "—";

  document.getElementById("reviewWorkplace").textContent =
    document.getElementById("doctorWorkplace")?.value.trim() || "—";

  document.getElementById("reviewSpecialty").textContent =
    specialty ? specialty.value : "—";

  document.getElementById("reviewExperience").textContent =
    document.getElementById("doctorExperience")?.value.trim() || "—";

  document.getElementById("reviewLicense").textContent =
    document.getElementById("doctorLicense")?.value.trim() || "—";

  document.getElementById("reviewBio").textContent =
    document.getElementById("doctorBio")?.value.trim() || "—";

  document.getElementById("reviewProfilePhoto").textContent =
    profilePhotoInput?.files?.length ? profilePhotoInput.files[0].name : "لم يتم إرفاق ملف";

  document.getElementById("reviewLicenseFile").textContent =
    licenseFileInput?.files?.length ? licenseFileInput.files[0].name : "لم يتم إرفاق ملف";

  document.getElementById("reviewCvFile").textContent =
    cvFileInput?.files?.length ? cvFileInput.files[0].name : "لم يتم إرفاق ملف";
}
  function updateDocsStatus() {
    const uploadInputs = document.querySelectorAll("[data-upload-input]");
    let filled = 0;

    uploadInputs.forEach((input) => {
      if (input.files && input.files.length > 0) filled++;
    });

    if (docsProgressFill) {
      docsProgressFill.style.width = `${(filled / uploadInputs.length) * 100}%`;
    }

    if (docsProgressText) {
      docsProgressText.textContent = `${filled} / ${uploadInputs.length} مكتمل`;
    }
  }

  async function validateStep(step) {
    clearErrors();

    if (step === 1) {
      const doctorName = document.getElementById("doctorName");
      const doctorEmail = document.getElementById("doctorEmail");
      const doctorPhone = document.getElementById("doctorPhone");
      const doctorWorkplace = document.getElementById("doctorWorkplace");

      let valid = true;

      if (!doctorName.value.trim()) {
        showError(doctorName, "الاسم الكامل مطلوب.");
        valid = false;
      }

      if (!doctorPhone.value.trim()) {
        showError(doctorPhone, "رقم الهاتف مطلوب.");
        valid = false;
      }

      if (!doctorWorkplace.value.trim()) {
        showError(doctorWorkplace, "مكان العمل الحالي مطلوب.");
        valid = false;
      }

      const emailValid = await validateEmailLive();
      if (!emailValid) valid = false;

      return valid;
    }

    if (step === 2) {
      const specialty = document.querySelector('input[name="specialty"]:checked');
      const specialtyContainer = document.querySelector(".specialty-pills");
      const doctorExperience = document.getElementById("doctorExperience");
      const doctorLicense = document.getElementById("doctorLicense");
      const doctorBio = document.getElementById("doctorBio");

      let valid = true;

      if (!specialty) {
        showError(specialtyContainer, "اختيار التخصص مطلوب.");
        valid = false;
      }

      if (!doctorExperience.value.trim()) {
        showError(doctorExperience, "سنوات الخبرة مطلوبة.");
        valid = false;
      }

      if (!doctorLicense.value.trim()) {
        showError(doctorLicense, "رقم الترخيص مطلوب.");
        valid = false;
      }

      if (!doctorBio.value.trim()) {
        showError(doctorBio, "النبذة المهنية مطلوبة.");
        valid = false;
      }

      return valid;
    }

    if (step === 3) {
      const profilePhoto = document.querySelector('input[name="profile_photo"]');
      const licenseFile = document.querySelector('input[name="license_file"]');

      let valid = true;

      if (!profilePhoto || !profilePhoto.files || profilePhoto.files.length === 0) {
        showError(profilePhoto.closest("[data-upload-tile]"), "الصورة الشخصية مطلوبة.");
        valid = false;
      }

      if (!licenseFile || !licenseFile.files || licenseFile.files.length === 0) {
        showError(licenseFile.closest("[data-upload-tile]"), "إثبات مزاولة المهنة مطلوب.");
        valid = false;
      }

      return valid;
    }

    if (step === 4) {
      const agreement = document.getElementById("doctorAgreement");

      if (!agreement.checked) {
        showError(agreement.closest(".agreement-check"), "يجب تأكيد صحة البيانات قبل الإرسال.");
        return false;
      }

      return true;
    }

    return true;
  }

  if (emailInput) {
    emailInput.addEventListener("input", function () {
      const value = this.value.trim();

      emailAvailability = {
        checked: false,
        valid: false,
        value,
        message: "",
      };

      clearFieldError(this);

      if (emailCheckTimer) {
        clearTimeout(emailCheckTimer);
      }

      if (value === "") return;

      if (!validateEmailFormat(value)) {
        showError(this, "صيغة البريد الإلكتروني غير صحيحة.");
        return;
      }

      emailCheckTimer = setTimeout(async () => {
        await validateEmailLive();
      }, 500);
    });

    emailInput.addEventListener("blur", async function () {
      await validateEmailLive();
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener("click", async function () {
      const isValid = await validateStep(currentStep);
      if (!isValid) return;

      if (currentStep < totalSteps) {
        currentStep++;
        showCurrentPane();
        updateStepper();

        if (currentStep === 4) {
          updateReview();
        }

        window.scrollTo({ top: 0, behavior: "smooth" });
      } else {
        form.submit();
      }
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener("click", function () {
      clearErrors();

      if (currentStep > 1) {
        currentStep--;
        showCurrentPane();
        updateStepper();
        window.scrollTo({ top: 0, behavior: "smooth" });
      }
    });
  }

  const uploadInputs = document.querySelectorAll("[data-upload-input]");
  uploadInputs.forEach((input) => {
    input.addEventListener("change", function () {
      const tile = this.closest("[data-upload-tile]");
      const nameBox = tile.querySelector("[data-upload-name]");

      if (this.files && this.files.length > 0) {
        nameBox.textContent = this.files[0].name;
        tile.classList.remove("is-invalid");
      } else {
        nameBox.textContent = "لم يتم إرفاق ملف بعد";
      }

      updateDocsStatus();
    });
  });

  const liveInputs = document.querySelectorAll(
    "#doctorName, #doctorPhone, #doctorWorkplace, #doctorExperience, #doctorLicense, #doctorBio"
  );

  liveInputs.forEach((input) => {
    input.addEventListener("input", function () {
      if (this.value.trim() !== "") {
        clearFieldError(this);
      }
    });
  });

  document.querySelectorAll('input[name="specialty"]').forEach((radio) => {
    radio.addEventListener("change", function () {
      const pills = document.querySelector(".specialty-pills");
      const formField = pills?.closest(".form-field");

      pills?.classList.remove("is-invalid");
      formField?.querySelector(".field-error-js")?.remove();
    });
  });

  const agreement = document.getElementById("doctorAgreement");
  if (agreement) {
    agreement.addEventListener("change", function () {
      const wrapper = this.closest(".agreement-check");
      wrapper?.classList.remove("is-invalid");
      wrapper?.querySelector(".field-error-js")?.remove();
    });
  }

  showCurrentPane();
  updateStepper();
  updateDocsStatus();
});
