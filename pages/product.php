<?php
session_start();
require_once '../includes/auth.php';

$user = getCurrentUser();
$message = getMessage();

// Get product ID from URL
$product_id = $_GET['id'] ?? 0;
if (!$product_id) {
    header('Location: products.php');
    exit;
}

// Fetch product details
require_once '../config/database.php';

$stmt = $conn->prepare("
    SELECT p.*, s.shop_name, s.address, s.phone, s.status as shop_status 
    FROM products p 
    JOIN shops s ON p.shop_id = s.id 
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: products.php');
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - LocalPhone Marketplace</title>
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

        <div class="product-detail">
            <!-- Product Image -->
            <div class="product-detail-image">
                <img src="<?php echo $product['image'] ? '../uploads/' . $product['image'] : 'https://via.placeholder.com/600x400?text=No+Image'; ?>" 
                     alt="<?php echo htmlspecialchars($product['title']); ?>">
            </div>

            <!-- Product Info -->
            <div class="product-detail-info">
                <h1 class="product-detail-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                <p class="product-detail-price"><?php echo '₹' . number_format($product['price'], 2); ?></p>
                
                <div class="product-detail-specs">
                    <p><strong>Brand:</strong> <?php echo htmlspecialchars($product['brand']); ?></p>
                    <p><strong>Model:</strong> <?php echo htmlspecialchars($product['model']); ?></p>
                    <p><strong>Condition:</strong> 
                        <span class="condition-badge <?php echo getConditionClass($product['condition']); ?>">
                            <?php echo htmlspecialchars($product['condition']); ?>
                        </span>
                    </p>
                    <p><strong>Storage:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
                </div>

                <!-- Seller Info -->
                <div class="product-detail-seller">
                    <h3>Seller Information</h3>
                    <p><strong>Shop:</strong> <a href="shop.php?id=<?php echo $product['shop_id']; ?>"><?php echo htmlspecialchars($product['shop_name']); ?></a></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($product['address']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($product['phone']); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="status-badge <?php echo $product['shop_status'] === 'approved' ? 'status-approved' : 'status-pending'; ?>">
                            <?php echo ucfirst($product['shop_status']); ?>
                        </span>
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex gap-2" style="margin-top: 2rem;">
                    <button id="contactSellerBtn" class="btn btn-primary">Contact Seller</button>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $product['phone']); ?>?text=Hello,%20I%20am%20interested%20in%20your%20<?php echo urlencode($product['title']); ?>." 
                       class="btn btn-secondary whatsapp-btn" 
                       data-phone="<?php echo preg_replace('/[^0-9]/', '', $product['phone']); ?>"
                       data-message="Hello, I am interested in your <?php echo htmlspecialchars($product['title']); ?>.">
                        💬 WhatsApp
                    </a>
                    <?php if ($user): ?>
                        <button class="btn btn-outline wishlist-btn" data-product-id="<?php echo $product['id']; ?>">
                            ♥ Add to Wishlist
                        </button>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline">Login to Wishlist</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Product Description -->
        <div class="product-detail-info mt-3">
            <h3>Description</h3>
            <p><?php echo htmlspecialchars($product['description']); ?></p>
        </div>
    </main>

    <!-- Inquiry Modal -->
    <div id="inquiryModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Contact Seller</h2>
                <button class="modal-close">&times;</button>
            </div>
            <form id="inquiryForm">
                <input type="hidden" id="inquiryProductId" value="<?php echo $product['id']; ?>">
                
                <?php if (!$user): ?>
                    <div class="form-group">
                        <label for="inquiryEmail">Your Email</label>
                        <input type="email" id="inquiryEmail" name="contact_email" required placeholder="Enter your email">
                    </div>
                    <div class="form-group">
                        <label for="inquiryPhone">Your Phone</label>
                        <input type="tel" id="inquiryPhone" name="contact_phone" placeholder="Enter your phone number">
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="inquiryMessage">Your Message</label>
                    <textarea id="inquiryMessage" name="message" required placeholder="Enter your message to the seller"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Send Message</button>
            </form>
        </div>
    </div>

    <?php
    // Helper function for condition class
    function getConditionClass($condition) {
        $conditions = [
            'New' => 'condition-new',
            'Like New' => 'condition-like-new',
            'Good' => 'condition-good',
            'Fair' => 'condition-fair',
            'Poor' => 'condition-poor'
        ];
        return $conditions[$condition] ?? 'condition-good';
    }
    ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>