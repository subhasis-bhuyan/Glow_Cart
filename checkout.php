<?php
/**
 * GlowCart Cosmetics - Secure Checkout & Order Creation
 */
require_once __DIR__ . '/config/db.php';
require_login('login.php');

// Cart must not be empty
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['flash_error'] = 'Your cart is empty. Please add products before checking out.';
    header('Location: cart.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$errors = [];

// Fetch latest customer info to prefill form
try {
    $u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $u_stmt->execute([':id' => $user_id]);
    $user = $u_stmt->fetch();
} catch (PDOException $e) {
    die("Database error fetching customer profile.");
}

// Calculate Current Order Totals from DB items
$ids = array_keys($_SESSION['cart']);
$in_placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, name, price, discount_price, stock, image FROM products WHERE id IN ($in_placeholders)");
$stmt->execute($ids);
$products_db = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

$checkout_items = [];
$subtotal = 0.00;

foreach ($_SESSION['cart'] as $pid => $item) {
    if (isset($products_db[$pid])) {
        $db_prod = $products_db[$pid];
        $unit_price = (!empty($db_prod['discount_price']) && $db_prod['discount_price'] < $db_prod['price']) ? (float)$db_prod['discount_price'] : (float)$db_prod['price'];
        $qty = (int)$item['quantity'];

        // Stock check
        if ($qty > (int)$db_prod['stock']) {
            $_SESSION['flash_error'] = "Insufficient stock for '{$db_prod['name']}'. Only {$db_prod['stock']} units left.";
            header('Location: cart.php');
            exit;
        }

        $line_subtotal = $unit_price * $qty;
        $subtotal += $line_subtotal;
        $checkout_items[] = [
            'id'            => $pid,
            'name'          => $db_prod['name'],
            'price'         => $unit_price,
            'quantity'      => $qty,
            'subtotal'      => $line_subtotal,
            'current_stock' => (int)$db_prod['stock']
        ];
    }
}

$delivery_charge = ($subtotal >= 500) ? 0.00 : 50.00;
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

// Handle Order Placement POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name  = clean_input($_POST['customer_name'] ?? '');
    $email          = clean_input($_POST['email'] ?? '');
    $phone          = clean_input($_POST['phone'] ?? '');
    $address        = clean_input($_POST['address'] ?? '');
    $city           = clean_input($_POST['city'] ?? '');
    $state          = clean_input($_POST['state'] ?? '');
    $pincode        = clean_input($_POST['pincode'] ?? '');
    $payment_method = clean_input($_POST['payment_method'] ?? 'Cash on Delivery');
    $upi_id         = clean_input($_POST['upi_id'] ?? '');

    // Form Validations
    if (empty($customer_name)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'A valid 10-digit phone number is required.';
    if (empty($address)) $errors[] = 'Delivery address is required.';
    if (empty($city)) $errors[] = 'City is required.';
    if (empty($state)) $errors[] = 'State is required.';
    if (empty($pincode) || strlen($pincode) < 6) $errors[] = 'A valid 6-digit PIN code is required.';

    if ($payment_method === 'Demo UPI' && empty($upi_id)) {
        $errors[] = 'Please provide your demo UPI ID (e.g. username@okhdfcbank).';
    }

    if (empty($errors)) {
        try {
            // Begin Atomic MySQL Transaction
            $pdo->beginTransaction();

            $is_sqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
            $lock_clause = $is_sqlite ? '' : ' FOR UPDATE';

            // Double check and lock product rows for stock safety
            foreach ($checkout_items as $item) {
                $chk = $pdo->prepare("SELECT stock FROM products WHERE id = :id{$lock_clause}");
                $chk->execute([':id' => $item['id']]);
                $avail = (int)$chk->fetchColumn();

                if ($avail < $item['quantity']) {
                    throw new Exception("Product '{$item['name']}' has only {$avail} units left in stock. Order cannot proceed.");
                }
            }

            // 1. Insert into orders table
            $order_stmt = $pdo->prepare("
                INSERT INTO orders (user_id, customer_name, email, phone, address, city, state, pincode, total_amount, payment_method, payment_status, status, created_at)
                VALUES (:user_id, :name, :email, :phone, :address, :city, :state, :pincode, :total, :method, :pay_status, 'Pending', CURRENT_TIMESTAMP)
            ");
            $order_stmt->execute([
                ':user_id'    => $user_id,
                ':name'       => $customer_name,
                ':email'      => $email,
                ':phone'      => $phone,
                ':address'    => $address,
                ':city'       => $city,
                ':state'      => $state,
                ':pincode'    => $pincode,
                ':total'      => $grand_total,
                ':method'     => $payment_method,
                ':pay_status' => ($payment_method === 'Demo UPI') ? 'Paid (Demo Verified)' : 'Pending (COD)'
            ]);

            $order_id = (int)$pdo->lastInsertId();

            // 2. Insert order items & 3. Reduce product stock in MySQL
            $item_stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
                VALUES (:order_id, :product_id, :product_name, :price, :quantity, :subtotal)
            ");

            $stock_stmt = $pdo->prepare("
                UPDATE products 
                SET stock = stock - :qty 
                WHERE id = :id
            ");

            foreach ($checkout_items as $item) {
                $item_stmt->execute([
                    ':order_id'     => $order_id,
                    ':product_id'   => $item['id'],
                    ':product_name' => $item['name'],
                    ':price'        => $item['price'],
                    ':quantity'     => $item['quantity'],
                    ':subtotal'     => $item['subtotal']
                ]);

                // Reduce stock automatically
                $stock_stmt->execute([
                    ':qty' => $item['quantity'],
                    ':id'  => $item['id']
                ]);
            }

            // Also update user's default address for convenience if blank
            if (empty($user['address'])) {
                $upd_user = $pdo->prepare("UPDATE users SET address = :addr, city = :city, state = :state, pincode = :pin WHERE id = :uid");
                $upd_user->execute([
                    ':addr'  => $address,
                    ':city'  => $city,
                    ':state' => $state,
                    ':pin'   => $pincode,
                    ':uid'   => $user_id
                ]);
            }

            // Commit Transaction
            $pdo->commit();

            // Clear Cart Session
            $_SESSION['cart'] = [];
            $_SESSION['last_order_id'] = $order_id;

            header("Location: order_success.php?order_id={$order_id}");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }
}

$page_title = 'Secure Checkout | GlowCart Cosmetics';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container">
    <div style="padding: 25px 0 10px;">
        <h1 style="font-size: 28px; margin-bottom: 6px;">Checkout</h1>
        <p style="font-size: 14px; color: var(--text-muted);">Please enter your shipping address and choose a payment method to complete your purchase.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div style="background: var(--danger-bg); color: var(--danger); padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 25px; border: 1px solid rgba(198,40,40,0.2); font-size: 13px;">
            <ul style="margin-left: 18px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="checkout.php" method="POST">
        <div class="checkout-layout">
            <!-- Shipping & Payment Form Card -->
            <div>
                <div class="form-card">
                    <h2 class="form-title"><span>🚚</span> 1. Shipping Details</h2>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="customer_name" class="form-label">Full Name *</label>
                            <input type="text" id="customer_name" name="customer_name" class="form-control" value="<?= htmlspecialchars($_POST['customer_name'] ?? $user['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number (10 Digits) *</label>
                        <input type="tel" id="phone" name="phone" class="form-control" pattern="[0-9]{10}" value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Street Address / House No. *</label>
                        <textarea id="address" name="address" class="form-control" rows="2" placeholder="e.g. Flat 402, Rose Villa, MG Road" required><?= htmlspecialchars($_POST['address'] ?? ($user['address'] ?? '')) ?></textarea>
                    </div>

                    <div class="form-grid-3">
                        <div class="form-group">
                            <label for="city" class="form-label">City *</label>
                            <input type="text" id="city" name="city" class="form-control" value="<?= htmlspecialchars($_POST['city'] ?? ($user['city'] ?? '')) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="state" class="form-label">State *</label>
                            <input type="text" id="state" name="state" class="form-control" value="<?= htmlspecialchars($_POST['state'] ?? ($user['state'] ?? '')) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="pincode" class="form-label">PIN Code *</label>
                            <input type="text" id="pincode" name="pincode" class="form-control" pattern="[0-9]{6}" placeholder="751024" value="<?= htmlspecialchars($_POST['pincode'] ?? ($user['pincode'] ?? '')) ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Payment Selection Card -->
                <div class="form-card">
                    <h2 class="form-title"><span>💳</span> 2. Payment Method</h2>

                    <label class="payment-option-card active" id="codOption">
                        <input type="radio" name="payment_method" value="Cash on Delivery" checked onchange="togglePaymentUI('cod')">
                        <div>
                            <strong>💵 Cash on Delivery (COD)</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">Pay in cash when your beauty parcel arrives at your doorstep.</div>
                        </div>
                    </label>

                    <label class="payment-option-card" id="upiOption">
                        <input type="radio" name="payment_method" value="Demo UPI" onchange="togglePaymentUI('upi')">
                        <div>
                            <strong>📱 Demo UPI / GPay / PhonePe (Simulated)</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">Instant simulated verification for college project demonstration.</div>
                        </div>
                    </label>

                    <!-- UPI ID Input (Shown when Demo UPI selected) -->
                    <div id="upiFieldBox" style="display: none; background: var(--surface-alt); padding: 15px; border-radius: var(--radius-sm); margin-top: 15px; border: 1px solid var(--border-color);">
                        <label for="upi_id" class="form-label">Enter Demo Virtual Payment Address (UPI ID):</label>
                        <input type="text" id="upi_id" name="upi_id" class="form-control" placeholder="e.g. subhasis@okhdfcbank" value="user@demo-upi">
                        <small style="display: block; margin-top: 5px; color: var(--text-muted);">Note: No real money will be charged. This is an academic demo.</small>
                    </div>
                </div>
            </div>

            <!-- Order Review Sidebar -->
            <div class="cart-summary-card">
                <h3 class="summary-title">Order Items (<?= count($checkout_items) ?>)</h3>

                <div style="max-height: 240px; overflow-y: auto; margin-bottom: 20px;">
                    <?php foreach ($checkout_items as $item): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13px; padding-bottom: 10px; border-bottom: 1px dashed var(--border-color);">
                            <div>
                                <strong style="display: block; color: var(--text-main);"><?= htmlspecialchars($item['name']) ?></strong>
                                <span style="color: var(--text-muted); font-size: 12px;"><?= $item['quantity'] ?> &times; <?= format_price($item['price']) ?></span>
                            </div>
                            <div style="font-weight: 600; color: var(--primary);">
                                <?= format_price($item['subtotal']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row">
                    <span>Items Subtotal</span>
                    <strong><?= format_price($subtotal) ?></strong>
                </div>

                <?php if ($discount > 0): ?>
                    <div class="summary-row" style="color: var(--success);">
                        <span>Discount <?= !empty($applied_coupon) ? '(' . htmlspecialchars($applied_coupon) . ')' : '(Tier 10%)' ?></span>
                        <strong>-<?= format_price($discount) ?></strong>
                    </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span>Delivery Charge</span>
                    <strong>
                        <?php if ($delivery_charge === 0.00): ?>
                            <span style="color: var(--success);">FREE</span>
                        <?php else: ?>
                            <?= format_price($delivery_charge) ?>
                        <?php endif; ?>
                    </strong>
                </div>

                <div class="summary-row total">
                    <span>Grand Total</span>
                    <span><?= format_price($grand_total) ?></span>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 25px;">
                    🛍️ Confirm & Place Order
                </button>

                <div style="text-align: center; margin-top: 15px; font-size: 12px; color: var(--text-muted);">
                    🔒 Guaranteed 256-Bit SSL Encrypted Checkout
                </div>
            </div>
        </div>
    </form>
</main>

<script>
function togglePaymentUI(type) {
    const upiBox = document.getElementById('upiFieldBox');
    const codCard = document.getElementById('codOption');
    const upiCard = document.getElementById('upiOption');

    if (type === 'upi') {
        upiBox.style.display = 'block';
        upiCard.classList.add('active');
        codCard.classList.remove('active');
    } else {
        upiBox.style.display = 'none';
        codCard.classList.add('active');
        upiCard.classList.remove('active');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
