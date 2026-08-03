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

  const registerPassword = document.getElementById("registerPassword");
  const registerStrengthFill = document.getElementById("registerStrengthFill");
  const registerStrengthRules = document.querySelectorAll("#registerStrength [data-rule]");

  if (registerPassword && registerStrengthFill) {
    registerPassword.addEventListener("input", function () {
      const value = this.value;

      const rules = {
        length: value.length >= 8,
        upper: /[A-Z]/.test(value),
        number: /[0-9]/.test(value),
        symbol: /[^A-Za-z0-9]/.test(value),
      };

      let met = 0;

      registerStrengthRules.forEach((rule) => {
        const isMet = rules[rule.dataset.rule];
        rule.classList.toggle("is-met", Boolean(isMet));
        if (isMet) met += 1;
      });

      registerStrengthFill.style.width = `${(met / registerStrengthRules.length) * 100}%`;
    });
  }

  const socialPopupButtons = document.querySelectorAll(".js-social-popup");

  socialPopupButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();

      const width = 560;
      const height = 680;
      const left = window.screenX + (window.outerWidth - width) / 2;
      const top = window.screenY + (window.outerHeight - height) / 2;

      window.open(
        this.href,
        "socialLoginPopup",
        `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes,status=no`
      );
    });
  });
});
