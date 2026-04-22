<?php
session_start();
require_once '../includes/auth.php';

requireLogin();
$user = getCurrentUser();
$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - LocalPhone Marketplace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container container">
            <a href="../index.php" class="navbar-logo">Local<span>Phone</span></a>
            <ul class="navbar-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="products.php">Browse Phones</a></li>
                <?php if ($user['role'] === 'shop'): ?>
                    <li><a href="shop-dashboard.php">Dashboard</a></li>
                <?php endif; ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <li><a href="../admin/dashboard.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="wishlist.php" class="active">Wishlist</a></li>
                <li><a href="../api/logout.php">Logout (<?php echo htmlspecialchars($user['name']); ?>)</a></li>
            </ul>
        </div>
    </nav>

    <main class="container" style="padding: 2rem 0;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?>">
                <?php echo htmlspecialchars($message['message']); ?>
            </div>
        <?php endif; ?>

        <div class="flex-between mb-2">
            <h1>My Wishlist</h1>
            <a href="products.php" class="btn btn-primary">Browse More Phones</a>
        </div>

        <div class="products-grid" id="wishlistGrid">
            <!-- Wishlist items will be loaded dynamically -->
            <p class="text-center">Loading wishlist...</p>
        </div>
    </main>

    <script>
        // Load wishlist items
        document.addEventListener('DOMContentLoaded', () => {
            loadWishlist();
        });

        async function loadWishlist() {
            const container = document.getElementById('wishlistGrid');
            container.innerHTML = '<p class="text-center">Loading wishlist...</p>';
            
            try {
                const response = await fetch('../api/wishlist.php');
                const data = await response.json();
                
                if (data.success && data.wishlist.length > 0) {
                    container.innerHTML = data.wishlist.map(p => renderWishlistItem(p)).join('');
                } else {
                    container.innerHTML = `
                        <div class="product-card" style="padding: 2rem; text-align: center;">
                            <h3>Your wishlist is empty</h3>
                            <p class="text-muted">Start browsing phones and add your favorites to your wishlist!</p>
                            <a href="products.php" class="btn btn-primary">Browse Phones</a>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading wishlist:', error);
                container.innerHTML = '<p class="text-center text-muted">Error loading wishlist. Please try again.</p>';
            }
        }

        function renderWishlistItem(product) {
            const imageSrc = product.image ? `../uploads/${product.image}` : 'https://via.placeholder.com/300x200?text=No+Image';
            const conditionClass = getConditionClass(product.condition);
            
            return `
                <div class="product-card" data-product-id="${product.product_id}">
                    <img src="${imageSrc}" alt="${product.title}" class="product-card-image">
                    <div class="product-card-content">
                        <h3 class="product-card-title">${product.title}</h3>
                        <p class="product-card-price">₹${product.price.toLocaleString('en-IN')}</p>
                        <div class="product-card-meta">
                            <span>${product.brand} ${product.model}</span>
                            <span class="condition-badge ${conditionClass}">${product.condition}</span>
                        </div>
                        <p class="text-muted" style="font-size: 0.8rem;">
                            <small>${product.shop_name} - ${product.address || 'N/A'}</small>
                        </p>
                        <div class="product-card-actions mt-1">
                            <a href="product.php?id=${product.product_id}" class="btn btn-primary btn-sm">View Details</a>
                            <button class="btn btn-danger btn-sm remove-wishlist-btn" data-product-id="${product.product_id}">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            `;
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

        // Handle remove from wishlist
        document.addEventListener('click', async (e) => {
            if (e.target.classList.contains('remove-wishlist-btn')) {
                const productId = e.target.dataset.productId;
                
                try {
                    const response = await fetch('../api/wishlist.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ product_id: parseInt(productId) })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showAlert(data.message, 'success');
                        loadWishlist();
                    } else {
                        showAlert(data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error removing from wishlist:', error);
                    showAlert('Error removing from wishlist. Please try again.', 'error');
                }
            }
        });
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>