<?php
/**
 * GlowCart Cosmetics - Admin Sidebar Navigation Component
 */
if (!isset($active_tab)) {
    $active_tab = 'dashboard';
}

// Fetch notification badges (pending orders & low stock)
$badge_pending_orders = 0;
$badge_low_stock = 0;

if (isset($pdo) && $pdo) {
    try {
        $badge_pending_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
        $badge_low_stock = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND status = 'Active'")->fetchColumn();
    } catch (PDOException $e) {}
}
?>
<!-- Sidebar Overlay for Mobile -->
<div class="admin-sidebar-overlay" id="adminSidebarOverlay"></div>

<!-- Admin Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-brand">
        💄 GLOWCART <span>ADMIN</span>
    </div>

    <ul class="admin-nav">
        <li class="admin-nav-item">
            <a href="index.php" class="admin-nav-link <?= ($active_tab === 'dashboard') ? 'active' : '' ?>">
                <span class="icon">📊</span>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="products.php" class="admin-nav-link <?= ($active_tab === 'products') ? 'active' : '' ?>">
                <span class="icon">🛍️</span>
                <span style="flex: 1;">Products</span>
                <?php if ($badge_low_stock > 0): ?>
                    <span class="admin-badge admin-badge-warning" style="font-size: 10px; padding: 2px 6px;" title="<?= $badge_low_stock ?> Low Stock"><?= $badge_low_stock ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="product_add.php" class="admin-nav-link <?= ($active_tab === 'product_add') ? 'active' : '' ?>">
                <span class="icon">➕</span>
                <span>Add Product</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="orders.php" class="admin-nav-link <?= ($active_tab === 'orders') ? 'active' : '' ?>">
                <span class="icon">📦</span>
                <span style="flex: 1;">Orders</span>
                <?php if ($badge_pending_orders > 0): ?>
                    <span class="admin-badge admin-badge-pending" style="font-size: 10px; padding: 2px 6px;" title="<?= $badge_pending_orders ?> Pending Orders"><?= $badge_pending_orders ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="customers.php" class="admin-nav-link <?= ($active_tab === 'customers') ? 'active' : '' ?>">
                <span class="icon">👥</span>
                <span>Customers</span>
            </a>
        </li>

        <li class="admin-nav-item">
            <a href="settings.php" class="admin-nav-link <?= ($active_tab === 'settings') ? 'active' : '' ?>">
                <span class="icon">⚙️</span>
                <span>Settings</span>
            </a>
        </li>
    </ul>

    <!-- Bottom Actions in Sidebar -->
    <div style="padding: 20px; border-top: 1px solid rgba(255,255,255,0.08);">
        <a href="../index.php" target="_blank" class="admin-btn admin-btn-outline" style="width: 100%; justify-content: center; margin-bottom: 10px; color: #a2a3b7; border-color: rgba(255,255,255,0.15); background: transparent;">
            <span>🏪</span> View Store ↗
        </a>
        <a href="logout.php" class="admin-btn admin-btn-danger" style="width: 100%; justify-content: center;">
            <span>🚪</span> Logout
        </a>
    </div>
</aside>
