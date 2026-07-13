// Filter functionality
const filterButtons = document.querySelectorAll('.filter-btn');
const productItems = document.querySelectorAll('.product-item');

filterButtons.forEach(button => {
    button.addEventListener('click', () => {
        const filterValue = button.getAttribute('data-filter');
        
        productItems.forEach(item => {
            if (filterValue === 'all' || item.classList.contains(filterValue)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
