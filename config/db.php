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

// Ensure favorites table exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `favorites` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `user_id` INT(11) NOT NULL,
          `product_id` INT(11) NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `user_product_unique` (`user_id`, `product_id`),
          KEY `fk_favorites_user` (`user_id`),
          KEY `fk_favorites_product` (`product_id`),
          CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_favorites_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {
    // Graceful fallback if table cannot be auto-created
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
 * Get array of favorited product IDs for a given user
 */
function get_user_favorite_ids(int $user_id): array {
    global $pdo;
    if ($user_id <= 0) return [];
    try {
        $stmt = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = :uid");
        $stmt->execute([':uid' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get total favorite count for a given user
 */
function get_favorite_count(int $user_id): int {
    global $pdo;
    if ($user_id <= 0) return 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = :uid");
        $stmt->execute([':uid' => $user_id]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Check if a product is in user's favorites
 */
function is_product_favorite(int $user_id, int $product_id): bool {
    global $pdo;
    if ($user_id <= 0 || $product_id <= 0) return false;
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM favorites WHERE user_id = :uid AND product_id = :pid LIMIT 1");
        $stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
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

/**
 * Highlight search matches in a string safely
 */
function highlight_text(?string $text, ?string $query): string {
    if ($text === null || $text === '') {
        return '';
    }
    $escaped_text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    if ($query === null || trim($query) === '') {
        return $escaped_text;
    }
    $escaped_query = preg_quote(trim($query), '/');
    if (empty($escaped_query)) {
        return $escaped_text;
    }
    return preg_replace('/(' . $escaped_query . ')/i', '<mark class="search-highlight">$1</mark>', $escaped_text);
}

