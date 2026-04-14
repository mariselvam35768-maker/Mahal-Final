<?php
session_start();
require_once 'db.php';

/**
 * Register a new user
 */
function registerUser($pdo, $name, $email, $phone, $password, $role = 'user') {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$name, $email, $phone, $hashedPassword, $role]);
}

/**
 * Login a user
 */
/**
 * Login a user
 */
function loginUser($pdo, $login_id, $password) {
    // 1. Try to find user in 'users' table (supports email and phone)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$login_id, $login_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = strtolower($user['role'] ?? 'user');
        return true;
    }

    // 2. Fallback to 'admin' table if not found in 'users' (only email)
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$login_id]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_name'] = $admin['name'];
        $_SESSION['user_role'] = 'admin'; // Always treat records from the admin table as admin
        return true;
    }

    return false;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
}
?>
