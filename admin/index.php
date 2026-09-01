<?php
/**
 * GlowCart Cosmetics - Administrator Dashboard
 */
require_once __DIR__ . '/auth_check.php';

$admin_page_title = 'Admin Dashboard | GlowCart Cosmetics';
$admin_header_title = 'Dashboard Overview';
$active_tab = 'dashboard';

// Fetch Dashboard Metrics
try {
    // 1. Total Revenue (excluding cancelled orders)
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'Cancelled'");
    $total_revenue = (float)$stmt->fetchColumn();

    // 2. Total Orders
    $total_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    // 3. Orders by Status
    $pending_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
    $delivered_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Delivered'")->fetchColumn();

    // 4. Products Count
    $total_products = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $active_products = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'Active'")->fetchColumn();

    // 5. Low Stock / Out of Stock
    $low_stock_count = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND status = 'Active'")->fetchColumn();
    $out_of_stock_count = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0 AND status = 'Active'")->fetchColumn();

    // 6. Registered Customers
    $total_customers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // 7. Recent Orders (Latest 6)
    $recent_orders = $pdo->query("
        SELECT id, customer_name, email, total_amount, payment_method, payment_status, status, created_at 
        FROM orders 
        ORDER BY id DESC 
        LIMIT 6
    ")->fetchAll();

    // 8. Critical Low Stock Products (Stock <= 5)
    $low_stock_items = $pdo->query("
        SELECT id, name, category, price, stock, image, status 
        FROM products 
        WHERE stock <= 5 AND status = 'Active' 
        ORDER BY stock ASC 
        LIMIT 6
    ")->fetchAll();

} catch (PDOException $e) {
    $error_msg = "Error loading metrics: " . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<!-- Header with Quick Action Buttons -->
<div class="page-header">
    <div>
        <h1 class="page-title">Welcome to GlowCart Admin</h1>
        <p style="color: var(--admin-muted); font-size: 13px; margin-top: 4px;">Real-time overview of catalog sales, pending orders, and inventory health.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="product_add.php" class="admin-btn admin-btn-primary">
            <span>➕</span> Add Product
        </a>
        <a href="orders.php" class="admin-btn admin-btn-outline">
            <span>📦</span> View Orders
        </a>
    </div>
</div>

<!-- Primary Metric KPI Cards -->
<div class="metrics-grid">
    <!-- Total Revenue -->
    <div class="metric-card">
        <div class="metric-icon icon-sales">💰</div>
        <div class="metric-details">
            <h4>Total Revenue</h4>
            <div class="number"><?= format_price($total_revenue) ?></div>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="metric-card">
        <div class="metric-icon icon-orders">📦</div>
        <div class="metric-details">
            <h4>Total Orders</h4>
            <div class="number"><?= number_format($total_orders) ?></div>
        </div>
    </div>

    <!-- Active Products -->
    <div class="metric-card">
        <div class="metric-icon icon-products">💄</div>
        <div class="metric-details">
            <h4>Active Catalog</h4>
            <div class="number"><?= number_format($active_products) ?> <span style="font-size: 12px; font-weight: normal; color: var(--admin-muted);">/ <?= $total_products ?> items</span></div>
        </div>
    </div>

    <!-- Registered Customers -->
    <div class="metric-card">
        <div class="metric-icon icon-users">👥</div>
        <div class="metric-details">
            <h4>Registered Users</h4>
            <div class="number"><?= number_format($total_customers) ?></div>
        </div>
    </div>

    <!-- Pending Orders Alert -->
    <div class="metric-card">
        <div class="metric-icon icon-pending">⏳</div>
        <div class="metric-details">
            <h4>Pending Orders</h4>
            <div class="number" style="<?= ($pending_orders > 0) ? 'color: #d84315;' : '' ?>">
                <?= number_format($pending_orders) ?>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="metric-card">
        <div class="metric-icon icon-stock">⚠️</div>
        <div class="metric-details">
            <h4>Low Stock Items</h4>
            <div class="number" style="<?= ($low_stock_count > 0) ? 'color: #c62828;' : '' ?>">
                <?= number_format($low_stock_count) ?>
            </div>
        </div>
    </div>
</div>

<div class="order-details-grid" style="grid-template-columns: 2fr 1.2fr;">
    <!-- Recent Orders Card Table -->
    <div class="card-table">
        <div class="card-table-header">
            <h3 style="font-size: 15px; font-weight: 600;">Recent Orders</h3>
            <a href="orders.php" style="color: var(--admin-primary); font-size: 13px; font-weight: 500;">View All &rarr;</a>
        </div>

        <?php if (!empty($recent_orders)): ?>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $ord): ?>
                            <tr>
                                <td style="font-weight: 600;">
                                    #GC-<?= str_pad($ord['id'], 5, '0', STR_PAD_LEFT) ?>
                                </td>
                                <td>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($ord['customer_name']) ?></div>
                                    <div style="font-size: 11px; color: var(--admin-muted);"><?= date('M d, Y h:i A', strtotime($ord['created_at'])) ?></div>
                                </td>
                                <td style="font-weight: 600; color: var(--admin-text);">
                                    <?= format_price($ord['total_amount']) ?>
                                </td>
                                <td>
                                    <span style="font-size: 12px; color: var(--admin-muted);"><?= htmlspecialchars($ord['payment_method']) ?></span>
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
                                <td>
                                    <a href="order_details.php?id=<?= $ord['id'] ?>" class="admin-btn admin-btn-outline admin-btn-sm">
                                        Manage
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
                <p>No orders placed yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Inventory Low Stock Alert Box -->
    <div class="card-table">
        <div class="card-table-header">
            <h3 style="font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <span>⚠️</span> Low Stock Alerts (≤ 5)
            </h3>
            <a href="products.php?stock_status=low" style="color: var(--admin-primary); font-size: 13px; font-weight: 500;">Manage &rarr;</a>
        </div>

        <?php if (!empty($low_stock_items)): ?>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Stock</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock_items as $item): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="" class="admin-product-thumb" onerror="this.src='https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=100';">
                                        <div>
                                            <div style="font-weight: 500; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?= htmlspecialchars($item['name']) ?>
                                            </div>
                                            <div style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($item['category']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($item['stock'] == 0): ?>
                                        <span class="admin-badge admin-badge-cancelled">Out of Stock</span>
                                    <?php else: ?>
                                        <span class="admin-badge admin-badge-warning"><?= (int)$item['stock'] ?> units</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="product_edit.php?id=<?= $item['id'] ?>" class="admin-btn admin-btn-outline admin-btn-sm" title="Restock product">
                                        Restock
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="admin-empty-state" style="padding: 30px;">
                <span class="icon">✨</span>
                <p>All catalog items have healthy inventory levels!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
