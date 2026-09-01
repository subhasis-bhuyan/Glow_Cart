<?php
/**
 * GlowCart Cosmetics - Order Details, Status Update & Invoice Printing
 */
require_once __DIR__ . '/auth_check.php';

$order_id = (int)($_GET['id'] ?? ($_POST['order_id'] ?? 0));

if ($order_id <= 0) {
    $_SESSION['flash_error'] = "Invalid order ID specified.";
    header('Location: orders.php');
    exit;
}

// Handle Order Status Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    $new_status = clean_input($_POST['status'] ?? '');
    $new_payment_status = clean_input($_POST['payment_status'] ?? '');
    $restock_inventory = isset($_POST['restock_inventory']) ? 1 : 0;

    $allowed_statuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];

    if (in_array($new_status, $allowed_statuses) && !empty($new_payment_status)) {
        try {
            $pdo->beginTransaction();

            // Fetch current order status to check for transitions
            $curr_stmt = $pdo->prepare("SELECT status FROM orders WHERE id = :id");
            $curr_stmt->execute([':id' => $order_id]);
            $old_status = $curr_stmt->fetchColumn();

            // If changing to Cancelled and restock is requested
            if ($new_status === 'Cancelled' && $old_status !== 'Cancelled' && $restock_inventory === 1) {
                $items_stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :oid");
                $items_stmt->execute([':oid' => $order_id]);
                $items_to_restore = $items_stmt->fetchAll();

                $restore_stmt = $pdo->prepare("UPDATE products SET stock = stock + :qty WHERE id = :pid");
                foreach ($items_to_restore as $it) {
                    if (!empty($it['product_id'])) {
                        $restore_stmt->execute([
                            ':qty' => (int)$it['quantity'],
                            ':pid' => (int)$it['product_id']
                        ]);
                    }
                }
            }

            // Update order
            $upd = $pdo->prepare("
                UPDATE orders 
                SET status = :status, payment_status = :payment_status 
                WHERE id = :id
            ");
            $upd->execute([
                ':status'         => $new_status,
                ':payment_status' => $new_payment_status,
                ':id'             => $order_id
            ]);

            $pdo->commit();
            $_SESSION['flash_success'] = "Order #GC-" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . " status updated to '{$new_status}' (Payment: '{$new_payment_status}').";
            header("Location: order_details.php?id={$order_id}");
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash_error'] = "Failed to update order: " . $e->getMessage();
        }
    }
}

// Fetch Order Information
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['flash_error'] = "Order #{$order_id} was not found.";
        header('Location: orders.php');
        exit;
    }

    // Fetch Order Items with product image
    $items_stmt = $pdo->prepare("
        SELECT oi.*, p.image, p.category 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = :oid
    ");
    $items_stmt->execute([':oid' => $order_id]);
    $order_items = $items_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$admin_page_title = "Order #GC-" . str_pad($order['id'], 5, '0', STR_PAD_LEFT) . " | GlowCart Admin";
$admin_header_title = "Order Details";
$active_tab = 'orders';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="page-header no-print">
    <div>
        <h1 class="page-title">Order #GC-<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h1>
        <p style="color: var(--admin-muted); font-size: 13px; margin-top: 4px;">
            Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?>
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="button" onclick="window.print()" class="admin-btn admin-btn-outline">
            <span>🖨️</span> Print Invoice / Receipt
        </button>
        <a href="orders.php" class="admin-btn admin-btn-outline">
            &larr; All Orders
        </a>
    </div>
</div>

<!-- Print-Only Header -->
<div style="display: none; @media print { display: block; margin-bottom: 25px; }" class="print-header">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #d81b60; padding-bottom: 15px;">
        <div>
            <h1 style="color: #d81b60; font-size: 24px; margin: 0;">💄 GLOWCART COSMETICS</h1>
            <div style="font-size: 12px; color: #666; margin-top: 4px;">Official Purchase Tax Invoice & Delivery Receipt</div>
            <div style="font-size: 11px; color: #888;">Bhubaneswar, Odisha | support@glowcart.com</div>
        </div>
        <div style="text-align: right;">
            <h2 style="font-size: 18px; margin: 0;">INVOICE #GC-<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h2>
            <div style="font-size: 12px; color: #666; margin-top: 4px;">Date: <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
            <div style="font-size: 12px; font-weight: bold; color: #2e7d32;">Status: <?= htmlspecialchars($order['status']) ?></div>
        </div>
    </div>
</div>

<div class="order-details-grid">
    <!-- Left Column: Order Items Table -->
    <div>
        <div class="card-table">
            <div class="card-table-header">
                <h3 style="font-size: 15px; font-weight: 600;">Ordered Beauty Items (<?= count($order_items) ?>)</h3>
            </div>

            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Item</th>
                            <th>Product Name</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td>
                                    <?php $img = $item['image'] ?: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=100'; ?>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="" class="admin-product-thumb">
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--admin-text);">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    </div>
                                    <?php if (!empty($item['category'])): ?>
                                        <div style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($item['category']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= format_price($item['price']) ?>
                                </td>
                                <td style="font-weight: 600;">
                                    &times; <?= (int)$item['quantity'] ?>
                                </td>
                                <td style="font-weight: 700; text-align: right;">
                                    <?= format_price($item['subtotal']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: #f8fafc; font-size: 14px;">
                            <td colspan="4" style="text-align: right; font-weight: 600;">Grand Total Amount:</td>
                            <td style="text-align: right; font-weight: 700; font-size: 16px; color: var(--admin-primary);">
                                <?= format_price($order['total_amount']) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Customer & Shipping Information -->
        <div class="admin-summary-card">
            <div class="admin-summary-title">
                <span>📍 Shipping & Customer Information</span>
                <span style="font-size: 12px; font-weight: normal; color: var(--admin-muted);">User ID: #<?= (int)$order['user_id'] ?></span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h4 style="font-size: 13px; color: var(--admin-muted); margin-bottom: 6px;">Customer Contact</h4>
                    <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($order['customer_name']) ?></div>
                    <div style="font-size: 13px; color: var(--admin-text); margin-top: 3px;">📧 <?= htmlspecialchars($order['email']) ?></div>
                    <div style="font-size: 13px; color: var(--admin-text); margin-top: 3px;">📞 <?= htmlspecialchars($order['phone']) ?></div>
                </div>

                <div>
                    <h4 style="font-size: 13px; color: var(--admin-muted); margin-bottom: 6px;">Delivery Address</h4>
                    <div style="font-size: 13px; line-height: 1.5; color: var(--admin-text);">
                        <?= nl2br(htmlspecialchars($order['address'])) ?><br>
                        <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> - <strong><?= htmlspecialchars($order['pincode']) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Status Controls & Payment Summary -->
    <div>
        <!-- Order Processing Form -->
        <div class="admin-summary-card no-print">
            <div class="admin-summary-title">
                <span>⚡ Update Order Status</span>
            </div>

            <form action="order_details.php?id=<?= $order_id ?>" method="POST">
                <input type="hidden" name="action" value="update_order">
                <input type="hidden" name="order_id" value="<?= $order_id ?>">

                <div class="admin-form-group">
                    <label for="orderStatus">Fulfillment Status</label>
                    <select id="orderStatus" name="status" class="admin-form-control" style="font-weight: 600;">
                        <option value="Pending" <?= ($order['status'] === 'Pending') ? 'selected' : '' ?>>⏳ Pending</option>
                        <option value="Confirmed" <?= ($order['status'] === 'Confirmed') ? 'selected' : '' ?>>📋 Confirmed</option>
                        <option value="Shipped" <?= ($order['status'] === 'Shipped') ? 'selected' : '' ?>>🚚 Shipped</option>
                        <option value="Delivered" <?= ($order['status'] === 'Delivered') ? 'selected' : '' ?>>✅ Delivered</option>
                        <option value="Cancelled" <?= ($order['status'] === 'Cancelled') ? 'selected' : '' ?>>❌ Cancelled</option>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label for="payStatus">Payment Status</label>
                    <select id="payStatus" name="payment_status" class="admin-form-control">
                        <option value="Pending" <?= ($order['payment_status'] === 'Pending') ? 'selected' : '' ?>>Pending Payment</option>
                        <option value="Paid" <?= ($order['payment_status'] === 'Paid') ? 'selected' : '' ?>>Paid (Completed)</option>
                        <option value="Refund Initiated (Demo)" <?= ($order['payment_status'] === 'Refund Initiated (Demo)') ? 'selected' : '' ?>>Refund Initiated</option>
                        <option value="Cancelled (No Charge)" <?= ($order['payment_status'] === 'Cancelled (No Charge)') ? 'selected' : '' ?>>Cancelled (No Charge)</option>
                    </select>
                </div>

                <div style="margin-bottom: 20px; font-size: 12px; background: #fffaf0; padding: 10px; border-radius: 6px; border: 1px solid #feebc8;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="restock_inventory" value="1" checked style="accent-color: var(--admin-primary);">
                        <span>Auto-replenish stock if marking as Cancelled</span>
                    </label>
                </div>

                <button type="submit" class="admin-btn admin-btn-primary" style="width: 100%; justify-content: center; padding: 10px;">
                    Update Fulfillment & Payment
                </button>
            </form>
        </div>

        <!-- Payment & Timeline Summary -->
        <div class="admin-summary-card">
            <div class="admin-summary-title">
                <span>💳 Payment Summary</span>
            </div>

            <ul class="info-list">
                <li>
                    <span class="label">Payment Method</span>
                    <span class="value"><?= htmlspecialchars($order['payment_method']) ?></span>
                </li>
                <li>
                    <span class="label">Payment Status</span>
                    <span class="value">
                        <span class="admin-badge admin-badge-info"><?= htmlspecialchars($order['payment_status']) ?></span>
                    </span>
                </li>
                <li>
                    <span class="label">Current Status</span>
                    <span class="value">
                        <?php
                        $st = strtolower($order['status']);
                        $badge_cls = match($st) {
                            'delivered' => 'admin-badge-delivered',
                            'shipped'   => 'admin-badge-shipped',
                            'confirmed' => 'admin-badge-confirmed',
                            'cancelled' => 'admin-badge-cancelled',
                            default     => 'admin-badge-pending'
                        };
                        ?>
                        <span class="admin-badge <?= $badge_cls ?>"><?= htmlspecialchars($order['status']) ?></span>
                    </span>
                </li>
                <li>
                    <span class="label">Shipping Charge</span>
                    <span class="value" style="color: #2e7d32; font-weight: 600;">FREE</span>
                </li>
                <li style="font-size: 15px; font-weight: 700; border-top: 1px solid var(--admin-border); margin-top: 8px; padding-top: 12px;">
                    <span class="label" style="color: var(--admin-text);">Total Paid</span>
                    <span class="value" style="color: var(--admin-primary);"><?= format_price($order['total_amount']) ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
