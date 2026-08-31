<?php
/**
 * GlowCart Cosmetics - Order Success & Receipt Confirmation
 */
require_once __DIR__ . '/config/db.php';
require_login('login.php');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id  = (int)$_SESSION['user_id'];

if ($order_id <= 0) {
    header('Location: orders.php');
    exit;
}

try {
    // Verify order belongs to logged-in customer
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :uid");
    $stmt->execute([':id' => $order_id, ':uid' => $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        die("<div style='font-family:sans-serif;padding:40px;text-align:center;'><h2>Order not found.</h2><a href='orders.php'>View My Orders</a></div>");
    }

    // Fetch ordered item lines
    $item_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :oid");
    $item_stmt->execute([':oid' => $order_id]);
    $items = $item_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database query error.");
}

$page_title = 'Order Placed Successfully! | GlowCart Cosmetics';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container">
    <div class="success-wrapper">
        <div class="success-icon">✓</div>
        <h1 style="font-size: 28px; color: var(--text-main); margin-bottom: 8px;">ORDER PLACED SUCCESSFULLY!</h1>
        <p style="font-size: 16px; color: var(--primary); font-weight: 500;">Thank you for shopping with GlowCart Cosmetics.</p>
        <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">A confirmation has been sent to your email <strong><?= htmlspecialchars($order['email']) ?></strong>.</p>

        <!-- Order Receipt Card -->
        <div class="order-receipt-box">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">Order Reference</span>
                    <h3 style="font-size: 18px; color: var(--primary);">#GC-<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h3>
                </div>
                <div>
                    <span style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">Order Date</span>
                    <div style="font-size: 14px; font-weight: 500;"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
                </div>
                <div>
                    <span style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">Status</span>
                    <div><span class="status-pill status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></div>
                </div>
            </div>

            <!-- Customer & Shipping Summary -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 13px; margin-bottom: 20px;">
                <div>
                    <strong>Customer Name:</strong> <?= htmlspecialchars($order['customer_name']) ?><br>
                    <strong>Contact Phone:</strong> <?= htmlspecialchars($order['phone']) ?>
                </div>
                <div>
                    <strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?><br>
                    <strong>Delivery Address:</strong> <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> - <?= htmlspecialchars($order['pincode']) ?>
                </div>
            </div>

            <!-- Items Table -->
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted); text-align: left;">
                        <th style="padding: 8px 0;">Item Name</th>
                        <th style="padding: 8px 0; text-align: center;">Qty</th>
                        <th style="padding: 8px 0; text-align: right;">Unit Price</th>
                        <th style="padding: 8px 0; text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr style="border-bottom: 1px dashed var(--border-color);">
                            <td style="padding: 10px 0;"><strong><?= htmlspecialchars($item['product_name']) ?></strong></td>
                            <td style="padding: 10px 0; text-align: center;"><?= $item['quantity'] ?></td>
                            <td style="padding: 10px 0; text-align: right;"><?= format_price($item['price']) ?></td>
                            <td style="padding: 10px 0; text-align: right; font-weight: 600;"><?= format_price($item['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="padding: 14px 0 4px; text-align: right; font-size: 15px; font-weight: 700;">Grand Total:</td>
                        <td style="padding: 14px 0 4px; text-align: right; font-size: 18px; font-weight: 700; color: var(--primary);"><?= format_price($order['total_amount']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
            <a href="orders.php" class="btn btn-primary btn-lg">📦 VIEW MY ORDERS</a>
            <a href="products.php" class="btn btn-outline btn-lg">🛍️ CONTINUE SHOPPING</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
