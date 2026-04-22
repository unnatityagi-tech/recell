<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

// Extract product ID from path if present
$productId = null;
if ($path && preg_match('/\/(\d+)$/', $path, $matches)) {
    $productId = (int)$matches[1];
}

switch ($method) {
    case 'GET':
        handleGetProducts($productId);
        break;
    case 'POST':
        handleCreateProduct();
        break;
    case 'PUT':
        handleUpdateProduct($productId);
        break;
    case 'DELETE':
        handleDeleteProduct($productId);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
}

function handleGetProducts($productId = null) {
    global $conn;
    
    if ($productId) {
        // Get single product
        $stmt = $conn->prepare("
            SELECT p.*, s.shop_name, s.address, s.phone 
            FROM products p 
            JOIN shops s ON p.shop_id = s.id 
            WHERE p.id = ? AND p.is_active = 1
        ");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        
        $product = $result->fetch_assoc();
        echo json_encode(['success' => true, 'product' => $product]);
    } else {
        // Get all products with filters
        $brand = $_GET['brand'] ?? '';
        $condition = $_GET['condition'] ?? '';
        $min_price = $_GET['min_price'] ?? 0;
        $max_price = $_GET['max_price'] ?? 999999;
        $location = $_GET['location'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $sql = "
            SELECT p.*, s.shop_name, s.address, s.phone 
            FROM products p 
            JOIN shops s ON p.shop_id = s.id 
            WHERE p.is_active = 1
        ";
        
        $params = [];
        $types = '';
        
        if ($brand) {
            $sql .= " AND p.brand = ?";
            $params[] = $brand;
            $types .= 's';
        }
        
        if ($condition) {
            $sql .= " AND p.condition = ?";
            $params[] = $condition;
            $types .= 's';
        }
        
        $sql .= " AND p.price BETWEEN ? AND ?";
        $params[] = $min_price;
        $params[] = $max_price;
        $types .= 'dd';
        
        if ($location) {
            $sql .= " AND s.address LIKE ?";
            $params[] = "%$location%";
            $types .= 's';
        }
        
        if ($search) {
            $sql .= " AND (p.title LIKE ? OR p.brand LIKE ? OR p.model LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= 'sss';
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        echo json_encode(['success' => true, 'products' => $products]);
    }
}

function handleCreateProduct() {
    global $conn;
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    $user = getCurrentUser();
    if ($user['role'] !== 'shop') {
        echo json_encode(['success' => false, 'message' => 'Only shop owners can add products']);
        exit;
    }
    
    // Get shop ID
    $stmt = $conn->prepare("SELECT id FROM shops WHERE user_id = ? AND status = 'approved'");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $shop_result = $stmt->get_result();
    
    if ($shop_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Shop not found or not approved']);
        $stmt->close();
        exit;
    }
    
    $shop = $shop_result->fetch_assoc();
    $shop_id = $shop['id'];
    $stmt->close();
    
    // Get form data
    $title = sanitize($_POST['title'] ?? '');
    $brand = sanitize($_POST['brand'] ?? '');
    $model = sanitize($_POST['model'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $condition = sanitize($_POST['condition'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    
    // Validation
    if (empty($title) || empty($brand) || empty($model) || $price <= 0 || empty($condition)) {
        echo json_encode(['success' => false, 'message' => 'Title, brand, model, price, and condition are required']);
        exit;
    }
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image = $file_name;
        }
    }
    
    // Insert product
    $stmt = $conn->prepare("
        INSERT INTO products (shop_id, title, brand, model, price, condition, description, image, location) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssdssss", $shop_id, $title, $brand, $model, $price, $condition, $description, $image, $location);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product']);
    }
    
    $stmt->close();
}

function handleUpdateProduct($productId) {
    global $conn;
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    $user = getCurrentUser();
    if ($user['role'] !== 'shop') {
        echo json_encode(['success' => false, 'message' => 'Only shop owners can update products']);
        exit;
    }
    
    // Get form data
    $title = sanitize($_POST['title'] ?? '');
    $brand = sanitize($_POST['brand'] ?? '');
    $model = sanitize($_POST['model'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $condition = sanitize($_POST['condition'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    
    // Validation
    if (empty($title) || empty($brand) || empty($model) || $price <= 0 || empty($condition)) {
        echo json_encode(['success' => false, 'message' => 'Title, brand, model, price, and condition are required']);
        exit;
    }
    
    // Check if product belongs to user
    $stmt = $conn->prepare("
        SELECT p.id FROM products p 
        JOIN shops s ON p.shop_id = s.id 
        WHERE p.id = ? AND s.user_id = ?
    ");
    $stmt->bind_param("ii", $productId, $user['id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
        $stmt->close();
        exit;
    }
    $stmt->close();
    
    // Update product
    $stmt = $conn->prepare("
        UPDATE products 
        SET title = ?, brand = ?, model = ?, price = ?, condition = ?, description = ?, location = ?, is_active = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssdssssi", $title, $brand, $model, $price, $condition, $description, $location, $is_active, $productId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update product']);
    }
    
    $stmt->close();
}

function handleDeleteProduct($productId) {
    global $conn;
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    $user = getCurrentUser();
    if ($user['role'] !== 'shop') {
        echo json_encode(['success' => false, 'message' => 'Only shop owners can delete products']);
        exit;
    }
    
    // Check if product belongs to user
    $stmt = $conn->prepare("
        SELECT p.id FROM products p 
        JOIN shops s ON p.shop_id = s.id 
        WHERE p.id = ? AND s.user_id = ?
    ");
    $stmt->bind_param("ii", $productId, $user['id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
        $stmt->close();
        exit;
    }
    $stmt->close();
    
    // Delete product
    $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $productId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
    }
    
    $stmt->close();
}
?>