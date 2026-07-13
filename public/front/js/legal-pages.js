window.addEventListener("scroll", function () {
  const header = document.querySelector(".main-header");
  if (header) {
    if (window.scrollY > 10) {
      header.classList.add("scrolled");
    } else {
      header.classList.remove("scrolled");
    }
  }
});

const navLinks = document.querySelectorAll(".legal-nav-link");
const sections = document.querySelectorAll(".legal-block");

function updateActiveSection() {
  let currentId = "";

  sections.forEach((section) => {
    const sectionTop = section.offsetTop - 150;
    if (window.scrollY >= sectionTop) {
      currentId = section.getAttribute("id");
    }
  });

  navLinks.forEach((link) => {
    link.classList.remove("active");
    if (link.getAttribute("href") === `#${currentId}`) {
      link.classList.add("active");
    }
  });
}

window.addEventListener("scroll", updateActiveSection);

navLinks.forEach((link) => {
  link.addEventListener("click", function () {
    navLinks.forEach((item) => item.classList.remove("active"));
    this.classList.add("active");
  });
});

updateActiveSection();