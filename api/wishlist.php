<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$user = getCurrentUser();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetWishlist();
        break;
    case 'POST':
        handleAddToWishlist();
        break;
    case 'DELETE':
        handleRemoveFromWishlist();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
}

function handleGetWishlist() {
    global $conn;
    $user = getCurrentUser();
    
    $stmt = $conn->prepare("
        SELECT w.*, p.title, p.brand, p.model, p.price, p.condition, p.image 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        WHERE w.user_id = ? AND p.is_active = 1
        ORDER BY w.created_at DESC
    ");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $wishlist = [];
    while ($row = $result->fetch_assoc()) {
        $wishlist[] = $row;
    }
    
    echo json_encode(['success' => true, 'wishlist' => $wishlist]);
}

function handleAddToWishlist() {
    global $conn;
    $user = getCurrentUser();
    
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $product_id = (int)($data['product_id'] ?? 0);
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        exit;
    }
    
    // Check if product exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        $stmt->close();
        exit;
    }
    $stmt->close();
    
    // Add to wishlist
    $stmt = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user['id'], $product_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to wishlist']);
    }
    
    $stmt->close();
}

function handleRemoveFromWishlist() {
    global $conn;
    $user = getCurrentUser();
    
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $product_id = (int)($data['product_id'] ?? 0);
    
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user['id'], $product_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove from wishlist']);
    }
    
    $stmt->close();
}
?>