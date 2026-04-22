<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$product_id = (int)($data['product_id'] ?? 0);
$message = sanitize($data['message'] ?? '');
$contact_email = sanitize($data['contact_email'] ?? '');
$contact_phone = sanitize($data['contact_phone'] ?? '');

// Validation
if ($product_id <= 0 || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Product ID and message are required']);
    exit;
}

// Check if product exists
$stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    $stmt->close();
    exit;
}
$stmt->close();

$user_id = null;
if (isLoggedIn()) {
    $user = getCurrentUser();
    $user_id = $user['id'];
    if (empty($contact_email)) {
        $contact_email = $user['email'];
    }
}

if (empty($contact_email)) {
    echo json_encode(['success' => false, 'message' => 'Contact email is required']);
    exit;
}

// Insert inquiry
$stmt = $conn->prepare("
    INSERT INTO inquiries (user_id, product_id, message, contact_email, contact_phone) 
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("iisss", $user_id, $product_id, $message, $contact_email, $contact_phone);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Inquiry sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send inquiry']);
}

$stmt->close();
?>