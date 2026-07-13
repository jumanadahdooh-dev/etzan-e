const articleContent = document.getElementById("articleContent");
const readingProgressBar = document.getElementById("readingProgressBar");

function updateReadingProgress() {
  if (!articleContent || !readingProgressBar) return;

  const contentTop = articleContent.offsetTop;
  const contentHeight = articleContent.offsetHeight;
  const scrollTop = window.scrollY;
  const windowHeight = window.innerHeight;

  const distance = scrollTop + windowHeight - contentTop;
  const progress = Math.min(Math.max((distance / contentHeight) * 100, 0), 100);

  readingProgressBar.style.width = `${progress}%`;
}

window.addEventListener("scroll", updateReadingProgress);
window.addEventListener("load", updateReadingProgress);
window.addEventListener("resize", updateReadingProgress);