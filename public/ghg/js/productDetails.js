// Get product ID from URL
const urlParams = new URLSearchParams(window.location.search);
const productID = urlParams.get('id');

// Dummy data for products (this can be replaced with actual data)
const products = {
    bread1: {
        name: "Fresh Bread",
        description: "Handmade bread baked using the finest fresh ingredients, with an irresistible taste.",
        ingredients: ["Flour", "Water", "Yeast", "Salt", "Sugar"],
        price: "$3.99",
        image: "assets/images/3.png"
    },
    cake1: {
        name: "Chocolate Cake",
        description: "A rich chocolate cake made with the finest cocoa and cream.",
        ingredients: ["Flour", "Sugar", "Cocoa", "Eggs", "Milk", "Butter"],
        price: "$6.99",
        image: "assets/images/4.png"
    },
    pastry1: {
        name: "Vanilla Pastry",
        description: "A delicious buttery pastry filled with vanilla cream, perfect for breakfast.",
        ingredients: ["Butter", "Flour", "Sugar", "Vanilla", "Eggs"],
        price: "$4.99",
        image: "assets/images/5.png"
    },
    bread2: {
        name: "Organic Bread",
        description: "Freshly baked with organic flour, giving it a healthy and rich flavor.",
        ingredients: ["Organic Flour", "Water", "Yeast", "Salt"],
        price: "$5.99",
        image: "assets/images/2.png"
    },
    cake2: {
        name: "Vanilla Cake",
        description: "A creamy vanilla cake topped with fresh fruits and whipped cream.",
        ingredients: ["Flour", "Sugar", "Eggs", "Vanilla", "Whipped Cream", "Fruits"],
        price: "$7.99",
        image: "assets/images/1.png"
    },
    pastry2: {
        name: "Chocolate Pastry",
        description: "Flaky pastry filled with rich chocolate and topped with a dusting of powdered sugar.",
        ingredients: ["Chocolate", "Cream", "Butter", "Flour"],
        price: "$5.99",
        image: "assets/images/6.png"
    },
    bread3: {
        name: "Seeded Bread",
        description: "Whole-grain bread filled with sunflower seeds and pumpkin seeds for extra crunch.",
        ingredients: ["Whole Grain Flour", "Water", "Yeast", "Sunflower Seeds", "Pumpkin Seeds"],
        price: "$6.49",
        image: "assets/images/7.png"
    },
    cake3: {
        name: "Strawberry Cake",
        description: "A light sponge cake layered with fresh strawberries and whipped cream.",
        ingredients: ["Flour", "Sugar", "Eggs", "Strawberries", "Whipped Cream"],
        price: "$8.99",
        image: "assets/images/8.png"
    },
    // Add more products here
};

// Get product details based on ID
const product = products[productID];

if (product) {
    document.getElementById('product-name').innerText = product.name;
    document.getElementById('product-description').innerText = product.description;
    document.getElementById('product-ingredients').innerHTML = product.ingredients.map(ingredient => `<li>${ingredient}</li>`).join('');
    document.getElementById('product-image').src = product.image;
    document.getElementById('product-price').innerText = product.price;
} else {
    document.querySelector('.product-details').innerHTML = "<p>Product not found.</p>";
}
