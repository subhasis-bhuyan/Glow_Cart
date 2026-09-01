<?php
/**
 * GlowCart Cosmetics - Shopping Cart Page
 */
require_once __DIR__ . '/config/db.php';

// Initialize Cart Array
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Form Submissions (Quantity updates, Removals, Clear Cart)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_cart') {
        if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
            foreach ($_POST['quantities'] as $pid => $qty) {
                $pid = (int)$pid;
                $qty = (int)$qty;
                if (isset($_SESSION['cart'][$pid])) {
                    // Check stock limit in DB
                    $stock_stmt = $pdo->prepare("SELECT stock FROM products WHERE id = :id");
                    $stock_stmt->execute([':id' => $pid]);
                    $available_stock = (int)$stock_stmt->fetchColumn();

                    if ($qty <= 0) {
                        unset($_SESSION['cart'][$pid]);
                    } else {
                        $_SESSION['cart'][$pid]['quantity'] = min($qty, $available_stock);
                    }
                }
            }
            $_SESSION['flash_success'] = 'Cart updated successfully.';
        }
        header('Location: cart.php');
        exit;
    }

    if ($action === 'remove_item') {
        $pid = (int)($_POST['product_id'] ?? 0);
        if (isset($_SESSION['cart'][$pid])) {
            unset($_SESSION['cart'][$pid]);
            $_SESSION['flash_success'] = 'Item removed from your cart.';
        }
        header('Location: cart.php');
        exit;
    }

    if ($action === 'clear_cart') {
        $_SESSION['cart'] = [];
        $_SESSION['flash_success'] = 'Your cart has been cleared.';
        header('Location: cart.php');
        exit;
    }
}

// Refresh Cart Item Details against current database prices and stock
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
            $unit_price = (!empty($db_prod['discount_price']) && $db_prod['discount_price'] < $db_prod['price']) ? (float)$db_prod['discount_price'] : (float)$db_prod['price'];
            $qty = min((int)$item['quantity'], (int)$db_prod['stock']);
            
            if ($qty > 0) {
                $line_subtotal = $unit_price * $qty;
                $subtotal += $line_subtotal;

                $cart_items[$pid] = [
                    'id'            => $pid,
                    'name'          => $db_prod['name'],
                    'category'      => $db_prod['category'],
                    'image'         => $db_prod['image'],
                    'price'         => $unit_price,
                    'stock'         => (int)$db_prod['stock'],
                    'quantity'      => $qty,
                    'line_subtotal' => $line_subtotal
                ];
                // Keep session updated
                $_SESSION['cart'][$pid]['quantity'] = $qty;
                $_SESSION['cart'][$pid]['price'] = $unit_price;
            } else {
                unset($_SESSION['cart'][$pid]);
            }
        } else {
            unset($_SESSION['cart'][$pid]);
        }
    }
}

// Discount & Delivery Calculation
$delivery_charge = ($subtotal >= 500 || $subtotal == 0) ? 0.00 : 50.00;
$applied_coupon = $_SESSION['applied_coupon'] ?? null;
$coupon_discount = 0.00;
if ($applied_coupon === 'GLOW15' && $subtotal >= 500) {
    $coupon_discount = round($subtotal * 0.15, 2);
} elseif ($applied_coupon === 'GLOW20' && $subtotal >= 1000) {
    $coupon_discount = round($subtotal * 0.20, 2);
}
$tier_discount = ($subtotal > 1000 && empty($applied_coupon)) ? round($subtotal * 0.10, 2) : 0.00;
$discount = $coupon_discount > 0 ? $coupon_discount : $tier_discount;
$grand_total = max(0.00, $subtotal - $discount + $delivery_charge);

$page_title = 'Shopping Cart (' . get_cart_count() . ' items) | GlowCart Cosmetics';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container">
    <div style="padding: 25px 0 10px;">
        <h1 style="font-size: 28px; margin-bottom: 6px;">Shopping Cart</h1>
        <p style="font-size: 14px; color: var(--text-muted);">Review your selected beauty items before moving to secure checkout.</p>
    </div>

    <?php if (!empty($cart_items)): ?>
        <form action="cart.php" method="POST" id="cartForm">
            <input type="hidden" name="action" value="update_cart">

            <div class="cart-layout">
                <!-- Cart Items Table Card -->
                <div class="cart-table-card">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr id="cartRow-<?= $item['id'] ?>">
                                    <td>
                                        <div class="cart-item-info">
                                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-img">
                                            <div>
                                                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--rose-gold-dark);">
                                                    <?= htmlspecialchars($item['category']) ?>
                                                </div>
                                                <strong style="font-size: 14px;">
                                                    <a href="product_details.php?id=<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                                                </strong>
                                                <div style="font-size: 13px; color: var(--primary); font-weight: 600; margin-top: 3px;">
                                                    <?= format_price($item['price']) ?>
                                                </div>
                                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 1px;">
                                                    <?= $item['stock'] ?> in stock
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <strong><?= format_price($item['price']) ?></strong>
                                    </td>

                                    <td>
                                        <div class="cart-mobile-row">
                                            <div class="quantity-control">
                                                <button type="button" class="qty-btn qty-minus">-</button>
                                                <input type="number" name="quantities[<?= $item['id'] ?>]" class="qty-input" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" readonly>
                                                <button type="button" class="qty-btn qty-plus">+</button>
                                            </div>
                                            <strong style="color: var(--primary); font-size: 16px; display: none;" class="mobile-line-total">
                                                <?= format_price($item['line_subtotal']) ?>
                                            </strong>
                                        </div>
                                    </td>

                                    <td>
                                        <strong style="color: var(--primary); font-size: 15px;" id="lineSubtotal-<?= $item['id'] ?>">
                                            <?= format_price($item['line_subtotal']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <div class="cart-mobile-actions">
                                            <button type="button" class="cart-remove-btn" onclick="removeItemFromCart(<?= $item['id'] ?>)" title="Remove Item" aria-label="Remove item">
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="padding: 16px 20px; background: var(--surface-alt); border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <a href="products.php" class="btn btn-outline btn-sm">&larr; Continue Shopping</a>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-secondary btn-sm">🔄 Update Cart</button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="clearFullCart()" style="color: var(--danger); border-color: var(--border-color);">Clear Cart</button>
                        </div>
                    </div>
                </div>

                <!-- Order Summary Card -->
                <div class="cart-summary-card">
                    <h3 class="summary-title">Order Summary</h3>

                    <div class="summary-row">
                        <span>Items Subtotal</span>
                        <strong id="cartSubtotalDisplay"><?= format_price($subtotal) ?></strong>
                    </div>

                    <div class="summary-row" id="cartDiscountRow" style="color: var(--success); display: <?= ($discount > 0) ? 'flex' : 'none' ?>;">
                        <span>Discount <?= !empty($applied_coupon) ? '(' . htmlspecialchars($applied_coupon) . ')' : ($subtotal > 1000 ? '(Tier 10%)' : '') ?></span>
                        <strong id="cartDiscountDisplay">-<?= format_price($discount) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <strong id="cartDeliveryDisplay">
                            <?php if ($delivery_charge === 0.00): ?>
                                <span style="color: var(--success); font-weight: 600;">FREE</span>
                            <?php else: ?>
                                <?= format_price($delivery_charge) ?>
                            <?php endif; ?>
                        </strong>
                    </div>

                    <div class="summary-row total">
                        <span>Grand Total</span>
                        <span id="cartGrandTotalDisplay"><?= format_price($grand_total) ?></span>
                    </div>

                    <!-- Interactive Promo Coupon Section -->
                    <div class="cart-coupon-box">
                        <label for="couponCodeInput" style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--text-main); display: block;">
                            🏷️ Have a Promo Coupon?
                        </label>
                        <div class="cart-coupon-input-wrap">
                            <input type="text" id="couponCodeInput" class="cart-coupon-input" placeholder="e.g. GLOW15 or GLOW20" value="<?= htmlspecialchars($applied_coupon ?? '') ?>" autocomplete="off">
                            <button type="button" id="applyCouponBtn" class="btn btn-primary btn-sm">Apply</button>
                        </div>
                        <div id="appliedCouponBadgeWrap">
                            <?php if (!empty($applied_coupon)): ?>
                                <div class="cart-coupon-badge-applied">
                                    <span>🎉 Coupon <strong><?= htmlspecialchars($applied_coupon) ?></strong> Applied!</span>
                                    <button type="button" class="cart-coupon-remove-btn" onclick="removeCouponCode()">✕</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="margin: 20px 0 10px;">
                        <?php if (is_logged_in()): ?>
                            <a href="checkout.php" class="btn btn-primary btn-block btn-lg">
                                Proceed to Checkout &rarr;
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary btn-block btn-lg">
                                Login to Checkout &rarr;
                            </a>
                            <small style="display: block; text-align: center; margin-top: 8px; color: var(--text-muted);">
                                Please log in to complete your purchase.
                            </small>
                        <?php endif; ?>
                    </div>

                    <div style="background: var(--primary-soft); border: 1px dashed var(--primary-light); padding: 12px; border-radius: var(--radius-sm); font-size: 12px; text-align: center; margin-top: 15px;">
                        🚚 <strong>Free Shipping</strong> applied on all orders above ₹500!
                    </div>
                </div>
            </div>
        </form>

        <!-- Hidden Helper Form for Removal / Clearing -->
        <form id="actionForm" action="cart.php" method="POST" style="display: none;">
            <input type="hidden" name="action" id="actionType" value="">
            <input type="hidden" name="product_id" id="actionPid" value="">
        </form>

        <script>
        function removeItemFromCart(pid) {
            if (confirm('Remove this product from your shopping cart?')) {
                document.getElementById('actionType').value = 'remove_item';
                document.getElementById('actionPid').value = pid;
                document.getElementById('actionForm').submit();
            }
        }

        function clearFullCart() {
            if (confirm('Are you sure you want to clear your entire cart?')) {
                document.getElementById('actionType').value = 'clear_cart';
                document.getElementById('actionForm').submit();
            }
        }
        </script>

    <?php else: ?>
        <div style="background: var(--surface); padding: 70px 20px; text-align: center; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin: 30px 0 60px;">
            <div style="font-size: 60px; margin-bottom: 15px;">🛍️</div>
            <h2 style="font-size: 24px; margin-bottom: 8px;">Your Shopping Cart is Empty</h2>
            <p style="margin-bottom: 25px; max-width: 450px; margin-left: auto; margin-right: auto;">Looks like you haven't added any luxury beauty products to your bag yet. Discover our best sellers and glow!</p>
            <a href="products.php" class="btn btn-primary btn-lg">Explore Beauty Products</a>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
