<?php
/**
 * GlowCart Cosmetics - AJAX Cart Operations
 */
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ----------------------------------------------------
// 1. Add Product to Cart
// ----------------------------------------------------
if ($action === 'add') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity   = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, price, discount_price, stock, image, status FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);
        $product = $stmt->fetch();

        if (!$product || $product['status'] !== 'Active') {
            echo json_encode(['success' => false, 'message' => 'Product is not available.']);
            exit;
        }

        if ($product['stock'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sorry, this product is currently out of stock.']);
            exit;
        }

        $current_qty_in_cart = isset($_SESSION['cart'][$product_id]) ? (int)$_SESSION['cart'][$product_id]['quantity'] : 0;
        $new_qty = $current_qty_in_cart + $quantity;

        if ($new_qty > $product['stock']) {
            echo json_encode([
                'success' => false,
                'message' => "Only {$product['stock']} units available in stock. You already have {$current_qty_in_cart} in cart."
            ]);
            exit;
        }

        $effective_price = (!empty($product['discount_price']) && $product['discount_price'] < $product['price']) ? (float)$product['discount_price'] : (float)$product['price'];

        $_SESSION['cart'][$product_id] = [
            'id'       => $product['id'],
            'name'     => $product['name'],
            'price'    => $effective_price,
            'image'    => $product['image'],
            'stock'    => (int)$product['stock'],
            'quantity' => $new_qty
        ];

        echo json_encode([
            'success'      => true,
            'message'      => "✓ {$product['name']} added to cart!",
            'cart_count'   => get_cart_count(),
            'product_name' => $product['name']
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error while updating cart.']);
        exit;
    }
}

// ----------------------------------------------------
// 2. Update Item Quantity in Cart
// ----------------------------------------------------
elseif ($action === 'update') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity   = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_id <= 0 || !isset($_SESSION['cart'][$product_id])) {
        echo json_encode(['success' => false, 'message' => 'Item not in cart.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);
        $stock = (int)$stmt->fetchColumn();

        if ($quantity > $stock) {
            echo json_encode(['success' => false, 'message' => "Only {$stock} units available in stock."]);
            exit;
        }

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        }

        echo json_encode([
            'success'    => true,
            'cart_count' => get_cart_count()
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating quantity.']);
        exit;
    }
}

// ----------------------------------------------------
// 4. Remove Item from Cart
// ----------------------------------------------------
elseif ($action === 'remove') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
    echo json_encode([
        'success'    => true,
        'cart_count' => get_cart_count()
    ]);
    exit;
}

// ----------------------------------------------------
// 5. Get Current Cart Count
// ----------------------------------------------------
elseif ($action === 'get_count') {
    echo json_encode([
        'success'    => true,
        'cart_count' => get_cart_count()
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit;
