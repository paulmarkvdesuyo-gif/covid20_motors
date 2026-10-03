// Shop JavaScript for customer store
let currentUser = null;
let currentCategory = 'all';
let searchQuery = '';
let allProducts = [];

// Load products from database
async function loadProducts() {
    const container = document.getElementById('productsContainer');
    if (!container) return;
    
    container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading products...</div>';
    
    try {
        const response = await fetch('api/products.php');
        const data = await response.json();
        
        if (data.success && data.products) {
            allProducts = data.products;
            displayProducts(allProducts);
        } else {
            container.innerHTML = '<div class="no-products">No products available.</div>';
        }
    } catch (error) {
        console.error('Error loading products:', error);
        container.innerHTML = '<div class="error">Failed to load products. Please check if server is running.</div>';
    }
}

// Display products with images
function displayProducts(products) {
    const container = document.getElementById('productsContainer');
    
    // Apply filters
    let filtered = [...products];
    if (currentCategory !== 'all') {
        filtered = filtered.filter(p => p.category === currentCategory);
    }
    if (searchQuery) {
        filtered = filtered.filter(p => 
            p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            (p.code && p.code.toLowerCase().includes(searchQuery.toLowerCase()))
        );
    }
    
    if (filtered.length === 0) {
        container.innerHTML = '<div class="no-products">No products match your search.</div>';
        return;
    }
    
    container.innerHTML = filtered.map(product => {
        // Check if product has a valid image
        let imageHtml = '';
        if (product.image && product.image !== '' && product.image !== 'null' && product.image !== 'undefined') {
            imageHtml = `<img src="${product.image}" alt="${escapeHtml(product.name)}" 
                          onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3096/3096961.png'">`;
        } else {
            // Default icon based on category
            const categoryIcons = {
                'Engine Parts': '🔧',
                'Brakes': '🛑',
                'Electrical': '⚡',
                'Accessories': '🎨'
            };
            const icon = categoryIcons[product.category] || '🔧';
            imageHtml = `<div class="image-placeholder">${icon}</div>`;
        }
        
        return `
            <div class="product-card">
                <div class="product-image">
                    ${imageHtml}
                </div>
                <div class="product-info">
                    <h3>${escapeHtml(product.name)}</h3>
                    ${product.code ? `<p class="product-code">${escapeHtml(product.code)}</p>` : ''}
                    <p class="product-category">${escapeHtml(product.category || 'General')}</p>
                    <div class="product-price">₱${parseFloat(product.price).toLocaleString()}</div>
                    <div class="product-stock ${product.stock > 0 ? 'in-stock' : 'out-stock'}">
                        ${product.stock > 0 ? `In Stock: ${product.stock}` : 'Out of Stock'}
                    </div>
                    <button class="add-to-cart-btn" 
                            onclick="addToCart(${product.id})" 
                            ${product.stock === 0 ? 'disabled' : ''}>
                        ${product.stock > 0 ? '<i class="fas fa-cart-plus"></i> Add to Cart' : 'Out of Stock'}
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// Filter products by category and search
function filterProducts() {
    currentCategory = document.getElementById('categoryFilter')?.value || 'all';
    searchQuery = document.getElementById('searchInput')?.value || '';
    displayProducts(allProducts);
}

// Reset filters
function resetFilters() {
    if (document.getElementById('searchInput')) {
        document.getElementById('searchInput').value = '';
    }
    if (document.getElementById('categoryFilter')) {
        document.getElementById('categoryFilter').value = 'all';
    }
    currentCategory = 'all';
    searchQuery = '';
    displayProducts(allProducts);
}

// Add to cart
async function addToCart(productId) {
    if (!currentUser) {
        showToast('Please login first', true);
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1500);
        return;
    }
    
    const product = allProducts.find(p => p.id === productId);
    if (!product || product.stock === 0) {
        showToast('Out of stock', true);
        return;
    }
    
    // Get existing cart from localStorage
    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        if (existingItem.quantity < product.stock) {
            existingItem.quantity++;
            showToast(`✓ ${product.name} quantity updated to ${existingItem.quantity}`);
        } else {
            showToast('Not enough stock available', true);
            return;
        }
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.price),
            quantity: 1,
            image: product.image || null,
            code: product.code || ''
        });
        showToast(`✓ ${product.name} added to cart!`);
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
}

// Update cart count badge
function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const total = cart.reduce((sum, item) => sum + item.quantity, 0);
    const badge = document.getElementById('cartCount');
    if (badge) badge.innerText = total;
}

// View cart
function viewCart() {
    window.location.href = 'cart.html';
}

// View orders
function viewOrders() {
    if (!currentUser) {
        showToast('Please login first', true);
        window.location.href = 'index.html';
        return;
    }
    window.location.href = 'orders.html';
}

// View profile
function viewProfile() {
    if (!currentUser) {
        showToast('Please login first', true);
        window.location.href = 'index.html';
        return;
    }
    window.location.href = 'profile.html';
}

// Toggle user menu dropdown
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) dropdown.classList.toggle('show');
}

// Logout function
function logout() {
    localStorage.removeItem('currentUser');
    localStorage.removeItem('cart');
    showToast('Logged out successfully');
    setTimeout(() => {
        window.location.href = 'index.html';
    }, 1000);
}

// Show toast notification
function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.className = isError ? 'toast error' : 'toast';
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

// Escape HTML to prevent XSS
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Initialize page
document.addEventListener('DOMContentLoaded', () => {
    // Load current user from localStorage
    const savedUser = localStorage.getItem('currentUser');
    if (savedUser) {
        try {
            currentUser = JSON.parse(savedUser);
            const userNameSpan = document.getElementById('userName');
            const welcomeNameSpan = document.getElementById('welcomeName');
            if (userNameSpan) {
                userNameSpan.innerHTML = `<i class="fas fa-user-circle"></i> ${currentUser.fullname ? currentUser.fullname.split(' ')[0] : 'User'}`;
            }
            if (welcomeNameSpan) {
                welcomeNameSpan.innerText = currentUser.fullname || 'User';
            }
        } catch(e) {
            console.error('Error parsing user data:', e);
        }
    }
    
    // Load products
    loadProducts();
    
    // Update cart count
    updateCartCount();
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const container = document.querySelector('.user-menu-container');
        const dropdown = document.getElementById('userDropdown');
        if (container && dropdown && !container.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
});