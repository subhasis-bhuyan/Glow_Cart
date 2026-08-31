<?php
/**
 * GlowCart Cosmetics - Admin Logout
 */
require_once __DIR__ . '/../config/db.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_email']);

header('Location: login.php');
exit;
