document.addEventListener("DOMContentLoaded", function () {
  /* =========================
     تبديل المظهر الفاتح/الغامق
  ========================= */
  const themeToggle = document.getElementById("etzanThemeToggle");

  if (themeToggle) {
    themeToggle.addEventListener("click", function () {
      const html = document.documentElement;
      const next = html.getAttribute("data-theme") === "dark" ? "light" : "dark";

      html.setAttribute("data-theme", next);
      localStorage.setItem("etzan-theme", next);
    });
  }

  const header = document.querySelector(".main-header");
  const navLinks = document.querySelectorAll(".nav-scroll-link");
  const allNavLinks = document.querySelectorAll(".nav-link");
  const sections = [];

  /* =========================
     Header Scroll Effect
  ========================= */
  if (header) {
    window.addEventListener("scroll", function () {
      if (window.scrollY > 10) {
        header.classList.add("scrolled");
      } else {
        header.classList.remove("scrolled");
      }
    });
  }

  /* =========================
     Collect Sections
  ========================= */
  navLinks.forEach((link) => {
    const sectionId = link.dataset.section;
    const section = document.getElementById(sectionId);

    if (section) {
      sections.push(section);
    }
  });

  /* =========================
     Active Section Logic
  ========================= */
  function setActiveLink() {
    if (!sections.length) return;

    let currentSection = "";
    const scrollPosition = window.scrollY + 140;

    sections.forEach((section) => {
      if (
        scrollPosition >= section.offsetTop &&
        scrollPosition < section.offsetTop + section.offsetHeight
      ) {
        currentSection = section.getAttribute("id");
      }
    });

    // إزالة active من الكل
    allNavLinks.forEach((link) => link.classList.remove("active"));

    // تفعيل السكشن الحالي
    navLinks.forEach((link) => {
      if (link.dataset.section === currentSection) {
        link.classList.add("active");
      }
    });
  }

  /* =========================
     Check if Home Page
  ========================= */
  const isHomePage =
    window.location.pathname === "/" ||
    window.location.pathname.endsWith("/");

  if (isHomePage) {
    window.addEventListener("scroll", setActiveLink);
    window.addEventListener("load", setActiveLink);
    window.addEventListener("hashchange", setActiveLink);

    setActiveLink();
  }

  /* =========================
     Fix Active for Pages (مثل اتصل بنا)
  ========================= */
  if (!isHomePage) {
    allNavLinks.forEach((link) => link.classList.remove("active"));

    const currentPath = window.location.pathname;

    allNavLinks.forEach((link) => {
      const href = link.getAttribute("href");

      if (href && href.includes(currentPath)) {
        link.classList.add("active");
      }
    });
  }
});
