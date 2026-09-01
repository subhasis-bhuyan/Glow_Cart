<?php
/**
 * GlowCart Cosmetics - Admin Orders Management
 */
require_once __DIR__ . '/auth_check.php';

$admin_page_title = 'Manage Orders | GlowCart Admin';
$admin_header_title = 'Order Processing';
$active_tab = 'orders';

// Search and filter parameters
$search = clean_input($_GET['search'] ?? '');
$status_filter = clean_input($_GET['status'] ?? '');
$payment_filter = clean_input($_GET['payment'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $extracted_id = 0;
    if (preg_match('/(?:GC-?|#GC-?|#)?(\d+)/i', $search, $m)) {
        $extracted_id = (int)$m[1];
    }

    if ($extracted_id > 0) {
        $where[] = "(id = :order_id OR customer_name LIKE :search_name OR email LIKE :search_email OR phone LIKE :search_phone)";
        $params[':order_id'] = $extracted_id;
        $params[':search_name'] = "%{$search}%";
        $params[':search_email'] = "%{$search}%";
        $params[':search_phone'] = "%{$search}%";
    } else {
        $where[] = "(customer_name LIKE :search_name OR email LIKE :search_email OR phone LIKE :search_phone OR payment_method LIKE :search_pay)";
        $params[':search_name'] = "%{$search}%";
        $params[':search_email'] = "%{$search}%";
        $params[':search_phone'] = "%{$search}%";
        $params[':search_pay'] = "%{$search}%";
    }
}

if (!empty($status_filter)) {
    $where[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($payment_filter)) {
    $where[] = "payment_status LIKE :pay_status";
    $params[':pay_status'] = "%{$payment_filter}%";
}

$where_clause = implode(" AND ", $where);

try {
    // Quick Counts by Status
    $cnt_pending = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
    $cnt_confirmed = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Confirmed'")->fetchColumn();
    $cnt_shipped = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Shipped'")->fetchColumn();
    $cnt_delivered = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Delivered'")->fetchColumn();
    $cnt_cancelled = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Cancelled'")->fetchColumn();

    // Fetch Orders with items count
    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count,
               (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) AS total_qty
        FROM orders o
        WHERE {$where_clause}
        ORDER BY o.id DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    $total_orders_found = count($orders);

} catch (PDOException $e) {
    $orders = [];
    $total_orders_found = 0;
    $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Customer Orders (<?= $total_orders_found ?>)</h1>
        <p style="color: var(--admin-muted); font-size: 13px; margin-top: 4px;">Track shipments, update delivery progress, and print invoices.</p>
    </div>
</div>

<!-- Order Status Quick Filter Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 22px;">
    <a href="orders.php" class="metric-card" style="padding: 12px 16px; border-left: 4px solid #718096; <?= empty($status_filter) ? 'background: #edf2f7;' : '' ?>">
        <div>
            <div style="font-size: 11px; color: var(--admin-muted); font-weight: 600;">ALL ORDERS</div>
            <div style="font-size: 18px; font-weight: 700;"><?= $cnt_pending + $cnt_confirmed + $cnt_shipped + $cnt_delivered + $cnt_cancelled ?></div>
        </div>
    </a>
    <a href="orders.php?status=Pending" class="metric-card" style="padding: 12px 16px; border-left: 4px solid #dd6b20; <?= ($status_filter === 'Pending') ? 'background: #fffaf0;' : '' ?>">
        <div>
            <div style="font-size: 11px; color: #dd6b20; font-weight: 600;">PENDING</div>
            <div style="font-size: 18px; font-weight: 700; color: #dd6b20;"><?= $cnt_pending ?></div>
        </div>
    </a>
    <a href="orders.php?status=Confirmed" class="metric-card" style="padding: 12px 16px; border-left: 4px solid #3182ce; <?= ($status_filter === 'Confirmed') ? 'background: #ebf8ff;' : '' ?>">
        <div>
            <div style="font-size: 11px; color: #3182ce; font-weight: 600;">CONFIRMED</div>
            <div style="font-size: 18px; font-weight: 700; color: #3182ce;"><?= $cnt_confirmed ?></div>
        </div>
    </a>
    <a href="orders.php?status=Shipped" class="metric-card" style="padding: 12px 16px; border-left: 4px solid #805ad5; <?= ($status_filter === 'Shipped') ? 'background: #faf5ff;' : '' ?>">
        <div>
            <div style="font-size: 11px; color: #805ad5; font-weight: 600;">SHIPPED</div>
            <div style="font-size: 18px; font-weight: 700; color: #805ad5;"><?= $cnt_shipped ?></div>
        </div>
    </a>
    <a href="orders.php?status=Delivered" class="metric-card" style="padding: 12px 16px; border-left: 4px solid #38a169; <?= ($status_filter === 'Delivered') ? 'background: #f0fff4;' : '' ?>">
        <div>
            <div style="font-size: 11px; color: #38a169; font-weight: 600;">DELIVERED</div>
            <div style="font-size: 18px; font-weight: 700; color: #38a169;"><?= $cnt_delivered ?></div>
        </div>
    </a>
</div>

<!-- Search & Filters Toolbar -->
<div class="admin-toolbar">
    <form action="orders.php" method="GET" class="admin-toolbar-group" style="flex: 1;">
        <div class="admin-search-wrapper">
            <span class="admin-search-icon">🔍</span>
            <input type="text" name="search" placeholder="Order #GC-..., name, email, phone..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <select name="status" class="admin-select" onchange="this.form.submit()">
            <option value="">All Order Statuses</option>
            <option value="Pending" <?= ($status_filter === 'Pending') ? 'selected' : '' ?>>Pending</option>
            <option value="Confirmed" <?= ($status_filter === 'Confirmed') ? 'selected' : '' ?>>Confirmed</option>
            <option value="Shipped" <?= ($status_filter === 'Shipped') ? 'selected' : '' ?>>Shipped</option>
            <option value="Delivered" <?= ($status_filter === 'Delivered') ? 'selected' : '' ?>>Delivered</option>
            <option value="Cancelled" <?= ($status_filter === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
        </select>

        <select name="payment" class="admin-select" onchange="this.form.submit()">
            <option value="">All Payments</option>
            <option value="Pending" <?= ($payment_filter === 'Pending') ? 'selected' : '' ?>>Payment Pending</option>
            <option value="Paid" <?= ($payment_filter === 'Paid') ? 'selected' : '' ?>>Payment Paid / Completed</option>
            <option value="Refund" <?= ($payment_filter === 'Refund') ? 'selected' : '' ?>>Refunds</option>
        </select>

        <button type="submit" class="admin-btn admin-btn-outline">Search</button>

        <?php if (!empty($search) || !empty($status_filter) || !empty($payment_filter)): ?>
            <a href="orders.php" class="admin-btn admin-btn-outline" style="color: var(--admin-danger);">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Orders Table -->
<div class="card-table">
    <?php if (!empty($orders)): ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order Ref</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--admin-primary);">
                                #GC-<?= str_pad($ord['id'], 5, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td style="font-size: 12px; color: var(--admin-muted);">
                                <?= date('M d, Y', strtotime($ord['created_at'])) ?><br>
                                <span style="font-size: 11px;"><?= date('h:i A', strtotime($ord['created_at'])) ?></span>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--admin-text);">
                                    <?= htmlspecialchars($ord['customer_name']) ?>
                                </div>
                                <div style="font-size: 11px; color: var(--admin-muted);">
                                    <?= htmlspecialchars($ord['email']) ?> &bull; <?= htmlspecialchars($ord['phone']) ?>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 13px; font-weight: 500;">
                                    <?= (int)$ord['total_qty'] ?> pcs
                                </span>
                                <div style="font-size: 11px; color: var(--admin-muted);">
                                    (<?= (int)$ord['item_count'] ?> products)
                                </div>
                            </td>
                            <td style="font-weight: 700; font-size: 14px;">
                                <?= format_price($ord['total_amount']) ?>
                            </td>
                            <td>
                                <div style="font-size: 12px; font-weight: 500;"><?= htmlspecialchars($ord['payment_method']) ?></div>
                                <div style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($ord['payment_status']) ?></div>
                            </td>
                            <td>
                                <?php
                                $st = strtolower($ord['status']);
                                $badge_cls = match($st) {
                                    'delivered' => 'admin-badge-delivered',
                                    'shipped'   => 'admin-badge-shipped',
                                    'confirmed' => 'admin-badge-confirmed',
                                    'cancelled' => 'admin-badge-cancelled',
                                    default     => 'admin-badge-pending'
                                };
                                ?>
                                <span class="admin-badge <?= $badge_cls ?>">
                                    <?= htmlspecialchars($ord['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="order_details.php?id=<?= $ord['id'] ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty-state">
            <span class="icon">📦</span>
            <h3>No orders found</h3>
            <p>No orders match the current criteria.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
