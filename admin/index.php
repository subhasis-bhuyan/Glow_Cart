<?php
/**
 * GlowCart Cosmetics - Admin Dashboard
 */
require_once __DIR__ . '/../config/db.php';
require_admin('login.php');

$admin_page_title = 'Dashboard Overview | GlowCart Admin';

try {
    // 1. Total Products
    $total_products = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

    // 2. Total Registered Customers
    $total_customers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // 3. Total Orders
    $total_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    // 4. Total Sales Revenue
    $total_sales = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'Cancelled'")->fetchColumn();

    // 5. Pending Orders
    $pending_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();

    // 6. Out of Stock Products
    $out_of_stock = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 0")->fetchColumn();

    // Recent 5 Orders
    $recent_orders_stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5");
    $recent_orders = $recent_orders_stmt->fetchAll();

    // Low Stock Alert Products (stock <= 5)
    $low_stock_stmt = $pdo->query("SELECT id, name, category, stock, price, image FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 5");
    $low_stock_products = $low_stock_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard Overview</h1>
        <p style="color: var(--admin-muted); font-size: 13px;">Key metrics and real-time operations performance.</p>
    </div>

    <div style="display: flex; gap: 10px;">
        <a href="add_product.php" class="admin-btn admin-btn-primary">➕ Add Product</a>
        <a href="orders.php" class="admin-btn admin-btn-outline">📦 View Orders</a>
    </div>
</div>

<!-- Metrics Grid -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-icon icon-sales">💰</div>
        <div class="metric-details">
            <h4>Total Sales</h4>
            <div class="number"><?= format_price($total_sales) ?></div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon icon-orders">📦</div>
        <div class="metric-details">
            <h4>Total Orders</h4>
            <div class="number"><?= $total_orders ?></div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon icon-pending">⏳</div>
        <div class="metric-details">
            <h4>Pending Orders</h4>
            <div class="number" style="color: var(--admin-warning);"><?= $pending_orders ?></div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon icon-products">💄</div>
        <div class="metric-details">
            <h4>Total Products</h4>
            <div class="number"><?= $total_products ?></div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon icon-users">👥</div>
        <div class="metric-details">
            <h4>Total Customers</h4>
            <div class="number"><?= $total_customers ?></div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon icon-stock">⚠️</div>
        <div class="metric-details">
            <h4>Out of Stock</h4>
            <div class="number" style="color: var(--admin-danger);"><?= $out_of_stock ?></div>
        </div>
    </div>
</div>

<!-- Recent Orders Section -->
<div class="card-table">
    <div class="card-table-header">
        <h3 style="font-size: 16px; font-weight: 600;">Recent Customer Orders</h3>
        <a href="orders.php" style="color: var(--admin-primary); font-size: 13px; font-weight: 500;">View All Orders &rarr;</a>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($recent_orders)): ?>
                <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><strong>#GC-<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                        <td>
                            <strong><?= htmlspecialchars($order['customer_name']) ?></strong>
                            <div style="font-size: 12px; color: var(--admin-muted);"><?= htmlspecialchars($order['phone']) ?></div>
                        </td>
                        <td><strong><?= format_price($order['total_amount']) ?></strong></td>
                        <td><?= htmlspecialchars($order['payment_method']) ?></td>
                        <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                        <td>
                            <span class="status-pill status-<?= htmlspecialchars($order['status']) ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="orders.php?highlight=<?= $order['id'] ?>" class="admin-btn admin-btn-outline admin-btn-sm">
                                View / Update
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: var(--admin-muted);">
                        No orders placed yet.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Low Stock Alerts -->
<?php if (!empty($low_stock_products)): ?>
    <div class="card-table">
        <div class="card-table-header" style="background: #fff8e1;">
            <h3 style="font-size: 15px; font-weight: 600; color: #f57f17;">⚠️ Low / Out of Stock Inventory Alert</h3>
            <a href="products.php" style="color: #f57f17; font-size: 13px; font-weight: 500;">Manage Inventory &rarr;</a>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock Remaining</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_stock_products as $lp): ?>
                    <tr>
                        <td style="display: flex; align-items: center; gap: 12px;">
                            <img src="<?= htmlspecialchars($lp['image']) ?>" alt="" class="admin-product-thumb">
                            <strong><?= htmlspecialchars($lp['name']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($lp['category']) ?></td>
                        <td><?= format_price($lp['price']) ?></td>
                        <td>
                            <span class="admin-badge <?= $lp['stock'] > 0 ? 'admin-badge-active' : 'admin-badge-inactive' ?>" style="<?= $lp['stock'] <= 0 ? 'background:#ffebee; color:#c62828;' : 'background:#fff3e0; color:#ef6c00;' ?>">
                                <?= $lp['stock'] ?> units left
                            </span>
                        </td>
                        <td>
                            <a href="edit_product.php?id=<?= $lp['id'] ?>" class="admin-btn admin-btn-primary admin-btn-sm">
                                ✏️ Restock Now
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
