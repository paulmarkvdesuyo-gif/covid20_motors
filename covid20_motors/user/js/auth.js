// Authentication Functions
let currentUser = null;

// Load user from localStorage
function loadCurrentUser() {
    const stored = localStorage.getItem('currentUser');
    if (stored) {
        currentUser = JSON.parse(stored);
        updateUserUI();
        return true;
    }
    return false;
}

// Save user to localStorage
function saveCurrentUser(user) {
    currentUser = user;
    localStorage.setItem('currentUser', JSON.stringify(user));
    updateUserUI();
}

// Clear user from localStorage
function clearCurrentUser() {
    currentUser = null;
    localStorage.removeItem('currentUser');
    updateUserUI();
}

// Update UI based on login status
function updateUserUI() {
    const userNameSpan = document.getElementById('userName');
    const welcomeNameSpan = document.getElementById('welcomeName');
    const guestMenu = document.getElementById('guestMenu');
    const userMenuDiv = document.getElementById('userMenu');
    
    if (currentUser) {
        if (userNameSpan) userNameSpan.innerHTML = `<i class="fas fa-user-circle"></i> ${currentUser.fullname.split(' ')[0]}`;
        if (welcomeNameSpan) welcomeNameSpan.innerText = currentUser.fullname;
        if (guestMenu) guestMenu.style.display = 'none';
        if (userMenuDiv) userMenuDiv.style.display = 'block';
    } else {
        if (userNameSpan) userNameSpan.innerHTML = '<i class="fas fa-user-circle"></i> Account';
        if (guestMenu) guestMenu.style.display = 'block';
        if (userMenuDiv) userMenuDiv.style.display = 'none';
    }
}

// Login function
async function loginUser() {
    const username = document.getElementById('loginUsername').value;
    const password = document.getElementById('loginPassword').value;
    
    if (!username || !password) {
        showToast('Please enter username and password', true);
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/api_login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            saveCurrentUser(data.user);
            showToast(`Welcome back, ${data.user.fullname}!`);
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1000);
        } else {
            showToast(data.message || 'Login failed', true);
        }
    } catch(error) {
        console.error('Login error:', error);
        showToast('Connection error. Make sure your server is running.', true);
    }
}

// Register function
async function registerUser() {
    const fullname = document.getElementById('regFullname').value;
    const username = document.getElementById('regUsername').value;
    const email = document.getElementById('regEmail').value;
    const phone = document.getElementById('regPhone').value;
    const address = document.getElementById('regAddress').value;
    const password = document.getElementById('regPassword').value;
    const confirm = document.getElementById('regConfirmPassword').value;
    
    if (!fullname || !username || !email || !password) {
        showToast('Please fill all required fields', true);
        return;
    }
    
    if (password.length < 6) {
        showToast('Password must be at least 6 characters', true);
        return;
    }
    
    if (password !== confirm) {
        showToast('Passwords do not match', true);
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/api_register.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fullname, username, email, password, phone, address })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Registration successful! Please login.');
            closeModal('registerModal');
            // Clear form
            document.getElementById('regFullname').value = '';
            document.getElementById('regUsername').value = '';
            document.getElementById('regEmail').value = '';
            document.getElementById('regPhone').value = '';
            document.getElementById('regAddress').value = '';
            document.getElementById('regPassword').value = '';
            document.getElementById('regConfirmPassword').value = '';
        } else {
            showToast(data.message || 'Registration failed', true);
        }
    } catch(error) {
        console.error('Registration error:', error);
        showToast('Connection error', true);
    }
}

// Logout function
function logout() {
    clearCurrentUser();
    localStorage.removeItem('cart');
    showToast('Logged out successfully');
    window.location.href = 'index.html';
}

// Function to close modals
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

// Function to show modals
function showLoginModal() {
    const modal = document.getElementById('loginModal');
    if (modal) modal.style.display = 'flex';
}

function showRegisterModal() {
    const modal = document.getElementById('registerModal');
    if (modal) modal.style.display = 'flex';
}

// Check login status on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCurrentUser();
});