<?php
/**
 * GlowCart Cosmetics - AJAX Cart Operations
 */
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Helper to compute full cart breakdown
function calculate_current_cart_totals(PDO $pdo): array {
    $cart_items = [];
    $subtotal = 0.00;

    if (!empty($_SESSION['cart'])) {
        $ids = array_keys($_SESSION['cart']);
        $in_placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, category, price, discount_price, stock, image, status FROM products WHERE id IN ($in_placeholders)");
        $stmt->execute($ids);
        $products_db = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

        foreach ($_SESSION['cart'] as $pid => $item) {
            if (isset($products_db[$pid]) && $products_db[$pid]['status'] === 'Active') {
                $db_prod = $products_db[$pid];
                $unit_price = (!empty($db_prod['discount_price']) && $db_prod['discount_price'] < $db_prod['price']) 
                    ? (float)$db_prod['discount_price'] 
                    : (float)$db_prod['price'];
                $qty = min((int)$item['quantity'], (int)$db_prod['stock']);
                if ($qty > 0) {
                    $line_subtotal = $unit_price * $qty;
                    $subtotal += $line_subtotal;
                    $cart_items[$pid] = [
                        'price' => $unit_price,
                        'quantity' => $qty,
                        'line_subtotal' => $line_subtotal
                    ];
                }
            }
        }
    }

    $delivery_charge = ($subtotal >= 500 || $subtotal == 0) ? 0.00 : 50.00;
    
    // Check coupon discount
    $coupon_code = strtoupper(trim($_SESSION['applied_coupon'] ?? ''));
    $coupon_discount_rate = 0.00;
    if ($coupon_code === 'GLOW15') {
        $coupon_discount_rate = 0.15;
    } elseif ($coupon_code === 'GLOW20') {
        $coupon_discount_rate = 0.20;
    }

    if ($coupon_discount_rate > 0) {
        $discount = round($subtotal * $coupon_discount_rate, 2);
    } elseif ($subtotal > 1000) {
        $discount = round($subtotal * 0.10, 2); // Default tier discount
    } else {
        $discount = 0.00;
    }

    $grand_total = max(0, $subtotal - $discount + $delivery_charge);

    return [
        'subtotal'                  => $subtotal,
        'formatted_subtotal'        => format_price($subtotal),
        'discount'                  => $discount,
        'formatted_discount'        => ($discount > 0 ? '-' . format_price($discount) : '₹0.00'),
        'delivery_charge'           => $delivery_charge,
        'formatted_delivery_charge' => ($delivery_charge === 0.00 ? 'FREE' : format_price($delivery_charge)),
        'grand_total'               => $grand_total,
        'formatted_grand_total'     => format_price($grand_total),
        'cart_count'                => get_cart_count(),
        'items'                     => $cart_items,
        'coupon_code'               => $coupon_code
    ];
}

$action = $_POST['action'] ?? $_GET['action'] ?? $_REQUEST['action'] ?? '';

// ----------------------------------------------------
// 1. Add Product to Cart
// ----------------------------------------------------
if ($action === 'add') {
    $product_id = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;
    $quantity   = isset($_REQUEST['quantity']) ? max(1, (int)$_REQUEST['quantity']) : 1;

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
    $product_id = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;
    $quantity   = isset($_REQUEST['quantity']) ? (int)$_REQUEST['quantity'] : 1;

    if ($product_id <= 0 || !isset($_SESSION['cart'][$product_id])) {
        echo json_encode(['success' => false, 'message' => 'Item not in cart.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT stock, price, discount_price FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);
        $prod_info = $stmt->fetch();

        if (!$prod_info) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }

        $stock = (int)$prod_info['stock'];
        if ($quantity > $stock) {
            echo json_encode(['success' => false, 'message' => "Only {$stock} units available in stock."]);
            exit;
        }

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        }

        $totals = calculate_current_cart_totals($pdo);
        $item_line_subtotal = isset($totals['items'][$product_id]) ? $totals['items'][$product_id]['line_subtotal'] : 0;

        echo json_encode([
            'success'                     => true,
            'cart_count'                  => $totals['cart_count'],
            'item_removed'                => ($quantity <= 0),
            'item_quantity'               => $quantity,
            'item_line_subtotal'          => $item_line_subtotal,
            'formatted_item_line_subtotal'=> format_price($item_line_subtotal),
            'subtotal'                    => $totals['subtotal'],
            'formatted_subtotal'          => $totals['formatted_subtotal'],
            'discount'                    => $totals['discount'],
            'formatted_discount'          => $totals['formatted_discount'],
            'delivery_charge'             => $totals['delivery_charge'],
            'formatted_delivery_charge'   => $totals['formatted_delivery_charge'],
            'grand_total'                 => $totals['grand_total'],
            'formatted_grand_total'       => $totals['formatted_grand_total'],
            'cart_empty'                  => ($totals['cart_count'] === 0)
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating quantity.']);
        exit;
    }
}

// ----------------------------------------------------
// 3. Remove Item from Cart
// ----------------------------------------------------
elseif ($action === 'remove') {
    $product_id = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
    $totals = calculate_current_cart_totals($pdo);

    echo json_encode([
        'success'                  => true,
        'cart_count'               => $totals['cart_count'],
        'subtotal'                 => $totals['subtotal'],
        'formatted_subtotal'       => $totals['formatted_subtotal'],
        'discount'                 => $totals['discount'],
        'formatted_discount'       => $totals['formatted_discount'],
        'delivery_charge'          => $totals['delivery_charge'],
        'formatted_delivery_charge'=> $totals['formatted_delivery_charge'],
        'grand_total'              => $totals['grand_total'],
        'formatted_grand_total'    => $totals['formatted_grand_total'],
        'cart_empty'               => ($totals['cart_count'] === 0)
    ]);
    exit;
}

// ----------------------------------------------------
// 4. Quick View Product Details API
// ----------------------------------------------------
elseif ($action === 'quick_view') {
    $product_id = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;

    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id AND status = 'Active'");
        $stmt->execute([':id' => $product_id]);
        $p = $stmt->fetch();

        if (!$p) {
            echo json_encode(['success' => false, 'message' => 'Product not found or unavailable.']);
            exit;
        }

        $eff_price = (!empty($p['discount_price']) && $p['discount_price'] < $p['price']) ? (float)$p['discount_price'] : (float)$p['price'];
        $has_disc = (!empty($p['discount_price']) && $p['discount_price'] < $p['price']);
        $disc_pct = $has_disc ? round((($p['price'] - $p['discount_price']) / $p['price']) * 100) : 0;

        echo json_encode([
            'success'                  => true,
            'product' => [
                'id'                       => (int)$p['id'],
                'name'                     => $p['name'],
                'category'                 => $p['category'],
                'description'              => $p['description'],
                'price'                    => $eff_price,
                'formatted_price'          => format_price($eff_price),
                'original_price'           => (float)$p['price'],
                'formatted_original_price' => format_price($p['price']),
                'has_discount'             => $has_disc,
                'discount_percent'         => $disc_pct,
                'stock'                    => (int)$p['stock'],
                'in_stock'                 => ((int)$p['stock'] > 0),
                'image'                    => $p['image'],
                'rating'                   => (float)$p['rating'],
                'url'                      => 'product_details.php?id=' . (int)$p['id']
            ]
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error retrieving product data.']);
        exit;
    }
}

// ----------------------------------------------------
// 5. Apply Promo Coupon Code API
// ----------------------------------------------------
elseif ($action === 'apply_coupon') {
    $code = strtoupper(trim($_REQUEST['coupon_code'] ?? ''));

    if (empty($code)) {
        unset($_SESSION['applied_coupon']);
        $totals = calculate_current_cart_totals($pdo);
        echo json_encode([
            'success' => true,
            'message' => 'Coupon removed.',
            'totals'  => $totals
        ]);
        exit;
    }

    if ($code === 'GLOW15') {
        $_SESSION['applied_coupon'] = 'GLOW15';
        $totals = calculate_current_cart_totals($pdo);
        echo json_encode([
            'success' => true,
            'message' => '🎉 Coupon GLOW15 applied! 15% discount activated.',
            'code'    => 'GLOW15',
            'totals'  => $totals
        ]);
        exit;
    } elseif ($code === 'GLOW20') {
        $_SESSION['applied_coupon'] = 'GLOW20';
        $totals = calculate_current_cart_totals($pdo);
        echo json_encode([
            'success' => true,
            'message' => '✨ VIP Coupon GLOW20 applied! 20% discount activated.',
            'code'    => 'GLOW20',
            'totals'  => $totals
        ]);
        exit;
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid promo coupon code. Try code "GLOW15".'
        ]);
        exit;
    }
}

// ----------------------------------------------------
// 6. Get Current Cart Count
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
