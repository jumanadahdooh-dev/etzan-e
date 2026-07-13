const searchInput = document.getElementById("articleSearch");
const categoryButtons = document.querySelectorAll(".category-chip");
const articleItems = document.querySelectorAll(".article-item");
const emptyState = document.getElementById("articlesEmptyState");

let activeCategory = "all";

function filterArticles() {
  const searchValue = searchInput.value.trim().toLowerCase();
  let visibleCount = 0;

  articleItems.forEach((item) => {
    const category = item.dataset.category.toLowerCase();
    const title = item.dataset.title.toLowerCase();
    const excerpt = item.dataset.excerpt.toLowerCase();

    const matchesCategory =
      activeCategory === "all" || category === activeCategory.toLowerCase();

    const matchesSearch =
      title.includes(searchValue) ||
      excerpt.includes(searchValue) ||
      category.includes(searchValue);

    const shouldShow = matchesCategory && matchesSearch;

    item.classList.toggle("is-hidden", !shouldShow);

    if (shouldShow) {
      visibleCount += 1;
    }
  });

  emptyState.classList.toggle("is-visible", visibleCount === 0);
}

categoryButtons.forEach((button) => {
  button.addEventListener("click", () => {
    categoryButtons.forEach((btn) => btn.classList.remove("is-active"));
    button.classList.add("is-active");
    activeCategory = button.dataset.category;
    filterArticles();
  });
});

searchInput.addEventListener("input", filterArticles);

filterArticles();