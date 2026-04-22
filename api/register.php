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

$name = sanitize($data['name'] ?? '');
$email = sanitize($data['email'] ?? '');
$password = $data['password'] ?? '';
$role = sanitize($data['role'] ?? 'customer');
$shop_name = sanitize($data['shop_name'] ?? '');
$shop_address = sanitize($data['shop_address'] ?? '');
$shop_phone = sanitize($data['shop_phone'] ?? '');

// Validation
if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Name, email, and password are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    $stmt->close();
    exit;
}
$stmt->close();

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Start transaction
$conn->begin_transaction();

try {
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
    $stmt->execute();
    $user_id = $conn->insert_id;
    $stmt->close();

    // If shop role, create shop entry
    if ($role === 'shop') {
        $status = 'pending'; // Requires admin approval
        $stmt = $conn->prepare("INSERT INTO shops (user_id, shop_name, address, phone, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $shop_name, $shop_address, $shop_phone, $status);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    
    // Auto-login after registration
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;

    echo json_encode([
        'success' => true, 
        'message' => 'Registration successful',
        'user' => [
            'id' => $user_id,
            'name' => $name,
            'email' => $email,
            'role' => $role
        ]
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}
?>