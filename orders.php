<?php
/**
 * GlowCart Cosmetics - Customer Orders History & Cancellation
 */
require_once __DIR__ . '/config/db.php';
require_login('login.php');

$user_id = (int)$_SESSION['user_id'];

// Handle Order Cancellation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $cancel_order_id = (int)($_POST['order_id'] ?? 0);
    $cancel_reason   = clean_input($_POST['cancel_reason'] ?? 'Not specified');

    if ($cancel_order_id > 0) {
        try {
            $pdo->beginTransaction();

            // Fetch order and verify ownership + cancellable status
            $is_sqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
            $lock_clause = $is_sqlite ? '' : ' FOR UPDATE';
            $chk_stmt = $pdo->prepare("SELECT id, status, total_amount, payment_method, payment_status FROM orders WHERE id = :id AND user_id = :uid{$lock_clause}");
            $chk_stmt->execute([':id' => $cancel_order_id, ':uid' => $user_id]);
            $ord = $chk_stmt->fetch();

            if (!$ord) {
                throw new Exception("Order not found or you do not have permission to cancel it.");
            }

            if (!in_array($ord['status'], ['Pending', 'Confirmed'])) {
                throw new Exception("Order #GC-" . str_pad($cancel_order_id, 5, '0', STR_PAD_LEFT) . " cannot be cancelled because it is already {$ord['status']}.");
            }

            // Fetch items to replenish product inventory/stock
            $items_stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :oid");
            $items_stmt->execute([':oid' => $cancel_order_id]);
            $items_to_restore = $items_stmt->fetchAll();

            // Restock products
            $restore_stmt = $pdo->prepare("UPDATE products SET stock = stock + :qty WHERE id = :pid");
            foreach ($items_to_restore as $it) {
                if (!empty($it['product_id'])) {
                    $restore_stmt->execute([
                        ':qty' => (int)$it['quantity'],
                        ':pid' => (int)$it['product_id']
                    ]);
                }
            }

            // Update order status to Cancelled
            $new_pay_status = ($ord['payment_method'] === 'Demo UPI') ? 'Refund Initiated (Demo)' : 'Cancelled (No Charge)';
            $upd_stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'Cancelled', payment_status = :pay_status 
                WHERE id = :id AND user_id = :uid
            ");
            $upd_stmt->execute([
                ':pay_status' => $new_pay_status,
                ':id'         => $cancel_order_id,
                ':uid'        => $user_id
            ]);

            $pdo->commit();
            $_SESSION['flash_success'] = "Order #GC-" . str_pad($cancel_order_id, 5, '0', STR_PAD_LEFT) . " has been successfully cancelled. Product stock has been replenished.";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash_error'] = $e->getMessage();
        }
    }
    header('Location: orders.php');
    exit;
}

$search = clean_input($_GET['search'] ?? '');
$status_filter = clean_input($_GET['status'] ?? '');

$where = ["user_id = :uid"];
$params = [':uid' => $user_id];

if (!empty($status_filter)) {
    $where[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $extracted_id = 0;
    if (preg_match('/(?:GC-?|#GC-?|#)?(\d+)/i', $search, $m)) {
        $extracted_id = (int)$m[1];
    }

    if ($extracted_id > 0) {
        $where[] = "(id = :order_id OR id IN (SELECT order_id FROM order_items WHERE product_name LIKE :item_q))";
        $params[':order_id'] = $extracted_id;
        $params[':item_q'] = "%{$search}%";
    } else {
        $where[] = "(status LIKE :status_q OR payment_method LIKE :pay_q OR id IN (SELECT order_id FROM order_items WHERE product_name LIKE :item_q))";
        $params[':status_q'] = "%{$search}%";
        $params[':pay_q'] = "%{$search}%";
        $params[':item_q'] = "%{$search}%";
    }
}

try {
    // Fetch orders for this customer matching filters
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE " . implode(' AND ', $where) . " ORDER BY id DESC");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // Group items by order
    $order_items = [];
    if (!empty($orders)) {
        $order_ids = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        
        $item_stmt = $pdo->prepare("
            SELECT oi.*, p.image 
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id IN ($placeholders)
        ");
        $item_stmt->execute($order_ids);
        $all_items = $item_stmt->fetchAll();

        foreach ($all_items as $item) {
            $order_items[$item['order_id']][] = $item;
        }
    }

} catch (PDOException $e) {
    die("Database query error.");
}

$page_title = 'My Orders | GlowCart Cosmetics';
$statuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container" style="padding: 40px 20px 70px;">
    <div style="max-width: 960px; margin: 0 auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 style="font-size: 28px; margin-bottom: 4px;">My Orders</h1>
                <p style="font-size: 14px; color: var(--text-muted);">Track your cosmetic purchases, shipment updates, and receipts.</p>
            </div>
            <a href="products.php" class="btn btn-outline btn-sm">🛍️ Shop More Products</a>
        </div>

        <!-- Orders Search and Filter Bar -->
        <div style="background: var(--surface); padding: 16px 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); margin-bottom: 25px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
            <form action="orders.php" method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; flex: 1; align-items: center;">
                <div style="position: relative; flex: 1; min-width: 220px; max-width: 380px;">
                    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; color: var(--text-muted);">🔍</span>
                    <input type="text" name="search" class="form-control" style="padding-left: 34px; padding-right: 30px; height: 38px; font-size: 13px;" placeholder="Search Order ID (#GC-00001), item name..." value="<?= htmlspecialchars($search) ?>">
                    <?php if (!empty($search)): ?>
                        <a href="orders.php<?= !empty($status_filter) ? '?status=' . urlencode($status_filter) : '' ?>" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 12px; color: var(--text-muted); text-decoration: none;" title="Clear search">✕</a>
                    <?php endif; ?>
                </div>

                <select name="status" class="form-control" style="max-width: 170px; height: 38px; font-size: 13px;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($status_filter === $s) ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary btn-sm" style="height: 38px; padding: 0 16px;">Search</button>
                <?php if (!empty($search) || !empty($status_filter)): ?>
                    <a href="orders.php" class="btn btn-outline btn-sm" style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center;">Reset</a>
                <?php endif; ?>
            </form>

            <div style="font-size: 13px; color: var(--text-muted);">
                Showing <strong><?= count($orders) ?></strong> <?= count($orders) === 1 ? 'order' : 'orders' ?>
            </div>
        </div>

        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <?php 
                    $items = $order_items[$order['id']] ?? []; 
                    $is_cancellable = in_array($order['status'], ['Pending', 'Confirmed']);
                ?>
                <div style="background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); margin-bottom: 25px; overflow: hidden;">
                    
                    <!-- Order Header -->
                    <div style="background: var(--surface-alt); padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Order Placed</span>
                            <div style="font-size: 14px; font-weight: 500;"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
                        </div>

                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Total Amount</span>
                            <div style="font-size: 16px; font-weight: 700; color: var(--primary);"><?= format_price($order['total_amount']) ?></div>
                        </div>

                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Payment</span>
                            <div style="font-size: 13px;"><?= htmlspecialchars($order['payment_method']) ?> (<?= htmlspecialchars($order['payment_status']) ?>)</div>
                        </div>

                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Order ID</span>
                            <div style="font-size: 14px; font-weight: 600;"><?= highlight_text('#GC-' . str_pad($order['id'], 5, '0', STR_PAD_LEFT), $search) ?></div>
                        </div>

                        <div>
                            <span class="status-pill status-<?= htmlspecialchars($order['status']) ?>">
                                ● <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Order Items Breakdown -->
                    <div style="padding: 20px 24px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 12px 0; width: 60px;">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; background: var(--surface-alt); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 20px;">💄</div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px 15px;">
                                            <strong style="display: block;"><?= highlight_text($item['product_name'], $search) ?></strong>
                                            <span style="font-size: 12px; color: var(--text-muted);">Quantity: <?= $item['quantity'] ?> &bull; Unit Price: <?= format_price($item['price']) ?></span>
                                        </td>
                                        <td style="padding: 12px 0; text-align: right; font-weight: 600; color: var(--text-main);">
                                            <?= format_price($item['subtotal']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Delivery Address Info & Order Actions Footer -->
                        <div style="margin-top: 15px; padding-top: 14px; border-top: 1px dashed var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                            <div style="font-size: 13px; color: var(--text-muted); flex: 1; min-width: 250px;">
                                <strong>Shipping To:</strong> <?= htmlspecialchars($order['customer_name']) ?> &bull; <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> - <?= htmlspecialchars($order['pincode']) ?> &bull; 📱 <?= htmlspecialchars($order['phone']) ?>
                            </div>

                            <!-- Order Action Buttons -->
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <?php if ($is_cancellable): ?>
                                    <button type="button" 
                                            class="btn-danger-outline btn-cancel-order-trigger" 
                                            data-order-id="<?= $order['id'] ?>" 
                                            data-order-code="#GC-<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?>">
                                        ✕ Cancel Order
                                    </button>
                                <?php elseif ($order['status'] === 'Cancelled'): ?>
                                    <span style="font-size: 12px; color: var(--danger); font-weight: 600; background: var(--danger-bg); padding: 5px 12px; border-radius: var(--radius-full);">
                                        ✕ Order Cancelled
                                    </span>
                                <?php elseif ($order['status'] === 'Shipped'): ?>
                                    <span style="font-size: 12px; color: #512da8; font-weight: 600; background: #ede7f6; padding: 5px 12px; border-radius: var(--radius-full);">
                                        🚚 Shipped (In Transit)
                                    </span>
                                <?php elseif ($order['status'] === 'Delivered'): ?>
                                    <span style="font-size: 12px; color: var(--success); font-weight: 600; background: var(--success-bg); padding: 5px 12px; border-radius: var(--radius-full);">
                                        ✓ Delivered
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div style="background: var(--surface); padding: 60px 20px; text-align: center; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <div style="font-size: 50px; margin-bottom: 15px;">📦</div>
                <h2 style="font-size: 22px; margin-bottom: 8px;">No Orders Found</h2>
                <p style="margin-bottom: 25px; color: var(--text-muted);">
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                        No orders match your search criteria. Try searching for another Order ID or resetting your filters.
                    <?php else: ?>
                        You haven't placed any cosmetics orders yet. Start exploring our beauty catalog today!
                    <?php endif; ?>
                </p>
                <?php if (!empty($search) || !empty($status_filter)): ?>
                    <a href="orders.php" class="btn btn-primary">Reset Filters</a>
                <?php else: ?>
                    <a href="products.php" class="btn btn-primary btn-lg">Shop Beauty Products</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Interactive Cancel Order Confirmation Modal -->
<div id="cancelOrderModal" class="cancel-modal-overlay">
    <div class="cancel-modal-box">
        <div class="cancel-modal-header">
            <h3><span>⚠️</span> Cancel Order</h3>
            <button type="button" id="closeCancelModalBtn" class="modal-close-btn" style="color: var(--danger);">&times;</button>
        </div>
        <form action="orders.php" method="POST" id="cancelOrderForm">
            <input type="hidden" name="action" value="cancel_order">
            <input type="hidden" name="order_id" id="cancelOrderIdInput" value="">
            
            <div class="cancel-modal-body">
                <p style="font-size: 15px; margin-bottom: 12px; color: var(--text-main);">
                    Are you sure you want to cancel order <strong id="cancelOrderCodeDisplay" style="color: var(--primary);">#GC-00000</strong>?
                </p>
                
                <div style="background: #fff8e1; border: 1px solid #ffe082; padding: 12px; border-radius: var(--radius-sm); font-size: 13px; color: #795548; margin-bottom: 18px;">
                    ℹ️ <strong>Cancellation Policy:</strong> Once cancelled, all ordered cosmetic items will be immediately returned to catalog inventory, and any demo digital payments will be marked for refund.
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="cancelReasonSelect" class="form-label" style="font-size: 13px; font-weight: 600;">Reason for Cancellation *</label>
                    <select id="cancelReasonSelect" name="cancel_reason" class="form-control" style="font-size: 13px;" required>
                        <option value="Ordered by mistake / wrong item">Ordered by mistake / wrong item or shade</option>
                        <option value="Need to change shipping address">Need to change shipping address</option>
                        <option value="Found better price or product">Found better price or alternative</option>
                        <option value="Expected faster delivery">Expected faster delivery time</option>
                        <option value="Changed my mind">Changed my mind / test order</option>
                        <option value="Other reason">Other reason</option>
                    </select>
                </div>
            </div>

            <div class="cancel-modal-footer">
                <button type="button" id="dismissCancelModalBtn" class="btn btn-outline btn-sm">Keep My Order</button>
                <button type="submit" class="btn btn-danger btn-sm">✕ Confirm Cancellation</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

