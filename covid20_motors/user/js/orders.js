// Orders Functions
let userOrders = [];

// Load user orders from the database, with local checkout orders as fallback
async function loadOrders() {
    if (!currentUser) {
        window.location.href = 'index.html';
        return;
    }

    const stored = localStorage.getItem('orders');
    const localOrders = stored ? JSON.parse(stored).filter(order => order.userId == currentUser.id) : [];

    try {
        const response = await fetch(`${API_BASE_URL}/user/api_orders.php?user_id=${encodeURIComponent(currentUser.id)}`);
        const data = await response.json();
        const databaseOrders = data.success ? (data.data || []).map(order => ({
            ...order,
            orderId: order.order_number,
            orderDate: order.order_date,
            total: parseFloat(order.total_amount || 0),
            paymentMethod: order.payment_method,
            status: order.status,
            items: (order.items || []).map(item => ({
                ...item,
                name: item.name || `Product #${item.product_id}`,
                price: parseFloat(item.price || 0),
                quantity: parseInt(item.quantity || 0, 10)
            }))
        })) : [];

        const databaseOrderIds = new Set(databaseOrders.map(order => order.orderId));
        userOrders = [...databaseOrders, ...localOrders.filter(order => !databaseOrderIds.has(order.orderId))];
    } catch (error) {
        console.error('Error loading customer orders:', error);
        userOrders = localOrders;
    }

    displayOrders();
}

// Display orders
function displayOrders() {
    const container = document.getElementById('ordersList');
    const noOrdersDiv = document.getElementById('noOrders');
    
    if (!container) return;
    
    if (userOrders.length === 0) {
        container.innerHTML = '';
        if (noOrdersDiv) noOrdersDiv.style.display = 'block';
        return;
    }
    
    if (noOrdersDiv) noOrdersDiv.style.display = 'none';
    
    container.innerHTML = userOrders.map(order => `
        <div class="order-card">
            <div class="order-header">
                <span class="order-id">Order #${order.orderId}</span>
                <span class="order-date">${new Date(order.orderDate).toLocaleDateString()}</span>
                <span class="order-status status-${order.status}">${order.status}</span>
            </div>
            <div class="order-items">
                ${(order.items || []).map(item => `
                    <div class="order-item">
                        <span>${escapeHtml(item.name)} x${item.quantity}</span>
                        <span>₱${(parseFloat(item.price || 0) * item.quantity).toLocaleString()}</span>
                    </div>
                `).join('')}
                ${(!order.items || order.items.length === 0) ? '<div class="order-item">No item details available</div>' : ''}
            </div>
            <div class="order-footer">
                <span>Payment: ${order.paymentMethod === 'cod' || order.paymentMethod === 'cash' ? 'Cash on Delivery' : order.paymentMethod === 'gcash' ? 'GCash' : 'Credit Card'}</span>
                <span class="order-total">Total: ₱${parseFloat(order.total || order.total_amount || 0).toLocaleString()}</span>
            </div>
        </div>
    `).join('');
}

// Initialize orders page
document.addEventListener('DOMContentLoaded', function() {
    loadOrders();
});