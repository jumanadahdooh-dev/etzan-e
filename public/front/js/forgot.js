document.addEventListener("DOMContentLoaded", function () {
  const toggleButtons = document.querySelectorAll(".toggle-pass");

  toggleButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const input = this.parentElement.querySelector("input");
      const icon = this.querySelector("i");

      if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    });
  });

  const codeInputs = document.querySelectorAll(".code-digit");
  const fullCode = document.getElementById("fullCode");
  const verifyForm = fullCode ? fullCode.closest("form") : null;

  function updateCodeValue() {
    if (!fullCode) return;
    fullCode.value = [...codeInputs].map((input) => input.value).join("");
  }

  if (codeInputs.length && fullCode) {
    const oldCode = fullCode.value || "";

    if (oldCode.length === 4) {
      codeInputs.forEach((input, index) => {
        input.value = oldCode[index] || "";
      });
    }

    codeInputs.forEach((input, index) => {
      input.addEventListener("input", function () {
        this.value = this.value.replace(/[^0-9]/g, "").slice(0, 1);
        updateCodeValue();

        if (this.value && index < codeInputs.length - 1) {
          codeInputs[index + 1].focus();
        }
      });

      input.addEventListener("keydown", function (e) {
        if (e.key === "Backspace" && !this.value && index > 0) {
          codeInputs[index - 1].focus();
        }
      });

      input.addEventListener("paste", function (e) {
        e.preventDefault();

        const pasted = (e.clipboardData || window.clipboardData)
          .getData("text")
          .replace(/\D/g, "")
          .slice(0, 4);

        if (!pasted) return;

        codeInputs.forEach((box, i) => {
          box.value = pasted[i] || "";
        });

        updateCodeValue();

        const nextIndex = Math.min(pasted.length, codeInputs.length - 1);
        codeInputs[nextIndex].focus();
      });
    });

    if (verifyForm) {
      verifyForm.addEventListener("submit", function () {
        updateCodeValue();
      });
    }
  }

  const newPassword = document.getElementById("newPassword");
  const strengthFill = document.getElementById("strengthFill");

  if (newPassword && strengthFill) {
    newPassword.addEventListener("input", function () {
      const value = this.value;
      let strength = 0;

      if (value.length >= 6) strength += 30;
      if (/[A-Z]/.test(value)) strength += 20;
      if (/[0-9]/.test(value)) strength += 20;
      if (/[^A-Za-z0-9]/.test(value)) strength += 30;

      strengthFill.style.width = `${Math.min(strength, 100)}%`;
    });
  }
});
