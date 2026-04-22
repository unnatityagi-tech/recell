// LocalPhone Marketplace - Main JavaScript

// API Base URL - Dynamic path detection
const API_BASE = (() => {
    const path = window.location.pathname;
    if (path.includes('/pages/')) {
        return '../api/';
    } else {
        return 'api/';
    }
})();

// Utility functions
function formatPrice(price) {
    return '₹' + parseFloat(price).toLocaleString('en-IN');
}

function getConditionClass(condition) {
    const conditions = {
        'New': 'condition-new',
        'Like New': 'condition-like-new',
        'Good': 'condition-good',
        'Fair': 'condition-fair',
        'Poor': 'condition-poor'
    };
    return conditions[condition] || 'condition-good';
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = message;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        setTimeout(() => alertDiv.remove(), 5000);
    }
}

// API Functions
async function fetchProducts(filters = {}) {
    const params = new URLSearchParams(filters);
    const url = `${API_BASE}products.php?${params}`;
    
    try {
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status} ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text.substring(0, 500));
            throw new Error('Response is not JSON. Content-Type: ' + contentType);
        }
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('fetchProducts error:', error);
        throw error;
    }
}

async function fetchProduct(id) {
    const response = await fetch(`${API_BASE}products.php/${id}`);
    const data = await response.json();
    return data;
}

// Render product card
function renderProductCard(product) {
    const isPagesDir = window.location.pathname.includes('/pages/');
    const imageSrc = product.image ? 
        (isPagesDir ? `../uploads/${product.image}` : `uploads/${product.image}`) : 
        'https://via.placeholder.com/300x200?text=No+Image';
    const conditionClass = getConditionClass(product.condition);
    
    return `
        <div class="product-card" data-product-id="${product.id}">
            <img src="${imageSrc}" alt="${product.title}" class="product-card-image">
            <div class="product-card-content">
                <h3 class="product-card-title">${product.title}</h3>
                <p class="product-card-price">${formatPrice(product.price)}</p>
                <div class="product-card-meta">
                    <span>${product.brand} ${product.model}</span>
                    <span class="condition-badge ${conditionClass}">${product.condition}</span>
                </div>
                <p class="text-muted" style="font-size: 0.8rem;">
                    <small>${product.shop_name} - ${product.location || 'N/A'}</small>
                </p>
                <div class="product-card-actions mt-1">
                    <a href="${isPagesDir ? 'product.php' : 'pages/product.php'}?id=${product.id}" class="btn btn-primary btn-sm">View Details</a>
                    <button class="btn btn-outline btn-sm wishlist-btn" data-product-id="${product.id}">
                        &#9829; Wishlist
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Load products into grid
async function loadProducts(containerSelector, filters = {}) {
    const container = document.querySelector(containerSelector);
    if (!container) return;
    
    container.innerHTML = '<p class="text-center">Loading products...</p>';
    
    try {
        const data = await fetchProducts(filters);
        
        if (data.success && data.products.length > 0) {
            container.innerHTML = data.products.map(p => renderProductCard(p)).join('');
        } else {
            container.innerHTML = '<p class="text-center">No products found.</p>';
        }
    } catch (error) {
        console.error('Error loading products:', error);
        container.innerHTML = '<p class="text-center text-muted">Error loading products. Please try again.</p>';
    }
}

// Search functionality
function setupSearch() {
    const searchForm = document.getElementById('searchForm');
    if (!searchForm) return;
    
    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput');
        const search = searchInput ? searchInput.value.trim() : '';
        
        if (search) {
            window.location.href = `pages/products.php?search=${encodeURIComponent(search)}`;
        }
    });
}

// Filter functionality
function setupFilters() {
    const filterForm = document.getElementById('filterForm');
    if (!filterForm) return;
    
    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const brand = document.getElementById('filterBrand')?.value || '';
        const condition = document.getElementById('filterCondition')?.value || '';
        const minPrice = document.getElementById('filterMinPrice')?.value || '';
        const maxPrice = document.getElementById('filterMaxPrice')?.value || '';
        const location = document.getElementById('filterLocation')?.value || '';
        
        const params = new URLSearchParams();
        if (brand) params.set('brand', brand);
        if (condition) params.set('condition', condition);
        if (minPrice) params.set('min_price', minPrice);
        if (maxPrice) params.set('max_price', maxPrice);
        if (location) params.set('location', location);
        
        window.location.href = `products.php?${params.toString()}`;
    });
}

// Wishlist functionality
function setupWishlist() {
    document.addEventListener('click', async (e) => {
        if (e.target.classList.contains('wishlist-btn')) {
            e.preventDefault();
            const productId = e.target.dataset.productId;
            
            try {
                const response = await fetch(`${API_BASE}wishlist.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: parseInt(productId) })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    e.target.textContent = '♥ Saved';
                    e.target.disabled = true;
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                console.error('Error adding to wishlist:', error);
                showAlert('Please login to add items to wishlist', 'error');
            }
        }
    });
}

// Contact/Inquiry modal
function setupInquiryModal() {
    const modal = document.getElementById('inquiryModal');
    const openBtn = document.getElementById('contactSellerBtn');
    const closeBtn = document.querySelector('.modal-close');
    
    if (!modal || !openBtn) return;
    
    openBtn.addEventListener('click', () => {
        modal.classList.add('active');
    });
    
    closeBtn?.addEventListener('click', () => {
        modal.classList.remove('active');
    });
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
    
    // Handle inquiry form submission
    const inquiryForm = document.getElementById('inquiryForm');
    if (inquiryForm) {
        inquiryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const productId = document.getElementById('inquiryProductId')?.value;
            const message = document.getElementById('inquiryMessage')?.value;
            const contactEmail = document.getElementById('inquiryEmail')?.value;
            const contactPhone = document.getElementById('inquiryPhone')?.value;
            
            try {
                const response = await fetch(`${API_BASE}inquiry.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        product_id: parseInt(productId),
                        message,
                        contact_email: contactEmail,
                        contact_phone: contactPhone
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    modal.classList.remove('active');
                    inquiryForm.reset();
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                console.error('Error sending inquiry:', error);
                showAlert('Error sending inquiry. Please try again.', 'error');
            }
        });
    }
}

// Login/Register form handling
function setupAuthForms() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('loginEmail')?.value;
            const password = document.getElementById('loginPassword')?.value;
            
            console.log('Login attempt:', { email, apiBase: API_BASE });
            
            try {
                const url = `${API_BASE}login.php`;
                console.log('Fetching from:', url);
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                
                console.log('Response status:', response.status, response.statusText);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status} ${response.statusText}`);
                }
                
                const contentType = response.headers.get('content-type');
                console.log('Content-Type:', contentType);
                
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text.substring(0, 500));
                    throw new Error('Server returned non-JSON response');
                }
                
                const data = await response.json();
                console.log('Login response:', data);
                
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showAlert(data.message || 'Login failed', 'error');
                }
            } catch (error) {
                console.error('Login error:', error);
                showAlert(`Login error: ${error.message}. Please check console for details.`, 'error');
            }
        });
    }
    
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const name = document.getElementById('registerName')?.value;
            const email = document.getElementById('registerEmail')?.value;
            const password = document.getElementById('registerPassword')?.value;
            const confirmPassword = document.getElementById('registerConfirmPassword')?.value;
            const role = document.getElementById('registerRole')?.value;
            const shopName = document.getElementById('shopName')?.value;
            const shopAddress = document.getElementById('shopAddress')?.value;
            const shopPhone = document.getElementById('shopPhone')?.value;
            
            if (password !== confirmPassword) {
                showAlert('Passwords do not match', 'error');
                return;
            }
            
            try {
                const response = await fetch(`${API_BASE}register.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name,
                        email,
                        password,
                        role,
                        shop_name: shopName,
                        shop_address: shopAddress,
                        shop_phone: shopPhone
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect || '../index.php';
                    }, 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                console.error('Error registering:', error);
                showAlert('Error registering. Please try again.', 'error');
            }
        });
    }
}

// Shop dashboard functions
function setupShopDashboard() {
    const addProductForm = document.getElementById('addProductForm');
    
    if (addProductForm) {
        addProductForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(addProductForm);
            
            try {
                const response = await fetch(`${API_BASE}products.php`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    addProductForm.reset();
                    // Reload products
                    loadProducts('#productsList');
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                console.error('Error adding product:', error);
                showAlert('Error adding product. Please try again.', 'error');
            }
        });
    }
    
    // Delete product
    document.addEventListener('click', async (e) => {
        if (e.target.classList.contains('delete-product')) {
            if (!confirm('Are you sure you want to delete this product?')) return;
            
            const productId = e.target.dataset.productId;
            
            try {
                const response = await fetch(`${API_BASE}products.php/${productId}`, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Remove from DOM
                    e.target.closest('tr')?.remove();
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting product:', error);
                showAlert('Error deleting product.', 'error');
            }
        }
    });
}

// Admin dashboard functions
function setupAdminDashboard() {
    // Approve/Reject shop
    document.addEventListener('click', async (e) => {
        if (e.target.classList.contains('approve-shop') || e.target.classList.contains('reject-shop')) {
            const shopId = e.target.dataset.shopId;
            const action = e.target.classList.contains('approve-shop') ? 'approved' : 'rejected';
            
            if (!confirm(`Are you sure you want to ${action} this shop?`)) return;
            
            // This would need an admin API endpoint
            showAlert('Shop management coming soon!', 'info');
        }
    });
    
    // Remove listing
    document.addEventListener('click', async (e) => {
        if (e.target.classList.contains('remove-listing')) {
            const productId = e.target.dataset.productId;
            
            if (!confirm('Are you sure you want to remove this listing?')) return;
            
            // This would need an admin API endpoint
            showAlert('Listing management coming soon!', 'info');
        }
    });
}

// WhatsApp integration
function setupWhatsApp() {
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('whatsapp-btn')) {
            e.preventDefault();
            const phone = e.target.dataset.phone;
            const message = e.target.dataset.message || 'Hello, I am interested in your product.';
            
            const whatsappUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        }
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    setupSearch();
    setupFilters();
    setupWishlist();
    setupInquiryModal();
    setupAuthForms();
    setupShopDashboard();
    setupAdminDashboard();
    setupWhatsApp();
    
    // Auto-load products only on home page (not on browse page which has its own loader)
    const isBrowsePage = window.location.pathname.includes('/products.php');
    const productsGrid = document.querySelector('#productsGrid');
    if (productsGrid && !isBrowsePage) {
        loadProducts('#productsGrid');
    }
});