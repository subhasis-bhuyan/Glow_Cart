<?php
/**
 * GlowCart Cosmetics - Administrator Logout Handler
 */
require_once __DIR__ . '/../config/db.php';

// Unset Admin Session State
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_email']);

$_SESSION['flash_success'] = 'You have been safely logged out of the Administrator Panel.';
header('Location: login.php');
exit;
