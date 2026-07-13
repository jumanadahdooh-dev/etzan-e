document.getElementById("contactForm").addEventListener("submit", function (e) {
  e.preventDefault();

  let name = document.getElementById("name");
  let email = document.getElementById("email");
  let subject = document.getElementById("subject");
  let message = document.getElementById("message");
  let formMessage = document.getElementById("formMessage");

  let valid = true;

  [name, email, subject, message].forEach(field => {
    field.classList.remove("error");
  });

  if (name.value === "") {
    name.classList.add("error");
    valid = false;
  }

  if (email.value === "" || !email.value.includes("@") || !email.value.includes(".")) {
    email.classList.add("error");
    valid = false;
  }

  if (subject.value === "") {
    subject.classList.add("error");
    valid = false;
  }

  if (message.value === "") {
    message.classList.add("error");
    valid = false;
  }

  if (!valid) {
    formMessage.textContent = "Please fill all fields correctly.";
    formMessage.className = "fail";
  } else {
    formMessage.textContent = "success";
    formMessage.className = "success";
    document.getElementById("contactForm").reset();
  }
});