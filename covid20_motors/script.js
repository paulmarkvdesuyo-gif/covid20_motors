// CO_VID 20's Motor Parts - Complete System with Full CRUD Operations

// Global variables
let products = [];
let sales = [];
let salesChart = null;

// ============================================
// PAGE NAVIGATION
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, setting up menu clicks...');
    
    const menuItems = document.querySelectorAll('.menu-item');
    for (let i = 0; i < menuItems.length; i++) {
        menuItems[i].addEventListener('click', function(e) {
            const pageId = this.getAttribute('data-page');
            switchPage(pageId);
        });
    }
    
    fetch('api_admin_session.php')
        .then(response => response.ok ? response.json() : null)
        .then(data => {
            if (!data || !data.success) return;
            document.getElementById('loginPage').style.display = 'none';
            document.getElementById('mainApp').style.display = 'block';
            initApp();
        })
        .catch(error => console.warn('Unable to restore admin session:', error));
});

function switchPage(pageId) {
    const pages = ['dashboard', 'inventory', 'sales', 'lowstock', 'reports'];
    for (let i = 0; i < pages.length; i++) {
        const page = document.getElementById(pages[i] + 'Page');
        if (page) page.style.display = 'none';
    }
    
    const targetPage = document.getElementById(pageId + 'Page');
    if (targetPage) targetPage.style.display = 'block';
    
    const menuItems = document.querySelectorAll('.menu-item');
    for (let i = 0; i < menuItems.length; i++) {
        menuItems[i].classList.remove('active');
        if (menuItems[i].getAttribute('data-page') === pageId) {
            menuItems[i].classList.add('active');
        }
    }
    
    if (pageId === 'reports') {
        generateReportPreview();
    }
    
    if (pageId === 'dashboard') {
        setTimeout(function() {
            updateSalesChart();
        }, 100);
    }
}

// ============================================
// DATA FUNCTIONS
// ============================================

function loadData() {
    const savedProducts = localStorage.getItem('covid_products');
    const savedSales = localStorage.getItem('covid_sales');
    
    if (savedProducts) {
        products = JSON.parse(savedProducts);
    } else {
        products = [
            { id: 1, name: 'Brake Pads', price: 450, stock: 12 },
            { id: 2, name: 'Engine Oil (1L)', price: 320, stock: 3 },
            { id: 3, name: 'Air Filter', price: 280, stock: 2 },
            { id: 4, name: 'Spark Plug', price: 150, stock: 18 },
            { id: 5, name: 'Chain Lubricant', price: 220, stock: 1 }
        ];
        saveData();
    }
    
    if (savedSales) {
        sales = JSON.parse(savedSales);
        console.log('Loaded sales:', sales.length, 'Total: ₱' + calculateTotalSales());
    } else {
        sales = [];
        saveData();
    }
}

function saveData() {
    localStorage.setItem('covid_products', JSON.stringify(products));
    localStorage.setItem('covid_sales', JSON.stringify(sales));
}

function calculateTotalSales() {
    let total = 0;
    for (let i = 0; i < sales.length; i++) {
        total = total + (sales[i].total || 0);
    }
    return total;
}

// ============================================
// INVENTORY FUNCTIONS
// ============================================

function addProduct(name, price, stock) {
    if (!name || price <= 0 || stock < 0) {
        showToast('Please fill all fields correctly', 'error');
        return false;
    }
    
    const newId = products.length > 0 ? Math.max(...products.map(p => p.id)) + 1 : 1;
    products.push({
        id: newId,
        name: name.trim(),
        price: parseFloat(price),
        stock: parseInt(stock)
    });
    saveData();
    refreshAllUI();
    showToast('Product "' + name + '" added successfully!');
    return true;
}

function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        products = products.filter(p => p.id !== id);
        saveData();
        refreshAllUI();
        showToast('Product deleted successfully');
    }
}

function openEditModal(id) {
    const product = products.find(p => p.id === id);
    if (!product) return;
    
    const newName = prompt('Edit Product Name:', product.name);
    if (newName && newName.trim()) product.name = newName.trim();
    
    const newPrice = prompt('Edit Price (₱):', product.price);
    if (newPrice && !isNaN(newPrice) && parseFloat(newPrice) > 0) product.price = parseFloat(newPrice);
    
    const newStock = prompt('Edit Stock Quantity:', product.stock);
    if (newStock && !isNaN(newStock) && parseInt(newStock) >= 0) product.stock = parseInt(newStock);
    
    saveData();
    refreshAllUI();
    showToast('Product updated successfully');
}

// ============================================
// SALE FUNCTIONS WITH FULL CRUD
// ============================================

function recordSale(productId, quantity) {
    const product = products.find(p => p.id === parseInt(productId));
    if (!product) {
        showToast('Product not found', 'error');
        return false;
    }
    
    if (quantity <= 0) {
        showToast('Invalid quantity', 'error');
        return false;
    }
    
    if (product.stock < quantity) {
        showToast('Insufficient stock! Only ' + product.stock + ' left', 'error');
        return false;
    }
    
    const total = product.price * quantity;
    product.stock -= quantity;
    
    const now = new Date();
    const dateString = now.toLocaleString();
    
    const newSale = {
        id: Date.now(),
        productId: product.id,
        productName: product.name,
        quantity: quantity,
        total: total,
        date: dateString,
        timestamp: now.getTime()
    };
    
    sales.unshift(newSale);
    saveData();
    refreshAllUI();
    
    showToast('💵 Sale recorded: ' + quantity + ' x ' + product.name + ' = ₱' + total.toFixed(2), 'success');
    return true;
}

// NEW FUNCTION: Delete a single sale and restore stock
function deleteSale(saleId) {
    // Find the sale to delete
    const saleToDelete = sales.find(s => s.id === saleId);
    if (!saleToDelete) {
        showToast('Sale not found', 'error');
        return;
    }
    
    // Confirm deletion
    if (!confirm('Delete this sale?\n\nProduct: ' + saleToDelete.productName + '\nQuantity: ' + saleToDelete.quantity + '\nAmount: ₱' + saleToDelete.total.toFixed(2) + '\n\nStock will be restored to the product.')) {
        return;
    }
    
    // Find the product and restore stock
    const product = products.find(p => p.id === saleToDelete.productId);
    if (product) {
        product.stock = product.stock + saleToDelete.quantity;
        console.log('Restored ' + saleToDelete.quantity + ' units to ' + product.name);
    }
    
    // Remove the sale from the array
    sales = sales.filter(s => s.id !== saleId);
    
    // Save all changes
    saveData();
    refreshAllUI();
    
    const newTotal = calculateTotalSales();
    showToast('🗑️ Sale deleted! ₱' + saleToDelete.total.toFixed(2) + ' removed. New total: ₱' + newTotal.toFixed(2), 'info');
}

// NEW FUNCTION: Edit a sale
function editSale(saleId) {
    const sale = sales.find(s => s.id === saleId);
    if (!sale) {
        showToast('Sale not found', 'error');
        return;
    }
    
    const product = products.find(p => p.id === sale.productId);
    if (!product) {
        showToast('Product not found', 'error');
        return;
    }
    
    const newQuantity = prompt('Edit quantity for ' + sale.productName + ':\nCurrent quantity: ' + sale.quantity, sale.quantity);
    if (!newQuantity || isNaN(newQuantity) || parseInt(newQuantity) <= 0) {
        showToast('Invalid quantity', 'error');
        return;
    }
    
    const newQty = parseInt(newQuantity);
    const oldQty = sale.quantity;
    const qtyDifference = newQty - oldQty;
    
    // Check if we have enough stock for increase
    if (qtyDifference > 0 && product.stock < qtyDifference) {
        showToast('Insufficient stock! Only ' + product.stock + ' units available', 'error');
        return;
    }
    
    // Update product stock
    if (qtyDifference > 0) {
        product.stock = product.stock - qtyDifference;
    } else if (qtyDifference < 0) {
        product.stock = product.stock + Math.abs(qtyDifference);
    }
    
    // Update sale
    const newTotal = product.price * newQty;
    sale.quantity = newQty;
    sale.total = newTotal;
    
    saveData();
    refreshAllUI();
    
    showToast('✏️ Sale updated! New total: ₱' + newTotal.toFixed(2), 'success');
}

// ============================================
// UI RENDER FUNCTIONS
// ============================================

function refreshAllUI() {
    renderInventoryTable();
    renderSalesHistory();
    updateDashboardStats();
    updateLowStockList();
    populateProductSelect();
    updateSalesChart();
}

function renderInventoryTable() {
    const tbody = document.getElementById('inventoryTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    for (let i = 0; i < products.length; i++) {
        const product = products[i];
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = product.id;
        row.insertCell(1).innerHTML = product.name;
        row.insertCell(2).innerHTML = '₱ ' + product.price.toFixed(2);
        row.insertCell(3).innerHTML = '<span style="color: ' + (product.stock <= 5 ? '#ef4444' : '#22c55e') + '; font-weight: bold;">' + product.stock + '</span>';
        row.insertCell(4).innerHTML = product.stock <= 5 ? 
            '<span class="status-badge status-low">⚠️ Low Stock</span>' : 
            '<span class="status-badge status-good">✓ In Stock</span>';
        row.insertCell(5).innerHTML = '<button class="action-btn btn-edit" onclick="openEditModal(' + product.id + ')"><i class="fas fa-edit"></i></button> <button class="action-btn btn-delete" onclick="deleteProduct(' + product.id + ')"><i class="fas fa-trash"></i></button>';
    }
}

// UPDATED: Sales history with Edit and Delete buttons
function renderSalesHistory() {
    const tbody = document.getElementById('salesHistoryBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    const limit = sales.length > 100 ? 100 : sales.length;
    
    if (sales.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px;">No sales recorded yet. Go to "Record Sale" to add your first sale!</td></tr>';
        return;
    }
    
    for (let i = 0; i < limit; i++) {
        const sale = sales[i];
        const row = tbody.insertRow();
        row.insertCell(0).innerHTML = sale.date;
        row.insertCell(1).innerHTML = sale.productName;
        row.insertCell(2).innerHTML = sale.quantity;
        row.insertCell(3).innerHTML = '₱ ' + sale.total.toFixed(2);
        row.insertCell(4).innerHTML = `
            <button class="action-btn btn-edit" onclick="editSale(${sale.id})" title="Edit Sale">
                <i class="fas fa-edit"></i>
            </button>
            <button class="action-btn btn-delete" onclick="deleteSale(${sale.id})" title="Delete Sale">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;
    }
}

function updateDashboardStats() {
    const totalProducts = products.length;
    let lowStock = 0;
    for (let i = 0; i < products.length; i++) {
        if (products[i].stock <= 5) lowStock++;
    }
    
    const totalSales = calculateTotalSales();
    
    const totalProductsEl = document.getElementById('totalProducts');
    const lowStockCountEl = document.getElementById('lowStockCount');
    const totalSalesAmountEl = document.getElementById('totalSalesAmount');
    const bellBadgeEl = document.getElementById('bellBadge');
    
    if (totalProductsEl) totalProductsEl.innerText = totalProducts;
    if (lowStockCountEl) lowStockCountEl.innerText = lowStock;
    if (totalSalesAmountEl) totalSalesAmountEl.innerText = totalSales.toFixed(2);
    if (bellBadgeEl) bellBadgeEl.innerText = lowStock;
    
    console.log('Dashboard Updated - Total Sales: ₱' + totalSales.toFixed(2));
}

function updateSalesChart() {
    console.log('=== UPDATING SALES CHART ===');
    console.log('Total sales records:', sales.length);
    
    const grandTotal = calculateTotalSales();
    console.log('Grand total sales: ₱' + grandTotal.toFixed(2));
    
    const last7Days = [];
    const salesData = [0, 0, 0, 0, 0, 0, 0];
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(today.getDate() - i);
        date.setHours(0, 0, 0, 0);
        const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
        const monthDay = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        last7Days.push(dayName + ' ' + monthDay);
    }
    
    for (let i = 0; i < sales.length; i++) {
        const sale = sales[i];
        let saleAmount = sale.total || 0;
        let saleDate;
        
        if (sale.timestamp) {
            saleDate = new Date(sale.timestamp);
        } else if (sale.date) {
            saleDate = new Date(sale.date);
        } else {
            continue;
        }
        
        saleDate.setHours(0, 0, 0, 0);
        
        if (isNaN(saleDate.getTime())) {
            continue;
        }
        
        const diffTime = today - saleDate;
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays >= 0 && diffDays <= 6) {
            const index = 6 - diffDays;
            if (index >= 0 && index < 7) {
                salesData[index] = salesData[index] + saleAmount;
            }
        }
    }
    
    console.log('Chart data by day:', salesData);
    
    const canvas = document.getElementById('salesChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    
    if (salesChart) {
        salesChart.destroy();
        salesChart = null;
    }
    
    const maxDataValue = Math.max(...salesData, 100);
    
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: last7Days,
            datasets: [{
                label: 'Daily Sales (₱)',
                data: salesData,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#4f46e5',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { size: 12, weight: 'bold' }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₱ ' + context.raw.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱ ' + value.toLocaleString();
                        }
                    },
                    title: {
                        display: true,
                        text: 'Sales Amount (₱)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
    
    console.log('Chart updated successfully');
}

function updateLowStockList() {
    const container = document.getElementById('lowStockList');
    if (!container) return;
    
    const lowItems = [];
    for (let i = 0; i < products.length; i++) {
        if (products[i].stock <= 5) {
            lowItems.push(products[i]);
        }
    }
    
    if (lowItems.length === 0) {
        container.innerHTML = '<div class="form-card" style="text-align: center; color: #22c55e;"><i class="fas fa-check-circle"></i> All products have sufficient stock!</div>';
        return;
    }
    
    let html = '';
    for (let i = 0; i < lowItems.length; i++) {
        const p = lowItems[i];
        html = html + '<div class="lowstock-item"><div><strong>' + p.name + '</strong><br><small>Stock: ' + p.stock + ' units | Price: ₱' + p.price + '</small></div><i class="fas fa-exclamation-triangle" style="color: #ef4444; font-size: 24px;"></i></div>';
    }
    container.innerHTML = html;
}

function populateProductSelect() {
    const select = document.getElementById('saleProductSelect');
    if (select) {
        let options = '<option value="">Select Product</option>';
        for (let i = 0; i < products.length; i++) {
            const p = products[i];
            options = options + '<option value="' + p.id + '">' + p.name + ' (₱' + p.price + ') - Stock: ' + p.stock + '</option>';
        }
        select.innerHTML = options;
    }
}

// ============================================
// EXPORT FUNCTIONS
// ============================================

function exportCSV() {
    let csv = "Product Name,Price (₱),Stock,Status\n";
    for (let i = 0; i < products.length; i++) {
        const p = products[i];
        csv = csv + '"' + p.name + '",' + p.price + ',' + p.stock + ',' + (p.stock <= 5 ? 'Low Stock' : 'Good') + '\n';
    }
    csv = csv + '\n\nSales Report\n';
    csv = csv + 'Date,Product,Quantity,Total (₱)\n';
    for (let i = 0; i < sales.length; i++) {
        const s = sales[i];
        csv = csv + '"' + s.date + '","' + s.productName + '",' + s.quantity + ',' + (s.total || 0) + '\n';
    }
    csv = csv + '\n\nTOTAL SALES: ₱' + calculateTotalSales().toFixed(2);
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'covid20_report_' + Date.now() + '.csv';
    link.click();
    URL.revokeObjectURL(link.href);
    showToast('CSV report downloaded!');
}

function exportJSON() {
    const data = { 
        products: products, 
        sales: sales, 
        totalSales: calculateTotalSales(),
        generatedAt: new Date().toISOString() 
    };
    const json = JSON.stringify(data, null, 2);
    const blob = new Blob([json], { type: 'application/json' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'covid20_report_' + Date.now() + '.json';
    link.click();
    URL.revokeObjectURL(link.href);
    showToast('JSON report downloaded!');
}

function exportTXT() {
    let txt = "=== CO_VID 20's MOTOR PARTS - INVENTORY & SALES REPORT ===\n";
    txt = txt + "Generated: " + new Date().toLocaleString() + "\n\n";
    txt = txt + "=== INVENTORY ===\n";
    for (let i = 0; i < products.length; i++) {
        const p = products[i];
        txt = txt + p.name + " | Price: ₱" + p.price + " | Stock: " + p.stock + " | " + (p.stock <= 5 ? '⚠️ LOW STOCK' : 'OK') + "\n";
    }
    txt = txt + "\n=== SALES HISTORY ===\n";
    for (let i = 0; i < sales.length; i++) {
        const s = sales[i];
        txt = txt + s.date + " | " + s.productName + " | Qty: " + s.quantity + " | ₱" + (s.total || 0) + "\n";
    }
    txt = txt + "\nTOTAL SALES: ₱" + calculateTotalSales().toFixed(2);
    
    const blob = new Blob([txt], { type: 'text/plain' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'covid20_report_' + Date.now() + '.txt';
    link.click();
    URL.revokeObjectURL(link.href);
    showToast('TXT report downloaded!');
}

function generateReportPreview() {
    const preview = document.getElementById('reportPreview');
    if (preview) {
        const data = { 
            products: products, 
            sales: sales, 
            totalSales: calculateTotalSales() 
        };
        preview.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function showToast(message, type) {
    type = type || 'success';
    const toast = document.createElement('div');
    let bgColor = '#22c55e';
    if (type === 'error') bgColor = '#ef4444';
    if (type === 'warning') bgColor = '#f59e0b';
    if (type === 'info') bgColor = '#3b82f6';
    
    toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: ' + bgColor + '; color: white; padding: 12px 20px; border-radius: 10px; z-index: 10000; font-size: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10001;';
    let icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    if (type === 'info') icon = 'info-circle';
    
    toast.innerHTML = '<i class="fas fa-' + icon + '"></i> ' + message;
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 4000);
}

window.checkLowStockAlert = function() {
    let lowCount = 0;
    for (let i = 0; i < products.length; i++) {
        if (products[i].stock <= 5) lowCount++;
    }
    if (lowCount > 0) {
        showToast('⚠️ ' + lowCount + ' product(s) have low stock!', 'error');
    } else {
        showToast('✓ All stock levels are healthy!', 'success');
    }
};

// ============================================
// INITIALIZE APP
// ============================================

function initApp() {
    console.log('Initializing app...');
    loadData();
    refreshAllUI();
    console.log('App initialized - Products:', products.length, 'Sales:', sales.length, 'Total: ₱' + calculateTotalSales().toFixed(2));
    
    const addBtn = document.getElementById('addProductBtn');
    if (addBtn) {
        addBtn.onclick = function() {
            const name = document.getElementById('prodName').value;
            const price = parseFloat(document.getElementById('prodPrice').value);
            const stock = parseInt(document.getElementById('prodStock').value);
            if (addProduct(name, price, stock)) {
                document.getElementById('prodName').value = '';
                document.getElementById('prodPrice').value = '';
                document.getElementById('prodStock').value = '';
            }
        };
    }
    
    const saleBtn = document.getElementById('recordSaleBtn');
    if (saleBtn) {
        saleBtn.onclick = function() {
            const productId = document.getElementById('saleProductSelect').value;
            const quantity = parseInt(document.getElementById('saleQty').value);
            if (recordSale(productId, quantity)) {
                document.getElementById('saleQty').value = '';
            }
        };
    }
    
    const exportCSVBtn = document.getElementById('exportCSV');
    if (exportCSVBtn) exportCSVBtn.onclick = exportCSV;
    
    const exportJSONBtn = document.getElementById('exportJSON');
    if (exportJSONBtn) exportJSONBtn.onclick = exportJSON;
    
    const exportTXTBtn = document.getElementById('exportTXT');
    if (exportTXTBtn) exportTXTBtn.onclick = exportTXT;
}

// ============================================
// LOGIN HANDLER
// ============================================

const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.onsubmit = async function(e) {
        e.preventDefault();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        try {
            const response = await fetch('api_admin_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                alert(data.message || 'Invalid admin credentials.');
                return;
            }

            document.getElementById('loginPage').style.display = 'none';
            document.getElementById('mainApp').style.display = 'block';
            initApp();
        } catch (error) {
            alert('Unable to reach the login service. Please try again.');
        }
    };
}

const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.onclick = async function() {
        try {
            await fetch('api_admin_logout.php', { method: 'POST' });
        } catch (error) {
            console.error('Admin logout failed:', error);
        }
        document.getElementById('loginPage').style.display = 'flex';
        document.getElementById('mainApp').style.display = 'none';
        showToast('Logged out', 'info');
    };
}

// Expose functions globally
window.openEditModal = openEditModal;
window.deleteProduct = deleteProduct;
window.deleteSale = deleteSale;
window.editSale = editSale;
window.checkLowStockAlert = window.checkLowStockAlert;
window.exportCSV = exportCSV;
window.exportJSON = exportJSON;
window.exportTXT = exportTXT;