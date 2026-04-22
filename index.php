<?php
session_start();
require_once 'includes/auth.php';

$user = getCurrentUser();
$message = getMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalPhone Marketplace - Buy & Sell Second-Hand Phones</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container container">
            <a href="index.php" class="navbar-logo">Local<span>Phone</span></a>
            <ul class="navbar-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="pages/products.php">Browse Phones</a></li>
                <?php if ($user): ?>
                    <?php if ($user['role'] === 'shop'): ?>
                        <li><a href="pages/shop-dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'admin'): ?>
                        <li><a href="admin/dashboard.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="pages/wishlist.php">Wishlist</a></li>
                    <li><a href="api/logout.php">Logout (<?php echo htmlspecialchars($user['name']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="pages/login.php" class="btn btn-primary">Login</a></li>
                    <li><a href="pages/register.php" class="btn btn-secondary">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Find Your Perfect Second-Hand Phone</h1>
            <p>Browse through verified listings from local shops near you</p>
            
            <!-- Search Bar -->
            <form class="search-bar" id="searchForm">
                <input type="text" id="searchInput" placeholder="Search for phones (e.g., iPhone, Samsung, OnePlus...)">
                <button type="submit">Search</button>
            </form>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?>">
                <?php echo htmlspecialchars($message['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Quick Categories -->
        <section class="mb-3">
            <h2 class="mb-2">Popular Brands</h2>
            <div class="flex gap-1" style="flex-wrap: wrap;">
                <a href="pages/products.php?brand=Apple" class="btn btn-outline">🍎 Apple</a>
                <a href="pages/products.php?brand=Samsung" class="btn btn-outline">📱 Samsung</a>
                <a href="pages/products.php?brand=OnePlus" class="btn btn-outline">🔴 OnePlus</a>
                <a href="pages/products.php?brand=Xiaomi" class="btn btn-outline">🔶 Xiaomi</a>
                <a href="pages/products.php?brand=Google" class="btn btn-outline">🔵 Google Pixel</a>
                <a href="pages/products.php?brand=Realme" class="btn btn-outline">💛 Realme</a>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="mb-3">
            <div class="flex-between mb-2">
                <h2>Featured Phones</h2>
                <a href="pages/products.php" class="btn btn-primary">View All</a>
            </div>
            <div class="products-grid" id="productsGrid">
                <!-- Products will be loaded dynamically -->
                <p class="text-center">Loading products...</p>
            </div>
        </section>

        <!-- Why Choose Us -->
        <section class="mb-3">
            <h2 class="text-center mb-2">Why Choose LocalPhone?</h2>
            <div class="products-grid">
                <div class="product-card" style="padding: 2rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
                    <h3>Verified Sellers</h3>
                    <p class="text-muted">All shops are verified and approved by our team</p>
                </div>
                <div class="product-card" style="padding: 2rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💰</div>
                    <h3>Best Prices</h3>
                    <p class="text-muted">Compare prices from multiple local shops</p>
                </div>
                <div class="product-card" style="padding: 2rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📍</div>
                    <h3>Near You</h3>
                    <p class="text-muted">Find phones available in your locality</p>
                </div>
                <div class="product-card" style="padding: 2rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
                    <h3>Easy Contact</h3>
                    <p class="text-muted">Direct WhatsApp integration with sellers</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 LocalPhone Marketplace. All rights reserved.</p>
            <p class="mt-1">Connecting buyers with trusted local phone sellers</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>