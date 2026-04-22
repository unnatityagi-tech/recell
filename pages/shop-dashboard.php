<?php
session_start();
require_once '../includes/auth.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] !== 'shop') {
    header('Location: ../index.php');
    exit;
}

$message = getMessage();

// Get shop details
require_once '../config/database.php';
$stmt = $conn->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$shop_result = $stmt->get_result();
$shop = $shop_result->fetch_assoc();
$stmt->close();

if (!$shop) {
    header('Location: register.php');
    exit;
}

// Get products count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE shop_id = ? AND is_active = 1");
$stmt->bind_param("i", $shop['id']);
$stmt->execute();
$products_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Get active products
$stmt = $conn->prepare("SELECT * FROM products WHERE shop_id = ? AND is_active = 1 ORDER BY created_at DESC");
$stmt->bind_param("i", $shop['id']);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Helper function for condition classes
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Dashboard - LocalPhone Marketplace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-section {
            display: none;
        }
        .dashboard-section.active {
            display: block;
        }
        .sidebar-menu a {
            display: block;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 4px;
            cursor: pointer;
        }
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #2196F3;
            color: white;
        }
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .loading-overlay.active {
            display: flex;
        }
        .loading-spinner {
            background: white;
            padding: 20px 40px;
            border-radius: 8px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">Processing...</div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container container">
            <a href="../index.php" class="navbar-logo">Local<span>Phone</span></a>
            <ul class="navbar-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="products.php">Browse Phones</a></li>
                <li><a href="shop-dashboard.php" class="active">Dashboard</a></li>
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

        <!-- Dynamic Message Container -->
        <div id="messageContainer"></div>

        <div class="dashboard-layout">
            <!-- Sidebar -->
            <aside class="dashboard-sidebar">
                <h3>Shop Dashboard</h3>
                <div class="sidebar-menu">
                    <a href="#" class="active" data-section="products" onclick="showSection('products'); return false;">My Products</a>
                    <a href="#" data-section="add-product" onclick="showSection('add-product'); return false;">Add Product</a>
                    <a href="#" data-section="shop-info" onclick="showSection('shop-info'); return false;">Shop Info</a>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="dashboard-content">
                <!-- Shop Status Header -->
                <div class="mb-2">
                    <h2>Shop: <?php echo htmlspecialchars($shop['shop_name']); ?></h2>
                    <p>Status: 
                        <span class="status-badge <?php echo 'status-' . $shop['status']; ?>">
                            <?php echo ucfirst($shop['status']); ?>
                        </span>
                    </p>
                </div>

                <!-- Stats -->
                <div class="flex gap-2 mb-3">
                    <div class="product-card" style="padding: 1.5rem; flex: 1; text-align: center;">
                        <h3 style="font-size: 2rem; color: var(--primary-color);"><?php echo $products_count; ?></h3>
                        <p class="text-muted">Active Products</p>
                    </div>
                    <div class="product-card" style="padding: 1.5rem; flex: 1; text-align: center;">
                        <h3 style="font-size: 2rem; color: var(--success-color);">0</h3>
                        <p class="text-muted">Inquiries</p>
                    </div>
                </div>

                <!-- Products Section -->
                <div id="products" class="dashboard-section active">
                    <div class="flex-between mb-2">
                        <h3>My Products</h3>
                        <button class="btn btn-primary" onclick="showSection('add-product')">+ Add Product</button>
                    </div>

                    <?php if (count($products) > 0): ?>
                        <table class="table" id="productsTable">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Brand</th>
                                    <th>Price</th>
                                    <th>Condition</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr id="product-<?php echo $product['id']; ?>">
                                        <td>
                                            <img src="../uploads/<?php echo $product['image'] ?: 'placeholder.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;"
                                                 onerror="this.src='https://via.placeholder.com/50x50?text=No+Image'">
                                        </td>
                                        <td><?php echo htmlspecialchars($product['title']); ?></td>
                                        <td><?php echo htmlspecialchars($product['brand']); ?></td>
                                        <td>₹<?php echo number_format($product['price']); ?></td>
                                        <td>
                                            <span class="condition-badge <?php echo getConditionClass($product['condition']); ?>">
                                                <?php echo $product['condition']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                            <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>, this)">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="product-card" style="padding: 2rem; text-align: center;" id="emptyProducts">
                            <h3>No products yet</h3>
                            <p class="text-muted">Add your first product to start selling!</p>
                            <button class="btn btn-primary" onclick="showSection('add-product')">Add Product</button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Add Product Section -->
                <div id="add-product" class="dashboard-section">
                    <h3 class="mb-2">Add New Product</h3>
                    <form id="addProductForm" class="product-card" style="padding: 1.5rem;" onsubmit="submitProductForm(event)">
                        <div class="form-group">
                            <label for="productTitle">Product Title *</label>
                            <input type="text" id="productTitle" name="title" required placeholder="e.g., iPhone 12 Pro 128GB">
                        </div>
                        
                        <div class="flex gap-2">
                            <div class="form-group" style="flex: 1;">
                                <label for="productBrand">Brand *</label>
                                <select id="productBrand" name="brand" required>
                                    <option value="">Select Brand</option>
                                    <option value="Apple">Apple</option>
                                    <option value="Samsung">Samsung</option>
                                    <option value="OnePlus">OnePlus</option>
                                    <option value="Xiaomi">Xiaomi</option>
                                    <option value="Google">Google Pixel</option>
                                    <option value="Realme">Realme</option>
                                    <option value="Vivo">Vivo</option>
                                    <option value="Oppo">Oppo</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="flex: 1;">
                                <label for="productModel">Model *</label>
                                <input type="text" id="productModel" name="model" required placeholder="e.g., iPhone 12 Pro">
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <div class="form-group" style="flex: 1;">
                                <label for="productPrice">Price (₹) *</label>
                                <input type="number" id="productPrice" name="price" required placeholder="e.g., 45000" min="1">
                            </div>
                            
                            <div class="form-group" style="flex: 1;">
                                <label for="productCondition">Condition *</label>
                                <select id="productCondition" name="condition" required>
                                    <option value="">Select Condition</option>
                                    <option value="New">New</option>
                                    <option value="Like New">Like New</option>
                                    <option value="Good">Good</option>
                                    <option value="Fair">Fair</option>
                                    <option value="Poor">Poor</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="productDescription">Description</label>
                            <textarea id="productDescription" name="description" rows="3" placeholder="Describe the phone's condition, storage, accessories included, etc."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="productLocation">Location</label>
                            <input type="text" id="productLocation" name="location" placeholder="e.g., City Center, Downtown">
                        </div>
                        
                        <div class="form-group">
                            <label for="productImage">Product Image</label>
                            <input type="file" id="productImage" name="image" accept="image/*" onchange="previewImage(this)">
                            <div id="imagePreview" style="margin-top: 10px;"></div>
                        </div>
                        
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">Add Product</button>
                            <button type="button" class="btn btn-outline" onclick="showSection('products')">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Shop Info Section -->
                <div id="shop-info" class="dashboard-section">
                    <h3 class="mb-2">Shop Information</h3>
                    <div class="product-card" style="padding: 1.5rem;">
                        <p><strong>Shop Name:</strong> <?php echo htmlspecialchars($shop['shop_name']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($shop['address']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($shop['phone']); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="status-badge <?php echo 'status-' . $shop['status']; ?>">
                                <?php echo ucfirst($shop['status']); ?>
                            </span>
                        </p>
                        <?php if ($shop['status'] === 'pending'): ?>
                            <div class="alert alert-info mt-2">
                                Your shop registration is pending admin approval. You can add products, but they won't be visible until your shop is approved.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Standalone Shop Dashboard JavaScript
        console.log('Shop Dashboard loaded');
        
        // Show/hide sections
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.dashboard-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show selected section
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.classList.add('active');
            }
            
            // Update sidebar menu
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.classList.remove('active');
                if (link.dataset.section === sectionId) {
                    link.classList.add('active');
                }
            });
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Image preview
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 200px; max-height: 200px; border-radius: 4px;">';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Submit product form
        async function submitProductForm(event) {
            event.preventDefault();
            
            const form = document.getElementById('addProductForm');
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            // Show loading
            submitBtn.textContent = 'Adding...';
            submitBtn.disabled = true;
            document.getElementById('loadingOverlay').classList.add('active');
            
            try {
                const response = await fetch('../api/products.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Product added successfully!', 'success');
                    form.reset();
                    document.getElementById('imagePreview').innerHTML = '';
                    
                    // Reload page after 1 second to show new product
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showMessage(data.message || 'Failed to add product', 'error');
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Error adding product: ' + error.message, 'error');
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            } finally {
                document.getElementById('loadingOverlay').classList.remove('active');
            }
        }
        
        // Delete product
        async function deleteProduct(productId, button) {
            if (!confirm('Are you sure you want to delete this product?')) {
                return;
            }
            
            button.textContent = 'Deleting...';
            button.disabled = true;
            document.getElementById('loadingOverlay').classList.add('active');
            
            try {
                const response = await fetch('../api/products.php/' + productId, {
                    method: 'DELETE'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Product deleted successfully!', 'success');
                    
                    // Remove row from table
                    const row = document.getElementById('product-' + productId);
                    if (row) {
                        row.remove();
                    }
                    
                    // Check if table is now empty
                    const tbody = document.querySelector('#productsTable tbody');
                    if (tbody && tbody.children.length === 0) {
                        document.getElementById('products').innerHTML = `
                            <div class="product-card" style="padding: 2rem; text-align: center;">
                                <h3>No products yet</h3>
                                <p class="text-muted">Add your first product to start selling!</p>
                                <button class="btn btn-primary" onclick="showSection('add-product')">Add Product</button>
                            </div>
                        `;
                    }
                } else {
                    showMessage(data.message || 'Failed to delete product', 'error');
                    button.textContent = 'Delete';
                    button.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('Error deleting product: ' + error.message, 'error');
                button.textContent = 'Delete';
                button.disabled = false;
            } finally {
                document.getElementById('loadingOverlay').classList.remove('active');
            }
        }
        
        // Show message
        function showMessage(message, type) {
            const container = document.getElementById('messageContainer');
            const div = document.createElement('div');
            div.className = type === 'success' ? 'success-message' : 'error-message';
            div.textContent = message;
            container.appendChild(div);
            
            // Remove after 5 seconds
            setTimeout(() => {
                div.remove();
            }, 5000);
            
            // Scroll to message
            div.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Show products section by default
            showSection('products');
        });
    </script>
</body>
</html>
