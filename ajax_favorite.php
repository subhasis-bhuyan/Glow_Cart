<?php
/**
 * GlowCart Cosmetics - AJAX Favorites & Wishlist API
 */
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Check login requirement for favorite modifications
if (in_array($action, ['toggle', 'remove', 'add'])) {
    if (!is_logged_in()) {
        echo json_encode([
            'success'       => false,
            'require_login' => true,
            'message'       => 'Please log in to save items to your favorites.'
        ]);
        exit;
    }
}

$user_id = (int)($_SESSION['user_id'] ?? 0);

// ----------------------------------------------------
// 1. Toggle Product in User's Favorites
// ----------------------------------------------------
if ($action === 'toggle') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : (int)($_GET['product_id'] ?? 0);

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product selected.']);
        exit;
    }

    try {
        // Verify product exists
        $p_stmt = $pdo->prepare("SELECT id, name, status FROM products WHERE id = :id");
        $p_stmt->execute([':id' => $product_id]);
        $product = $p_stmt->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }

        // Check if already favorited
        $chk_stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :uid AND product_id = :pid");
        $chk_stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
        $fav_id = $chk_stmt->fetchColumn();

        if ($fav_id) {
            // Remove favorite
            $del_stmt = $pdo->prepare("DELETE FROM favorites WHERE id = :id");
            $del_stmt->execute([':id' => $fav_id]);

            echo json_encode([
                'success'        => true,
                'is_favorite'    => false,
                'favorite_count' => get_favorite_count($user_id),
                'product_id'     => $product_id,
                'product_name'   => $product['name'],
                'message'        => "Removed {$product['name']} from favorites"
            ]);
            exit;
        } else {
            // Add favorite
            $ins_stmt = $pdo->prepare("INSERT INTO favorites (user_id, product_id, created_at) VALUES (:uid, :pid, NOW())");
            $ins_stmt->execute([':uid' => $user_id, ':pid' => $product_id]);

            echo json_encode([
                'success'        => true,
                'is_favorite'    => true,
                'favorite_count' => get_favorite_count($user_id),
                'product_id'     => $product_id,
                'product_name'   => $product['name'],
                'message'        => "❤️ Added {$product['name']} to favorites!"
            ]);
            exit;
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error updating favorites.']);
        exit;
    }
}

// ----------------------------------------------------
// 2. Explicitly Remove from Favorites
// ----------------------------------------------------
elseif ($action === 'remove') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : (int)($_GET['product_id'] ?? 0);

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product.']);
        exit;
    }

    try {
        $del_stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid AND product_id = :pid");
        $del_stmt->execute([':uid' => $user_id, ':pid' => $product_id]);

        echo json_encode([
            'success'        => true,
            'is_favorite'    => false,
            'favorite_count' => get_favorite_count($user_id),
            'product_id'     => $product_id,
            'message'        => 'Product removed from favorites.'
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        exit;
    }
}

// ----------------------------------------------------
// 3. Get Current User Favorite Count & List
// ----------------------------------------------------
elseif ($action === 'get_info') {
    if (!is_logged_in()) {
        echo json_encode([
            'success'        => true,
            'is_logged_in'   => false,
            'favorite_count' => 0,
            'favorite_ids'   => []
        ]);
        exit;
    }

    echo json_encode([
        'success'        => true,
        'is_logged_in'   => true,
        'favorite_count' => get_favorite_count($user_id),
        'favorite_ids'   => get_user_favorite_ids($user_id)
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit;
