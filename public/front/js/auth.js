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
