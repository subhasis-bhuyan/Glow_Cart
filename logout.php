<?php
/**
 * GlowCart Cosmetics - Customer Logout Handler
 */
require_once __DIR__ . '/config/db.php';

// Clear Customer Session Variables
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['user_phone']);

$_SESSION['flash_success'] = 'You have been logged out successfully.';
header('Location: login.php');
exit;
