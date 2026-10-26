<?php
session_start();
include_once 'database.php';

// Fungsi cek login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fungsi cek role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

// Fungsi redirect jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Fungsi redirect jika bukan admin
function requireAdmin() {
    requireLogin();
    if (!hasRole('admin')) {
        header('Location: index.php');
        exit();
    }
}

// Fungsi catat log
function addLog($conn, $user_id, $username, $action, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $username, $action, $details, $ip);
    $stmt->execute();
}

// Cek login saat halaman di-load
if (isLoggedIn()) {
    // Refresh session user
    $userId = $_SESSION['user_id'];
    $query = $conn->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $query->bind_param("i", $userId);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows == 0) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
}
?>