// Get the dark mode toggle button
const darkModeToggle = document.getElementById('dark-mode-toggle');
const sunIcon = document.getElementById('sun-icon');
const moonIcon = document.getElementById('moon-icon');

// Check if dark mode is enabled in localStorage
if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark-mode');
    moonIcon.style.display = 'inline';
    sunIcon.style.display = 'none';
}

// Toggle dark mode when the button is clicked
darkModeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');

    // Update the icon visibility
    if (document.body.classList.contains('dark-mode')) {
        moonIcon.style.display = 'inline';
        sunIcon.style.display = 'none';
        localStorage.setItem('theme', 'dark');
    } else {
        moonIcon.style.display = 'none';
        sunIcon.style.display = 'inline';
        localStorage.setItem('theme', 'light');
    }
});
const scrollTopBtn = document.getElementById("scrollTopBtn");

window.addEventListener("scroll", () => {
  if (window.scrollY > 300) {
    scrollTopBtn.style.display = "block";
  } else {
    scrollTopBtn.style.display = "none";
  }
});

scrollTopBtn.addEventListener("click", () => {
  window.scrollTo({
    top: 0,
    behavior: "smooth"
  });
});
