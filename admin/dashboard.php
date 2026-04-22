<?php
session_start();
require_once '../includes/auth.php';

requireRole(['admin']);
$user = getCurrentUser();
$message = getMessage();

// Get database connection
require_once '../config/database.php';

// Get statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$stats['customers'] = $result->fetch_assoc()['count'];
$result = $conn->query("SELECT COUNT(*) as count FROM shops");
$stats['shops'] = $result->fetch_assoc()['count'];
$result = $conn->query("SELECT COUNT(*) as count FROM shops WHERE status = 'pending'");
$stats['pending_shops'] = $result->fetch_assoc()['count'];
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
$stats['products'] = $result->fetch_assoc()['count'];

// Get all products (not just recent)
$result = $conn->query("
    SELECT p.*, s.shop_name, u.name as seller_name 
    FROM products p 
    JOIN shops s ON p.shop_id = s.id 
    JOIN users u ON s.user_id = u.id 
    WHERE p.is_active = 1 
    ORDER BY p.created_at DESC 
");
$all_products = $result->fetch_all(MYSQLI_ASSOC);

// Get pending shops
$result = $conn->query("
    SELECT s.*, u.name, u.email 
    FROM shops s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.status = 'pending' 
    ORDER BY s.created_at DESC
");
$pending_shops = $result->fetch_all(MYSQLI_ASSOC);

// Get all shops
$result = $conn->query("
    SELECT s.*, u.name, u.email 
    FROM shops s 
    JOIN users u ON s.user_id = u.id 
    ORDER BY s.created_at DESC
");
$all_shops = $result->fetch_all(MYSQLI_ASSOC);

// Get all users
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 20");
$users = $result->fetch_all(MYSQLI_ASSOC);

function getConditionClass($condition) {
    $conditions = ['New' => 'condition-new', 'Like New' => 'condition-like-new', 'Good' => 'condition-good', 'Fair' => 'condition-fair', 'Poor' => 'condition-poor'];
    return $conditions[$condition] ?? 'condition-good';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LocalPhone Marketplace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-section { display: none; }
        .admin-section.active { display: block; }
        .sidebar-menu a { display: block; padding: 12px 16px; color: #333; text-decoration: none; border-radius: 4px; margin-bottom: 4px; cursor: pointer; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #2196F3; color: white; }
        .success-message { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 15px; }
        .error-message { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px; }
        .loading-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .loading-overlay.active { display: flex; }
        .loading-spinner { background: white; padding: 20px 40px; border-radius: 8px; font-size: 18px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2rem; border-radius: 8px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
        .modal-close:hover { color: #000; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .image-preview { max-width: 200px; max-height: 200px; margin-top: 10px; border-radius: 4px; }
        .btn-sm { padding: 4px 12px; font-size: 12px; }
        .flex-gap { display: flex; gap: 10px; flex-wrap: wrap; }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner">Processing...</div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Product</h3>
                <button class="modal-close" onclick="closeModal('editProductModal')">&times;</button>
            </div>
            <form id="editProductForm" onsubmit="submitEditProduct(event)">
                <input type="hidden" id="editProductId" name="product_id">
                <input type="hidden" name="action" value="edit_product">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" id="editProductTitle" name="title" required>
                </div>
                <div class="flex-gap">
                    <div class="form-group" style="flex: 1;">
                        <label>Brand *</label>
                        <select id="editProductBrand" name="brand" required>
                            <option value="Apple">Apple</option>
                            <option value="Samsung">Samsung</option>
                            <option value="OnePlus">OnePlus</option>
                            <option value="Xiaomi">Xiaomi</option>
                            <option value="Google">Google</option>
                            <option value="Realme">Realme</option>
                            <option value="Vivo">Vivo</option>
                            <option value="Oppo">Oppo</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Model *</label>
                        <input type="text" id="editProductModel" name="model" required>
                    </div>
                </div>
                <div class="flex-gap">
                    <div class="form-group" style="flex: 1;">
                        <label>Price (₹) *</label>
                        <input type="number" id="editProductPrice" name="price" required min="1">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Condition *</label>
                        <select id="editProductCondition" name="condition" required>
                            <option value="New">New</option>
                            <option value="Like New">Like New</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Poor">Poor</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="editProductDescription" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" id="editProductLocation" name="location">
                </div>
                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" id="editProductImage" name="image" accept="image/*" onchange="previewEditImage(this)">
                    <div id="editImagePreview"></div>
                </div>
                <div class="flex-gap">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('editProductModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Shop Modal -->
    <div id="editShopModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Shop</h3>
                <button class="modal-close" onclick="closeModal('editShopModal')">&times;</button>
            </div>
            <form id="editShopForm" onsubmit="submitEditShop(event)">
                <input type="hidden" id="editShopId" name="shop_id">
                <input type="hidden" name="action" value="edit_shop">
                <div class="form-group">
                    <label>Shop Name *</label>
                    <input type="text" id="editShopName" name="shop_name" required>
                </div>
                <div class="form-group">
                    <label>Address *</label>
                    <textarea id="editShopAddress" name="address" required></textarea>
                </div>
                <div class="form-group">
                    <label>Phone *</label>
                    <input type="text" id="editShopPhone" name="phone" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="editShopStatus" name="status">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="flex-gap">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-outline" onclick="closeModal('editShopModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container container">
            <a href="../index.php" class="navbar-logo">Local<span>Phone</span></a>
            <ul class="navbar-menu">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../pages/products.php">Browse Phones</a></li>
                <li><a href="dashboard.php" class="active">Admin</a></li>
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

        <div id="messageContainer"></div>

        <div class="dashboard-layout">
            <!-- Sidebar -->
            <aside class="dashboard-sidebar">
                <h3>Admin Panel</h3>
                <div class="sidebar-menu">
                    <a href="#" class="active" data-section="overview" onclick="showSection('overview'); return false;">Overview</a>
                    <a href="#" data-section="shops" onclick="showSection('shops'); return false;">Manage Shops</a>
                    <a href="#" data-section="products" onclick="showSection('products'); return false;">Monitor Listings</a>
                    <a href="#" data-section="users" onclick="showSection('users'); return false;">Manage Users</a>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="dashboard-content">
                <h1 class="mb-3">Admin Dashboard</h1>

                <!-- Overview Section -->
                <div id="overview" class="admin-section active">
                    <div class="flex gap-2 mb-3" style="flex-wrap: wrap;">
                        <div class="product-card" style="padding: 1.5rem; flex: 1; min-width: 150px; text-align: center;">
                            <h3 style="font-size: 2rem; color: var(--primary-color);"><?php echo $stats['customers']; ?></h3>
                            <p class="text-muted">Customers</p>
                        </div>
                        <div class="product-card" style="padding: 1.5rem; flex: 1; min-width: 150px; text-align: center;">
                            <h3 style="font-size: 2rem; color: var(--success-color);"><?php echo $stats['shops']; ?></h3>
                            <p class="text-muted">Shops</p>
                        </div>
                        <div class="product-card" style="padding: 1.5rem; flex: 1; min-width: 150px; text-align: center;">
                            <h3 style="font-size: 2rem; color: var(--warning-color);"><?php echo $stats['pending_shops']; ?></h3>
                            <p class="text-muted">Pending Shops</p>
                        </div>
                        <div class="product-card" style="padding: 1.5rem; flex: 1; min-width: 150px; text-align: center;">
                            <h3 style="font-size: 2rem; color: var(--secondary-color);"><?php echo $stats['products']; ?></h3>
                            <p class="text-muted">Active Products</p>
                        </div>
                    </div>

                    <?php if (count($pending_shops) > 0): ?>
                        <div class="alert alert-warning mb-3">
                            <strong>⚠️ <?php echo count($pending_shops); ?> shop(s) pending approval</strong>
                            <button class="btn btn-sm btn-primary" onclick="showSection('shops')" style="margin-left: 10px;">Review Now</button>
                        </div>
                    <?php endif; ?>

                    <div class="product-card" style="padding: 1.5rem;">
                        <h3 class="mb-2">Quick Actions</h3>
                        <div class="flex gap-2" style="flex-wrap: wrap;">
                            <button class="btn btn-primary" onclick="showSection('shops')">Manage Shops</button>
                            <button class="btn btn-secondary" onclick="showSection('products')">Monitor Listings</button>
                            <button class="btn btn-outline" onclick="window.open('../index.php', '_blank')">View Website</button>
                        </div>
                    </div>
                </div>

                <!-- Shops Section -->
                <div id="shops" class="admin-section">
                    <h2 class="mb-2">Shop Management</h2>
                    
                    <?php if (count($pending_shops) > 0): ?>
                        <div class="alert alert-warning mb-2">
                            <strong><?php echo count($pending_shops); ?> shop(s) pending approval</strong>
                        </div>
                    <?php endif; ?>

                    <div class="product-card" style="overflow-x: auto;">
                        <table class="table" id="shopsTable">
                            <thead>
                                <tr>
                                    <th>Shop Name</th>
                                    <th>Owner</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_shops as $shop): ?>
                                    <tr id="shop-<?php echo $shop['id']; ?>">
                                        <td><?php echo htmlspecialchars($shop['shop_name']); ?></td>
                                        <td><?php echo htmlspecialchars($shop['name']); ?></td>
                                        <td><?php echo htmlspecialchars($shop['email']); ?></td>
                                        <td><?php echo htmlspecialchars($shop['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars(substr($shop['address'] ?? '', 0, 30)) . (strlen($shop['address'] ?? '') > 30 ? '...' : ''); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $shop['status']; ?>" id="status-<?php echo $shop['id']; ?>">
                                                <?php echo ucfirst($shop['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline" onclick="openEditShop(<?php echo $shop['id']; ?>, '<?php echo htmlspecialchars(addslashes($shop['shop_name'])); ?>', '<?php echo htmlspecialchars(addslashes($shop['address'])); ?>', '<?php echo htmlspecialchars($shop['phone']); ?>', '<?php echo $shop['status']; ?>')">Edit</button>
                                            <?php if ($shop['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-success" onclick="updateShopStatus(<?php echo $shop['id']; ?>, 'approve', this)">Approve</button>
                                                <button class="btn btn-sm btn-danger" onclick="updateShopStatus(<?php echo $shop['id']; ?>, 'reject', this)">Reject</button>
                                            <?php elseif ($shop['status'] === 'approved'): ?>
                                                <button class="btn btn-sm btn-danger" onclick="updateShopStatus(<?php echo $shop['id']; ?>, 'reject', this)">Reject</button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-success" onclick="updateShopStatus(<?php echo $shop['id']; ?>, 'approve', this)">Approve</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Products Section -->
                <div id="products" class="admin-section">
                    <h2 class="mb-2">Monitor Listings (<?php echo count($all_products); ?> products)</h2>
                    
                    <div class="product-card" style="overflow-x: auto;">
                        <table class="table" id="productsTable">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Brand</th>
                                    <th>Price</th>
                                    <th>Condition</th>
                                    <th>Seller</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_products as $product): ?>
                                    <tr id="product-<?php echo $product['id']; ?>">
                                        <td>
                                            <img src="../uploads/<?php echo $product['image'] ?: 'placeholder.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer;"
                                                 onclick="changeProductImage(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['title'])); ?>')"
                                                 title="Click to change image"
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
                                        <td><?php echo htmlspecialchars($product['shop_name']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline" onclick="openEditProduct(<?php echo $product['id']; ?>)">Edit</button>
                                            <a href="../pages/product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline" target="_blank">View</a>
                                            <button class="btn btn-sm btn-danger" onclick="removeProduct(<?php echo $product['id']; ?>, this)">Remove</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Users Section -->
                <div id="users" class="admin-section">
                    <h2 class="mb-2">User Management</h2>
                    
                    <div class="product-card" style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Registered</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $u['role'] === 'admin' ? 'status-approved' : ($u['role'] === 'shop' ? 'status-pending' : 'condition-good'); ?>">
                                                <?php echo ucfirst($u['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                        <td><span class="status-badge status-approved">Active</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Hidden file input for image change -->
    <input type="file" id="imageUploadInput" accept="image/*" style="display: none;">

    <script>
        const API_BASE = '../api/';
        let currentProductIdForImage = null;

        // Show/hide sections
        function showSection(sectionId) {
            document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
            document.getElementById(sectionId)?.classList.add('active');
            document.querySelectorAll('.sidebar-menu a').forEach(l => l.classList.remove('active'));
            document.querySelector(`.sidebar-menu a[data-section="${sectionId}"]`)?.classList.add('active');
            window.location.hash = sectionId;
        }

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Open edit product modal
        async function openEditProduct(productId) {
            document.getElementById('loadingOverlay').classList.add('active');
            
            try {
                const response = await fetch(API_BASE + 'admin.php?action=get_product&product_id=' + productId);
                const data = await response.json();
                
                if (data.success) {
                    const p = data.product;
                    document.getElementById('editProductId').value = p.id;
                    document.getElementById('editProductTitle').value = p.title;
                    document.getElementById('editProductBrand').value = p.brand;
                    document.getElementById('editProductModel').value = p.model;
                    document.getElementById('editProductPrice').value = p.price;
                    document.getElementById('editProductCondition').value = p.condition;
                    document.getElementById('editProductDescription').value = p.description || '';
                    document.getElementById('editProductLocation').value = p.location || '';
                    
                    // Show current image preview
                    const preview = document.getElementById('editImagePreview');
                    if (p.image) {
                        preview.innerHTML = `<img src="../uploads/${p.image}" class="image-preview">`;
                    } else {
                        preview.innerHTML = '';
                    }
                    
                    openModal('editProductModal');
                } else {
                    showMessage('Failed to load product data', 'error');
                }
            } catch (error) {
                showMessage('Error loading product: ' + error.message, 'error');
            } finally {
                document.getElementById('loadingOverlay').classList.remove('active');
            }
        }

        // Preview image before upload
        function previewEditImage(input) {
            const preview = document.getElementById('editImagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="image-preview">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Submit edit product
        async function submitEditProduct(e) {
            e.preventDefault();
            const form = document.getElementById('editProductForm');
            const formData = new FormData(form);
            const productId = document.getElementById('editProductId').value;
            
            document.getElementById('loadingOverlay').classList.add('active');
            
            try {
                const response = await fetch(API_BASE + 'admin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Product updated successfully!', 'success');
                    closeModal('editProductModal');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showMessage(data.message || 'Failed to update product', 'error');
                }
            } catch (error) {
                showMessage('Error: ' + error.message, 'error');
            } finally {
                document.getElementById('loadingOverlay').classList.remove('active');
            }
        }

        // Open edit shop modal
        function openEditShop(id, name, address, phone, status) {
            document.getElementById('editShopId').value = id;
            document.getElementById('editShopName').value = name;
            document.getElementById('editShopAddress').value = address;
            document.getElementById('editShopPhone').value = phone;
            document.getElementById('editShopStatus').value = status;
            openModal('editShopModal');
        }

        // Submit edit shop
        async function submitEditShop(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('editShopForm'));
            
            document.getElementById('loadingOverlay').classList.add('active');
            
            try {
                const response = await fetch(API_BASE + 'admin.php?action=edit_shop', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Shop updated successfully!', 'success');
                    closeModal('editShopModal');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showMessage(data.message || 'Failed to update shop', 'error');
                }
            } catch (error) {
                showMessage('Error: ' + error.message, 'error');
            } finally {
                document.getElementById('loadingOverlay').classList.remove('active');
            }
        }

        // Change product image directly
        function changeProductImage(productId, title) {
            currentProductIdForImage = productId;
            const input = document.getElementById('imageUploadInput');
            input.onchange = async function() {
                if (!this.files || !this.files[0]) return;
                
                const formData = new FormData();
                formData.append('action', 'update_product_image');
                formData.append('product_id', productId);
                formData.append('image', this.files[0]);
                
                document.getElementById('loadingOverlay').classList.add('active');
                
                try {
                    const response = await fetch(API_BASE + 'admin.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showMessage('Image updated successfully!', 'success');
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        showMessage(data.message || 'Failed to update image', 'error');
                    }
                } catch (error) {
                    showMessage('Error: ' + error.message, 'error');
                } finally {
                    document.getElementById('loadingOverlay').classList.remove('active');
                    input.value = '';
                }
            };
            input.click();
        }

        // Update shop status
        async function updateShopStatus(shopId, action, button) {
            const actionText = action === 'approve' ? 'approve' : 'reject';
            
            if (!confirm(`Are you sure you want to ${actionText} this shop?`)) return;
            
            button.textContent = 'Processing...';
            button.disabled = true;
            document.getElementById('loadingOverlay').classList.add('active');
            
            try {
                const response = await fetch(API_BASE + 'admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: action === 'approve' ? 'approve_shop' : 'reject_shop',
                        shop_id: shopId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage(`Shop ${actionText}d successfully!`, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showMessage(data.message || `Failed to ${actionText} shop`, 'error');
                    button.textContent = action === 'approve' ? 'Approve' : 'Reject';
                    button.disabled = false;
                }
            } catch (error) {
                showMessage(`Error: ${error.message}`, 'error');
                button.textContent = action === 'approve' ? 'Approve' : 'Reject';
                button.disabled = false;
            } finally {
                document.getElementById('loadingOverlay').classList.remove('active');
            }
        }

        // Remove product
        async function removeProduct(productId, button) {
            if (!confirm('Are you sure you want to remove this listing?')) return;
            
            button.textContent = 'Removing...';
            button.disabled = true;
            document.getElementById('loadingOverlay').classList.add('active');
            
            try {
                const response = await fetch(API_BASE + 'admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'remove_product', product_id: productId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Product removed successfully!', 'success');
                    document.getElementById('product-' + productId)?.remove();
                } else {
                    showMessage(data.message || 'Failed to remove product', 'error');
                    button.textContent = 'Remove';
                    button.disabled = false;
                }
            } catch (error) {
                showMessage(`Error: ${error.message}`, 'error');
                button.textContent = 'Remove';
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
            setTimeout(() => div.remove(), 5000);
            div.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.substring(1);
            if (hash && document.getElementById(hash)) {
                showSection(hash);
            }
        });

        // Close modals on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
