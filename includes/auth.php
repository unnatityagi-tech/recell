<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication helper functions

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: pages/login.php');
        exit;
    }
}

function requireRole($roles) {
    if (!isLoggedIn()) {
        header('Location: pages/login.php');
        exit;
    }
    
    if (!in_array($_SESSION['user_role'], $roles)) {
        header('Location: ../pages/login.php');
        exit;
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        $role = $_SESSION['user_role'];
        if ($role === 'admin') {
            header('Location: admin/dashboard.php');
        } elseif ($role === 'shop') {
            header('Location: pages/shop-dashboard.php');
        } else {
            header('Location: index.php');
        }
        exit;
    }
}

function logout() {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

function setMessage($type, $message) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message'], $_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>