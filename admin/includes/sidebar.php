<?php
/**
 * GlowCart Cosmetics - Admin Sidebar Navigation
 */
$admin_current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="admin-brand">
        💄 GLOWCART <span>ADMIN</span>
    </div>

    <ul class="admin-nav">
        <li class="admin-nav-item">
            <a href="index.php" class="admin-nav-link <?= ($admin_current_page === 'index.php') ? 'active' : '' ?>">
                <span class="icon">📊</span> Dashboard
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="products.php" class="admin-nav-link <?= ($admin_current_page === 'products.php') ? 'active' : '' ?>">
                <span class="icon">💄</span> Manage Products
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="add_product.php" class="admin-nav-link <?= ($admin_current_page === 'add_product.php') ? 'active' : '' ?>">
                <span class="icon">➕</span> Add New Product
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="orders.php" class="admin-nav-link <?= ($admin_current_page === 'orders.php') ? 'active' : '' ?>">
                <span class="icon">📦</span> Manage Orders
            </a>
        </li>

        <li style="margin: 20px 0; border-top: 1px solid rgba(255,255,255,0.08);"></li>

        <li class="admin-nav-item">
            <a href="../index.php" target="_blank" class="admin-nav-link">
                <span class="icon">🌐</span> View Live Store &nearr;
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="logout.php" class="admin-nav-link" style="color: #ff8a80;">
                <span class="icon">🚪</span> Logout
            </a>
        </li>
    </ul>
</aside>

<div class="admin-main">
    <!-- Admin Topbar -->
    <header class="admin-topbar">
        <div style="font-weight: 500; font-size: 15px; color: var(--admin-text);">
            GlowCart E-Commerce Administration Console
        </div>

        <div class="admin-topbar-user">
            <span style="font-size: 13px; color: var(--admin-muted);">
                Logged in as <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Administrator') ?></strong>
            </span>
            <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--admin-primary); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                A
            </div>
        </div>
    </header>

    <div class="admin-content">
        <?php if (isset($_SESSION['admin_flash_success'])): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(46,125,50,0.2);">
                ✓ <?= htmlspecialchars($_SESSION['admin_flash_success']) ?>
            </div>
            <?php unset($_SESSION['admin_flash_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['admin_flash_error'])): ?>
            <div style="background: #ffebee; color: #c62828; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(198,40,40,0.2);">
                ⚠️ <?= htmlspecialchars($_SESSION['admin_flash_error']) ?>
            </div>
            <?php unset($_SESSION['admin_flash_error']); ?>
        <?php endif; ?>
