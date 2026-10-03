// Cart Functions
let cart = [];

// Load cart from localStorage
function loadCart() {
    const stored = localStorage.getItem('cart');
    if (stored) {
        cart = JSON.parse(stored);
    } else {
        cart = [];
    }
    updateCartCount();
}

// Save cart to localStorage
function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
}

// Update cart count badge
function updateCartCount() {
    const total = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartBadge = document.getElementById('cartCount');
    if (cartBadge) cartBadge.innerText = total;
}

// Add to cart
async function addToCart(productId) {
    if (!currentUser) {
        showToast('Please login first', true);
        showLoginModal();
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/api_products.php`);
        const data = await response.json();
        
        if (data.success) {
            const product = data.products.find(p => p.id == productId);
            if (!product || product.stock === 0) {
                showToast('Product out of stock!', true);
                return;
            }
            
            const existing = cart.find(item => item.id == productId);
            if (existing) {
                if (existing.quantity < product.stock) {
                    existing.quantity++;
                    showToast(`${product.name} quantity updated!`);
                } else {
                    showToast('Not enough stock!', true);
                    return;
                }
            } else {
                cart.push({
                    id: product.id,
                    name: product.name,
                    price: parseFloat(product.price),
                    quantity: 1,
                    image: product.image || '🔧'
                });
                showToast(`${product.name} added to cart!`);
            }
            saveCart();
        }
    } catch(error) {
        console.error('Add to cart error:', error);
        showToast('Error adding to cart', true);
    }
}

// Remove from cart
function removeFromCart(productId) {
    cart = cart.filter(item => item.id != productId);
    saveCart();
    if (typeof displayCartItems === 'function') {
        displayCartItems();
    }
}

// Update quantity
function updateQuantity(productId, change) {
    const index = cart.findIndex(item => item.id == productId);
    if (index !== -1) {
        const newQty = cart[index].quantity + change;
        if (newQty <= 0) {
            cart.splice(index, 1);
        } else {
            cart[index].quantity = newQty;
        }
        saveCart();
        if (typeof displayCartItems === 'function') {
            displayCartItems();
        }
    }
}

// Get cart total
function getCartTotal() {
    return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
}

// Clear cart
function clearCart() {
    cart = [];
    saveCart();
}

// Initialize cart
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
});