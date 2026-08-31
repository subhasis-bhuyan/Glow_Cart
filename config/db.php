<?php
/**
 * GlowCart Cosmetics - Database Connection & Session Configuration
 * Technology: PHP 8+, MySQL PDO
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'glowcart_db';
$db_port = 3306;

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // If database doesn't exist yet, attempt automatic creation
    try {
        $temp_pdo = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8mb4", $db_user, $db_pass);
        $temp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Connect to newly created database
        $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, $options);
        
        // Auto import schema if tables do not exist
        $check = $pdo->query("SHOW TABLES LIKE 'products'")->rowCount();
        if ($check === 0) {
            $sql_file = __DIR__ . '/../database.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                $pdo->exec($sql);
            }
        }
    } catch (PDOException $ex) {
        die("<div style='font-family:sans-serif;padding:30px;background:#ffebee;color:#c62828;border-radius:8px;margin:50px auto;max-width:600px;'>
            <h2>Database Connection Error</h2>
            <p>Could not connect to MySQL server on <code>localhost</code>. Please ensure Apache and MySQL are running in your XAMPP Control Panel.</p>
            <p><strong>Error Details:</strong> " . htmlspecialchars($ex->getMessage()) . "</p>
        </div>");
    }
}

// ----------------------------------------------------
// Core Helper Functions
// ----------------------------------------------------

/**
 * Check if a customer is logged in
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if an admin is logged in
 */
function is_admin_logged_in(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Require customer login; redirect if not authenticated
 */
function require_login(string $redirect_to = 'login.php'): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: {$redirect_to}");
        exit;
    }
}

/**
 * Require admin login; redirect if not authenticated
 */
function require_admin(string $redirect_to = 'login.php'): void {
    if (!is_admin_logged_in()) {
        $_SESSION['admin_flash_error'] = 'Please log in with admin credentials.';
        header("Location: {$redirect_to}");
        exit;
    }
}

/**
 * Get current shopping cart total item count
 */
function get_cart_count(): int {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += (int)($item['quantity'] ?? 0);
    }
    return $total;
}

/**
 * Format price in Indian Rupee (INR) format
 */
function format_price($price): string {
    return '₹' . number_format((float)$price, 2);
}

/**
 * Sanitize string input
 */
function clean_input($data): string {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}
