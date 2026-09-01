<?php
/**
 * GlowCart Cosmetics - Registered Customer Management
 */
require_once __DIR__ . '/auth_check.php';

$admin_page_title = 'Customers | GlowCart Admin';
$admin_header_title = 'Customer Accounts';
$active_tab = 'customers';

$search = clean_input($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(u.name LIKE :search_name OR u.email LIKE :search_email OR u.phone LIKE :search_phone OR u.city LIKE :search_city)";
    $params[':search_name'] = "%{$search}%";
    $params[':search_email'] = "%{$search}%";
    $params[':search_phone'] = "%{$search}%";
    $params[':search_city'] = "%{$search}%";
}

$where_clause = implode(" AND ", $where);

try {
    // Fetch customers with order statistics
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.phone, u.city, u.state, u.pincode, u.created_at,
               COUNT(o.id) AS total_orders,
               COALESCE(SUM(CASE WHEN o.status != 'Cancelled' THEN o.total_amount ELSE 0 END), 0) AS total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE {$where_clause}
        GROUP BY u.id
        ORDER BY u.id DESC
    ");
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
    $total_customers_found = count($customers);

    // Aggregate stats
    $total_users_count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $paying_customers_count = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE status != 'Cancelled'")->fetchColumn();

} catch (PDOException $e) {
    $customers = [];
    $total_customers_found = 0;
    $total_users_count = 0;
    $paying_customers_count = 0;
    $_SESSION['flash_error'] = "Database query error: " . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Registered Customers (<?= $total_customers_found ?>)</h1>
        <p style="color: var(--admin-muted); font-size: 13px; margin-top: 4px;">View customer purchase history, shipping locations, and contact records.</p>
    </div>
</div>

<!-- Customer Stats -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 22px;">
    <div class="metric-card">
        <div class="metric-icon icon-users">👥</div>
        <div class="metric-details">
            <h4>Total Registered</h4>
            <div class="number"><?= number_format($total_users_count) ?></div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon icon-sales">🛍️</div>
        <div class="metric-details">
            <h4>Purchasing Customers</h4>
            <div class="number"><?= number_format($paying_customers_count) ?></div>
        </div>
    </div>
</div>

<!-- Search Toolbar -->
<div class="admin-toolbar">
    <form action="customers.php" method="GET" class="admin-toolbar-group" style="flex: 1;">
        <div class="admin-search-wrapper">
            <span class="admin-search-icon">🔍</span>
            <input type="text" name="search" placeholder="Search customer by name, email, phone, city..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="admin-btn admin-btn-outline">Search</button>
        <?php if (!empty($search)): ?>
            <a href="customers.php" class="admin-btn admin-btn-outline" style="color: var(--admin-danger);">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Customers Table -->
<div class="card-table">
    <?php if (!empty($customers)): ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Avatar</th>
                        <th>Customer Name</th>
                        <th>Contact Information</th>
                        <th>Location</th>
                        <th>Total Orders</th>
                        <th>Total Spent</th>
                        <th>Registered Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $cust): ?>
                        <tr>
                            <td>
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: #fce4ec; color: #ad1457; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px;">
                                    <?= strtoupper(substr($cust['name'], 0, 1)) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--admin-text);">
                                    <?= htmlspecialchars($cust['name']) ?>
                                </div>
                                <div style="font-size: 11px; color: var(--admin-muted);">User ID: #<?= (int)$cust['id'] ?></div>
                            </td>
                            <td>
                                <div style="font-size: 13px;">📧 <?= htmlspecialchars($cust['email']) ?></div>
                                <div style="font-size: 12px; color: var(--admin-muted); margin-top: 2px;">📞 <?= htmlspecialchars($cust['phone']) ?></div>
                            </td>
                            <td>
                                <div style="font-size: 13px;"><?= htmlspecialchars($cust['city'] ?: 'Not specified') ?></div>
                                <div style="font-size: 11px; color: var(--admin-muted);">
                                    <?= htmlspecialchars($cust['state'] ?: '') ?> <?= !empty($cust['pincode']) ? "({$cust['pincode']})" : '' ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($cust['total_orders'] > 0): ?>
                                    <a href="orders.php?search=<?= urlencode($cust['email']) ?>" class="admin-badge admin-badge-confirmed" style="font-size: 12px;">
                                        <?= (int)$cust['total_orders'] ?> orders &rarr;
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: var(--admin-muted);">0 orders</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 700; font-size: 14px; color: var(--admin-text);">
                                <?= format_price($cust['total_spent']) ?>
                            </td>
                            <td style="font-size: 12px; color: var(--admin-muted);">
                                <?= date('M d, Y', strtotime($cust['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty-state">
            <span class="icon">👥</span>
            <h3>No customers found</h3>
            <p>No customer accounts matched your search.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
