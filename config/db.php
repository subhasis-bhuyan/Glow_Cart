<?php
/**
 * GlowCart Cosmetics - Database Connection & Session Configuration
 * Technology: PHP 8+, MySQL PDO
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration - supports environment variables for cloud deployment (Render, Railway, etc.),
// automatic SQLite fallback for zero-configuration deployments, and local XAMPP MySQL.
$db_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

if ($db_url) {
    $url_parts = parse_url($db_url);
    $db_host = $url_parts['host'] ?? 'localhost';
    $db_port = (int)($url_parts['port'] ?? 3306);
    $db_user = $url_parts['user'] ?? 'root';
    $db_pass = $url_parts['pass'] ?? '';
    $db_name = ltrim($url_parts['path'] ?? 'glowcart_db', '/');
} else {
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_port = (int)(getenv('DB_PORT') ?: 3306);
    $db_user = getenv('DB_USER') ?: 'root';
    $db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
    $db_name = getenv('DB_NAME') ?: 'glowcart_db';
}

$has_remote_host = !empty(getenv('DB_HOST')) && !in_array(getenv('DB_HOST'), ['localhost', '127.0.0.1']);
$has_db_url      = !empty($db_url);
$is_cloud_env    = !empty(getenv('RENDER')) || !empty(getenv('PORT'));

$pdo = null;

// 1. Attempt MySQL connection if explicitly configured or on local development
if (extension_loaded('pdo_mysql') && ($has_remote_host || $has_db_url || !$is_cloud_env)) {
    try {
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        if (getenv('DB_SSL') === 'true' || getenv('MYSQL_ATTR_SSL_CA') || ($db_host !== 'localhost' && $db_host !== '127.0.0.1')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    } catch (PDOException $e) {
        // If database doesn't exist yet, attempt automatic creation
        try {
            $temp_pdo = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8mb4", $db_user, $db_pass, $options ?? []);
            $temp_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, $options ?? []);
            
            $check = $pdo->query("SHOW TABLES LIKE 'products'")->rowCount();
            if ($check === 0) {
                $sql_file = __DIR__ . '/../database.sql';
                if (file_exists($sql_file)) {
                    $pdo->exec(file_get_contents($sql_file));
                }
            }
        } catch (PDOException $ex) {
            // If explicit remote credentials failed, stop and show error
            if ($has_remote_host || $has_db_url) {
                die(render_db_error_html($ex->getMessage(), $db_host));
            }
            // If local MySQL is down or running in container, fallback to SQLite
            $pdo = null;
        }
    }
}

// 2. Seamless SQLite fallback (runs automatically on Render if no remote MySQL is configured)
if (!$pdo) {
    if (extension_loaded('pdo_sqlite')) {
        try {
            $pdo = init_sqlite_database();
        } catch (PDOException $sqle) {
            die(render_db_error_html($sqle->getMessage(), 'SQLite'));
        }
    } else {
        die(render_db_error_html("No database available. MySQL on {$db_host} failed and SQLite is not loaded.", $db_host));
    }
}

// 3. Ensure favorites table exists (for MySQL connections)
if ($pdo && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
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
    } catch (PDOException $e) {}
}

/**
 * Initialize embedded SQLite database with schema and full sample catalog
 */
function init_sqlite_database(): PDO {
    $db_path = __DIR__ . '/../glowcart.db';
    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Check if products table exists
    $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='products'")->fetch();
    if (!$check) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              name TEXT NOT NULL,
              email TEXT NOT NULL UNIQUE,
              phone TEXT NOT NULL,
              password TEXT NOT NULL,
              address TEXT DEFAULT NULL,
              city TEXT DEFAULT NULL,
              state TEXT DEFAULT NULL,
              pincode TEXT DEFAULT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS admins (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              username TEXT NOT NULL,
              email TEXT NOT NULL UNIQUE,
              password TEXT NOT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS products (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              name TEXT NOT NULL,
              category TEXT NOT NULL,
              description TEXT NOT NULL,
              price REAL NOT NULL,
              discount_price REAL DEFAULT NULL,
              stock INTEGER NOT NULL DEFAULT 0,
              image TEXT NOT NULL,
              rating REAL NOT NULL DEFAULT 4.5,
              is_featured INTEGER NOT NULL DEFAULT 0,
              is_bestseller INTEGER NOT NULL DEFAULT 0,
              status TEXT NOT NULL DEFAULT 'Active',
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS orders (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              user_id INTEGER NOT NULL,
              customer_name TEXT NOT NULL,
              email TEXT NOT NULL,
              phone TEXT NOT NULL,
              address TEXT NOT NULL,
              city TEXT NOT NULL,
              state TEXT NOT NULL,
              pincode TEXT NOT NULL,
              total_amount REAL NOT NULL,
              payment_method TEXT NOT NULL,
              payment_status TEXT NOT NULL DEFAULT 'Pending',
              status TEXT NOT NULL DEFAULT 'Pending',
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS order_items (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              order_id INTEGER NOT NULL,
              product_id INTEGER DEFAULT NULL,
              product_name TEXT NOT NULL,
              price REAL NOT NULL,
              quantity INTEGER NOT NULL,
              subtotal REAL NOT NULL,
              FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS favorites (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              user_id INTEGER NOT NULL,
              product_id INTEGER NOT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              UNIQUE(user_id, product_id),
              FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
              FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
            );
        ");

        // Seed default Admin and Customer
        $admin_hash = '$2y$10$w8T0P1zD8yT90a0K1C6DqeZk8gO9vH0k1Pq6y0mZ2eF.6B3t1QJeq';
        $user_hash  = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        
        $admin_stmt = $pdo->prepare("INSERT INTO admins (id, username, email, password, created_at) VALUES (1, 'Admin GlowCart', 'admin@glowcart.com', ?, CURRENT_TIMESTAMP)");
        $admin_stmt->execute([$admin_hash]);

        $user_stmt = $pdo->prepare("INSERT INTO users (id, name, email, phone, password, address, city, state, pincode, created_at) VALUES (1, 'Subhasis Nayak', 'subhasis@example.com', '9876543210', ?, '124 Lotus Garden, Patia', 'Bhubaneswar', 'Odisha', '751024', CURRENT_TIMESTAMP)");
        $user_stmt->execute([$user_hash]);

        // Seed 12 Catalog Products
        $pdo->exec("
            INSERT INTO products (id, name, category, description, price, discount_price, stock, image, rating, is_featured, is_bestseller, status, created_at) VALUES
            (1, 'Velvet Matte Rose Lipstick', 'Lipstick', 'Long-lasting, ultra-creamy matte lipstick enriched with vitamin E and jojoba oil for soft, hydrated, bold lips all day.', 899.00, 699.00, 35, 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=600&q=80', 4.8, 1, 1, 'Active', CURRENT_TIMESTAMP),
            (2, 'Luminous Radiance Liquid Foundation', 'Foundation', 'Lightweight medium-to-full buildable coverage liquid foundation with SPF 20 for an all-day natural glowing complexion.', 1299.00, 999.00, 20, 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80', 4.7, 1, 1, 'Active', CURRENT_TIMESTAMP),
            (3, 'Soft Peach Silk Powder Blush', 'Blush', 'Silky, blendable powder blush that delivers a natural flush of radiant color with a subtle satin shimmer.', 749.00, 549.00, 18, 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=600&q=80', 4.6, 1, 0, 'Active', CURRENT_TIMESTAMP),
            (4, 'Golden Sunset 12-Shade Eyeshadow Palette', 'Eyeshadow', 'A versatile palette featuring 12 buttery matte, metallic, and duo-chrome warm sunset shades for day-to-night eye looks.', 1499.00, 1199.00, 15, 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=600&q=80', 4.9, 1, 1, 'Active', CURRENT_TIMESTAMP),
            (5, 'Extreme Length Waterproof Mascara', 'Mascara', 'Smudge-proof, volumizing waterproof mascara with an hourglass wand that lifts and separates every lash.', 699.00, 499.00, 25, 'https://images.unsplash.com/photo-1560365163-3e8d64e762ef?auto=format&fit=crop&w=600&q=80', 4.5, 0, 1, 'Active', CURRENT_TIMESTAMP),
            (6, 'Hydra-Glow Vitamin C Face Serum', 'Skincare', 'Potent brightening serum formulated with 10% Vitamin C, Hyaluronic Acid, and Niacinamide to restore skin glow.', 1199.00, 899.00, 30, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=600&q=80', 4.9, 1, 1, 'Active', CURRENT_TIMESTAMP),
            (7, 'Ultimate Bridal Glam Makeup Kit', 'Makeup Kits', 'Complete luxury vanity kit including foundation, primer, 2 lipsticks, compact, blush, eyeliner, and blending sponge.', 3499.00, 2699.00, 10, 'https://images.unsplash.com/photo-1516975080664-ed2fc6a32937?auto=format&fit=crop&w=600&q=80', 4.8, 1, 1, 'Active', CURRENT_TIMESTAMP),
            (8, 'Professional 10-Piece Rose Gold Brush Set', 'Accessories', 'Ultra-soft vegan synthetic bristles with premium rose gold ferrules and marble handles for seamless blending.', 1299.00, 899.00, 22, 'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=600&q=80', 4.7, 0, 0, 'Active', CURRENT_TIMESTAMP),
            (9, 'Ruby Shine Hydrating Lip Gloss', 'Lipstick', 'High-shine, non-sticky lip gloss enriched with shea butter and coconut oil for plump, juicy lips.', 599.00, 399.00, 40, 'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?auto=format&fit=crop&w=600&q=80', 4.4, 0, 1, 'Active', CURRENT_TIMESTAMP),
            (10, 'Matte Finish Oil-Control Compact Powder', 'Foundation', 'Micro-fine pressed powder that blurs pores, absorbs excess oil, and sets makeup for up to 12 hours.', 649.00, 499.00, 12, 'https://images.unsplash.com/photo-1590156221185-c42152e9928d?auto=format&fit=crop&w=600&q=80', 4.6, 0, 0, 'Active', CURRENT_TIMESTAMP),
            (11, 'Rosewater Soothing Face Mist & Toner', 'Skincare', 'Pure steam-distilled Bulgarian rosewater mist that instantly hydrates, calms, and balances the skin pH.', 499.00, 349.00, 0, 'https://images.unsplash.com/photo-1608248597359-bbcf39ff1a60?auto=format&fit=crop&w=600&q=80', 4.5, 0, 0, 'Active', CURRENT_TIMESTAMP),
            (12, 'Precision Micro Eyeliner Pen - Midnight Black', 'Accessories', 'Waterproof 0.1mm micro-tip liquid eyeliner pen for sharp, smudge-resistant wings and clean lines.', 549.00, 399.00, 50, 'https://images.unsplash.com/photo-1583241800698-e8ab01830a07?auto=format&fit=crop&w=600&q=80', 4.7, 0, 1, 'Active', CURRENT_TIMESTAMP)
        ");
    }

    return $pdo;
}

/**
 * Render user-friendly database connection error page
 */
function render_db_error_html(string $message, string $host): string {
    $error_msg = htmlspecialchars($message);
    return "<div style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;padding:30px;background:#ffebee;color:#c62828;border-radius:10px;margin:50px auto;max-width:680px;box-shadow:0 4px 20px rgba(0,0,0,0.1);line-height:1.6;'>
        <h2 style='margin-top:0;display:flex;align-items:center;gap:10px;'>⚠️ Database Connection Error</h2>
        <p>Could not connect to database on <code>{$host}</code>.</p>
        <div style='background:rgba(0,0,0,0.05);padding:12px;border-radius:6px;font-size:13px;word-break:break-all;'>
            <strong>Error Details:</strong> {$error_msg}
        </div>
    </div>";
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

