<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/auth.php';

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = sanitize($data['email'] ?? '');
$password = $data['password'] ?? '';

// Validation
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

// Find user
$stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    $stmt->close();
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

// For shop users, check if shop is approved
if ($user['role'] === 'shop') {
    $stmt = $conn->prepare("SELECT status FROM shops WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $shop_result = $stmt->get_result();
    
    if ($shop_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Shop not found']);
        $stmt->close();
        exit;
    }
    
    $shop = $shop_result->fetch_assoc();
    $stmt->close();
    
    if ($shop['status'] === 'pending') {
        echo json_encode(['success' => false, 'message' => 'Your shop registration is pending admin approval']);
        exit;
    }
    
    if ($shop['status'] === 'rejected') {
        echo json_encode(['success' => false, 'message' => 'Your shop registration was rejected']);
        exit;
    }
}

// Set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];

echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ],
    'redirect' => $user['role'] === 'admin' ? '../admin/dashboard.php' : 
                  ($user['role'] === 'shop' ? '../pages/shop-dashboard.php' : '../index.php')
]);
?>