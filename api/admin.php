<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$user = getCurrentUser();
if ($user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

// Determine action from URL or POST data
$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif (isset($_POST['action'])) {
    $action = $_POST['action'];
} else {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $action = $data['action'] ?? '';
}

switch ($action) {
    case 'approve_shop':
        updateShopStatus($_POST['shop_id'] ?? $data['shop_id'] ?? 0, 'approved');
        break;
    case 'reject_shop':
        updateShopStatus($_POST['shop_id'] ?? $data['shop_id'] ?? 0, 'rejected');
        break;
    case 'edit_shop':
        editShop();
        break;
    case 'edit_product':
        editProduct();
        break;
    case 'update_product_image':
        updateProductImage();
        break;
    case 'remove_product':
        removeProduct($_POST['product_id'] ?? $data['product_id'] ?? 0);
        break;
    case 'get_product':
        getProduct($_GET['product_id'] ?? 0);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        exit;
}

function updateShopStatus($shop_id, $status) {
    global $conn;
    
    $shop_id = (int)$shop_id;
    if ($shop_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid shop ID']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE shops SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $shop_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Shop $status successfully"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update shop status']);
    }
    
    $stmt->close();
}

function editShop() {
    global $conn;
    
    $shop_id = (int)($_POST['shop_id'] ?? 0);
    $shop_name = sanitize($_POST['shop_name'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $status = sanitize($_POST['status'] ?? 'pending');
    
    if ($shop_id <= 0 || empty($shop_name) || empty($address) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE shops SET shop_name = ?, address = ?, phone = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $shop_name, $address, $phone, $status, $shop_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Shop updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update shop']);
    }
    
    $stmt->close();
}

function editProduct() {
    global $conn;
    
    $product_id = (int)($_POST['product_id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $brand = sanitize($_POST['brand'] ?? '');
    $model = sanitize($_POST['model'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $condition = sanitize($_POST['condition'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    
    if ($product_id <= 0 || empty($title) || empty($brand) || empty($model) || $price <= 0 || empty($condition)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        exit;
    }
    
    // Handle image upload if provided
    $image_sql = "";
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image = $file_name;
            $image_sql = ", image = ?";
        }
    }
    
    // Build SQL query
    if ($image) {
        $stmt = $conn->prepare("
            UPDATE products 
            SET title = ?, brand = ?, model = ?, price = ?, `condition` = ?, description = ?, location = ?, image = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssdssssi", $title, $brand, $model, $price, $condition, $description, $location, $image, $product_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE products 
            SET title = ?, brand = ?, model = ?, price = ?, `condition` = ?, description = ?, location = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssdsssi", $title, $brand, $model, $price, $condition, $description, $location, $product_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update product: ' . $conn->error]);
    }
    
    $stmt->close();
}

function updateProductImage() {
    global $conn;
    
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
        exit;
    }
    
    $upload_dir = '../uploads/';
    $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
    $target_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
        // Get old image to delete
        $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $old_image = $result->fetch_assoc()['image'] ?? '';
        $stmt->close();
        
        // Update with new image
        $stmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
        $stmt->bind_param("si", $file_name, $product_id);
        
        if ($stmt->execute()) {
            // Delete old image if exists
            if ($old_image && file_exists($upload_dir . $old_image)) {
                unlink($upload_dir . $old_image);
            }
            echo json_encode(['success' => true, 'message' => 'Image updated successfully', 'image' => $file_name]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update image in database']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    }
}

function removeProduct($product_id) {
    global $conn;
    
    $product_id = (int)$product_id;
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove product']);
    }
    
    $stmt->close();
}

function getProduct($product_id) {
    global $conn;
    
    $product_id = (int)$product_id;
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    $stmt = $conn->prepare("
        SELECT p.*, s.shop_name 
        FROM products p 
        JOIN shops s ON p.shop_id = s.id 
        WHERE p.id = ? AND p.is_active = 1
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    $product = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode(['success' => true, 'product' => $product]);
}
?>
