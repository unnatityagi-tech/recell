<?php
session_start();
require_once '../includes/auth.php';

$user = getCurrentUser();
$message = getMessage();

// Get filters from URL
$brand = $_GET['brand'] ?? '';
$condition = $_GET['condition'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$location = $_GET['location'] ?? '';
$search = $_GET['search'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Phones - LocalPhone Marketplace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .debug-info {
            background: #f0f0f0;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid #333;
            font-family: monospace;
            font-size: 12px;
        }
        .error-details {
            background: #ffe6e6;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid #ff0000;
            color: #ff0000;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container container">
            <a href="../index.php" class="navbar-logo">Local<span>Phone</span></a>
            <ul class="navbar-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="products.php" class="active">Browse Phones</a></li>
                <?php if ($user): ?>
                    <?php if ($user['role'] === 'shop'): ?>
                        <li><a href="shop-dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'admin'): ?>
                        <li><a href="../admin/dashboard.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="wishlist.php">Wishlist</a></li>
                    <li><a href="../api/logout.php">Logout (<?php echo htmlspecialchars($user['name']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn btn-primary">Login</a></li>
                    <li><a href="register.php" class="btn btn-secondary">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main class="container" style="padding: 2rem 0;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?>">
                <?php echo htmlspecialchars($message['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Debug Info (hidden by default, shown on error) -->
        <div id="debugInfo" class="debug-info" style="display: none;">
            <h4>Debug Information</h4>
            <div id="debugContent"></div>
        </div>

        <div class="dashboard-layout">
            <!-- Filters Sidebar -->
            <aside class="filters-sidebar">
                <h3>Filters</h3>
                
                <form id="filterForm" onsubmit="applyFilters(event)">
                    <div class="filter-group">
                        <label for="filterBrand">Brand</label>
                        <select id="filterBrand" name="brand">
                            <option value="">All Brands</option>
                            <option value="Apple" <?php echo $brand === 'Apple' ? 'selected' : ''; ?>>Apple</option>
                            <option value="Samsung" <?php echo $brand === 'Samsung' ? 'selected' : ''; ?>>Samsung</option>
                            <option value="OnePlus" <?php echo $brand === 'OnePlus' ? 'selected' : ''; ?>>OnePlus</option>
                            <option value="Xiaomi" <?php echo $brand === 'Xiaomi' ? 'selected' : ''; ?>>Xiaomi</option>
                            <option value="Google" <?php echo $brand === 'Google' ? 'selected' : ''; ?>>Google Pixel</option>
                            <option value="Realme" <?php echo $brand === 'Realme' ? 'selected' : ''; ?>>Realme</option>
                            <option value="Vivo" <?php echo $brand === 'Vivo' ? 'selected' : ''; ?>>Vivo</option>
                            <option value="Oppo" <?php echo $brand === 'Oppo' ? 'selected' : ''; ?>>Oppo</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filterCondition">Condition</label>
                        <select id="filterCondition" name="condition">
                            <option value="">All Conditions</option>
                            <option value="New" <?php echo $condition === 'New' ? 'selected' : ''; ?>>New</option>
                            <option value="Like New" <?php echo $condition === 'Like New' ? 'selected' : ''; ?>>Like New</option>
                            <option value="Good" <?php echo $condition === 'Good' ? 'selected' : ''; ?>>Good</option>
                            <option value="Fair" <?php echo $condition === 'Fair' ? 'selected' : ''; ?>>Fair</option>
                            <option value="Poor" <?php echo $condition === 'Poor' ? 'selected' : ''; ?>>Poor</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Price Range</label>
                        <div class="price-range">
                            <input type="number" id="filterMinPrice" name="min_price" placeholder="Min Price" value="<?php echo $min_price; ?>">
                            <span>to</span>
                            <input type="number" id="filterMaxPrice" name="max_price" placeholder="Max Price" value="<?php echo $max_price; ?>">
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filterLocation">Location</label>
                        <input type="text" id="filterLocation" name="location" placeholder="Enter location" value="<?php echo $location; ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Filters</button>
                </form>
                
                <div class="mt-2">
                    <a href="products.php" class="btn btn-outline" style="width: 100%;">Clear Filters</a>
                </div>
            </aside>

            <!-- Products Grid -->
            <div>
                <div class="flex-between mb-2">
                    <h2>Available Phones</h2>
                </div>
                
                <div id="productsGrid">
                    <p class="text-center">Loading products...</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Standalone JavaScript - doesn't depend on main.js
        console.log('Browse page standalone script starting...');
        
        // API base URL calculation
        const API_BASE = '../api/';
        
        // Format price function
        function formatPrice(price) {
            return '₹' + parseFloat(price).toLocaleString('en-IN');
        }
        
        // Get condition class
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
        
        // Render product card
        function renderProductCard(product) {
            const imageSrc = product.image ? `../uploads/${product.image}` : 'https://via.placeholder.com/300x200?text=No+Image';
            const conditionClass = getConditionClass(product.condition);
            
            return `
                <div class="product-card" data-product-id="${product.id}">
                    <img src="${imageSrc}" alt="${product.title}" class="product-card-image" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                    <div class="product-card-content">
                        <h3 class="product-card-title">${product.title}</h3>
                        <p class="product-card-price">${formatPrice(product.price)}</p>
                        <div class="product-card-meta">
                            <span>${product.brand} ${product.model}</span>
                            <span class="condition-badge ${conditionClass}">${product.condition}</span>
                        </div>
                        <p class="text-muted" style="font-size: 0.8rem;">
                            <small><a href="shop.php?id=${product.shop_id}" style="color: inherit; text-decoration: underline;">${product.shop_name}</a> - ${product.location || 'N/A'}</small>
                        </p>
                        <div class="product-card-actions mt-1">
                            <a href="product.php?id=${product.id}" class="btn btn-primary btn-sm">View Details</a>
                            <button class="btn btn-outline btn-sm" onclick="addToWishlist(${product.id})">♥ Wishlist</button>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Load products function
        async function loadProducts() {
            const grid = document.getElementById('productsGrid');
            
            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const filters = {};
            
            if (urlParams.get('brand')) filters.brand = urlParams.get('brand');
            if (urlParams.get('condition')) filters.condition = urlParams.get('condition');
            if (urlParams.get('min_price')) filters.min_price = urlParams.get('min_price');
            if (urlParams.get('max_price')) filters.max_price = urlParams.get('max_price');
            if (urlParams.get('location')) filters.location = urlParams.get('location');
            if (urlParams.get('search')) filters.search = urlParams.get('search');
            
            console.log('Loading products with filters:', filters);
            
            try {
                // Build query string
                const params = new URLSearchParams(filters);
                const url = API_BASE + 'products.php?' + params.toString();
                
                console.log('Fetching from:', url);
                
                const response = await fetch(url);
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error('HTTP error! Status: ' + response.status);
                }
                
                const data = await response.json();
                console.log('Response data:', data);
                
                if (data.success && data.products && data.products.length > 0) {
                    grid.innerHTML = data.products.map(p => renderProductCard(p)).join('');
                } else if (data.success) {
                    grid.innerHTML = '<p class="text-center">No products found matching your criteria.</p>';
                } else {
                    throw new Error(data.message || 'Failed to load products');
                }
            } catch (error) {
                console.error('Error loading products:', error);
                
                // Show debug info
                const debugDiv = document.getElementById('debugInfo');
                const debugContent = document.getElementById('debugContent');
                debugDiv.style.display = 'block';
                debugContent.innerHTML = `
                    <p><strong>Error:</strong> ${error.message}</p>
                    <p><strong>Stack:</strong> ${error.stack}</p>
                    <p><strong>URL:</strong> ${window.location.href}</p>
                    <p><strong>API Base:</strong> ${API_BASE}</p>
                `;
                
                grid.innerHTML = `
                    <div class="error-details">
                        <h4>Error Loading Products</h4>
                        <p>${error.message}</p>
                        <p>Please check the browser console for more details.</p>
                    </div>
                `;
            }
        }
        
        // Apply filters function
        function applyFilters(event) {
            event.preventDefault();
            
            const brand = document.getElementById('filterBrand').value;
            const condition = document.getElementById('filterCondition').value;
            const minPrice = document.getElementById('filterMinPrice').value;
            const maxPrice = document.getElementById('filterMaxPrice').value;
            const location = document.getElementById('filterLocation').value;
            
            const params = new URLSearchParams();
            if (brand) params.set('brand', brand);
            if (condition) params.set('condition', condition);
            if (minPrice) params.set('min_price', minPrice);
            if (maxPrice) params.set('max_price', maxPrice);
            if (location) params.set('location', location);
            
            window.location.href = 'products.php?' + params.toString();
        }
        
        // Add to wishlist function
        async function addToWishlist(productId) {
            try {
                const response = await fetch(API_BASE + 'wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Added to wishlist!');
                } else {
                    alert(data.message || 'Please login to add to wishlist');
                }
            } catch (error) {
                console.error('Error adding to wishlist:', error);
                alert('Please login to add items to wishlist');
            }
        }
        
        // Load products when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, starting product load...');
            loadProducts();
        });
    </script>
</body>
</html>
