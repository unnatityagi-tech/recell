<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/database.php';

$shop_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($shop_id <= 0) {
    header('Location: products.php');
    exit;
}

// Get shop details
$stmt = $conn->prepare("
    SELECT s.*, u.name as owner_name, u.email as owner_email
    FROM shops s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ? AND s.status = 'approved'
");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$shop_result = $stmt->get_result();
$shop = $shop_result->fetch_assoc();
$stmt->close();

if (!$shop) {
    header('Location: products.php');
    exit;
}

// Get shop's products
$stmt = $conn->prepare("
    SELECT * FROM products
    WHERE shop_id = ? AND is_active = 1
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($shop['shop_name']); ?> - LocalPhone Marketplace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .shop-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .shop-info-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .shop-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }
        .shop-stat {
            text-align: center;
        }
        .shop-stat h4 {
            font-size: 2rem;
            color: #667eea;
            margin: 0;
        }
        .shop-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .shop-product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .shop-product-card:hover {
            transform: translateY(-5px);
        }
        .shop-product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .shop-product-info {
            padding: 1rem;
        }
        .shop-product-info h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
        }
        .shop-product-info .price {
            font-size: 1.2rem;
            color: #667eea;
            font-weight: bold;
        }
        .contact-info {
            margin-top: 1.5rem;
        }
        .contact-info p {
            margin: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: #d4edda;
            color: #155724;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 0.5rem;
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
                <li><a href="products.php">Browse Phones</a></li>
                <?php if ($user): ?>
                    <?php if ($user['role'] === 'shop'): ?>
                        <li><a href="shop-dashboard.php">Dashboard</a></li>
                    <?php elseif ($user['role'] === 'admin'): ?>
                        <li><a href="../admin/dashboard.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="../api/logout.php">Logout (<?php echo htmlspecialchars($user['name']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php" class="btn btn-secondary">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Shop Header -->
    <div class="shop-header">
        <div class="container">
            <div class="flex-between" style="align-items: center;">
                <div>
                    <h1><?php echo htmlspecialchars($shop['shop_name']); ?></h1>
                    <span class="verified-badge">✓ Verified Shop</span>
                </div>
                <div class="shop-stats">
                    <div class="shop-stat">
                        <h4><?php echo count($products); ?></h4>
                        <p>Products</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main class="container" style="padding-bottom: 3rem;">
        <div class="dashboard-layout">
            <!-- Shop Info Sidebar -->
            <aside style="flex: 0 0 300px;">
                <div class="shop-info-card">
                    <h3>About Shop</h3>
                    
                    <div class="contact-info">
                        <p>
                            <span>📍</span>
                            <span><?php echo nl2br(htmlspecialchars($shop['address'])); ?></span>
                        </p>
                        <p>
                            <span>📞</span>
                            <span><?php echo htmlspecialchars($shop['phone']); ?></span>
                        </p>
                        <p>
                            <span>👤</span>
                            <span>Owner: <?php echo htmlspecialchars($shop['owner_name']); ?></span>
                        </p>
                        <p>
                            <span>📧</span>
                            <span><?php echo htmlspecialchars($shop['owner_email']); ?></span>
                        </p>
                        <p>
                            <span>📅</span>
                            <span>Member since: <?php echo date('M Y', strtotime($shop['created_at'])); ?></span>
                        </p>
                    </div>
                    
                    <?php if ($user && $user['role'] === 'customer'): ?>
                        <div style="margin-top: 1.5rem;">
                            <a href="inquiry.php?shop_id=<?php echo $shop['id']; ?>" class="btn btn-primary" style="width: 100%;">Contact Shop</a>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>

            <!-- Products Section -->
            <div style="flex: 1;">
                <h2>Products from <?php echo htmlspecialchars($shop['shop_name']); ?></h2>
                
                <?php if (count($products) > 0): ?>
                    <div class="shop-products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="shop-product-card">
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <img src="../uploads/<?php echo $product['image'] ?: 'placeholder.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                                         onerror="this.src='https://via.placeholder.com/250x200?text=No+Image'">
                                </a>
                                <div class="shop-product-info">
                                    <h4><?php echo htmlspecialchars($product['title']); ?></h4>
                                    <p class="text-muted"><?php echo htmlspecialchars($product['brand']); ?> • <?php echo $product['condition']; ?></p>
                                    <p class="price">₹<?php echo number_format($product['price']); ?></p>
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary" style="width: 100%; margin-top: 0.5rem;">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="product-card" style="padding: 3rem; text-align: center; margin-top: 2rem;">
                        <h3>No products available</h3>
                        <p class="text-muted">This shop hasn't listed any products yet.</p>
                        <a href="products.php" class="btn btn-primary" style="margin-top: 1rem;">Browse Other Shops</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer style="background: #f8f9fa; padding: 2rem 0; margin-top: 3rem;">
        <div class="container text-center">
            <p class="text-muted">LocalPhone Marketplace &copy; <?php echo date('Y'); ?></p>
        </div>
    </footer>
</body>
</html>
