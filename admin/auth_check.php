<?php
/**
 * GlowCart Cosmetics - Admin Authentication Guard
 */
require_once __DIR__ . '/../config/db.php';

if (!is_admin_logged_in()) {
    $_SESSION['admin_flash_error'] = 'Please log in to access the admin portal.';
    header('Location: login.php');
    exit;
}

// Fetch current admin user details if needed
$current_admin_id = (int)$_SESSION['admin_id'];
$admin_stmt = $pdo->prepare("SELECT id, username, email FROM admins WHERE id = :id LIMIT 1");
$admin_stmt->execute([':id' => $current_admin_id]);
$current_admin = $admin_stmt->fetch();

if (!$current_admin) {
    // Admin account no longer exists
    unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_email']);
    $_SESSION['admin_flash_error'] = 'Admin session invalid. Please log in again.';
    header('Location: login.php');
    exit;
}

// Keep session in sync with database
$_SESSION['admin_username'] = $current_admin['username'];
$_SESSION['admin_email'] = $current_admin['email'];
