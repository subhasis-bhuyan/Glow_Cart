<?php
/**
 * GlowCart Cosmetics - Admin Topbar Component
 */
$admin_name = $_SESSION['admin_username'] ?? 'Administrator';
$admin_email = $_SESSION['admin_email'] ?? 'admin@glowcart.com';
?>
<main class="admin-main">
    <!-- Topbar -->
    <header class="admin-topbar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <button type="button" class="admin-mobile-toggle" id="adminSidebarToggleBtn" aria-label="Toggle navigation menu">
                ☰
            </button>
            <div style="font-weight: 600; font-size: 15px; color: var(--admin-text);">
                <?= htmlspecialchars($admin_header_title ?? 'Control Center') ?>
            </div>
        </div>

        <div class="admin-topbar-user">
            <a href="../index.php" target="_blank" class="admin-btn admin-btn-outline admin-btn-sm" style="display: none; @media(min-width: 600px){display: inline-flex;}">
                <span>↗</span> Storefront
            </a>
            
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--admin-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                    <?= strtoupper(substr($admin_name, 0, 1)) ?>
                </div>
                <div style="line-height: 1.2;">
                    <div style="font-weight: 600; font-size: 13px;"><?= htmlspecialchars($admin_name) ?></div>
                    <div style="font-size: 11px; color: var(--admin-muted);"><?= htmlspecialchars($admin_email) ?></div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <div class="admin-content">
        <!-- Flash Alert Messages -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="admin-alert admin-alert-success">
                <span>✅</span>
                <span><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="admin-alert admin-alert-danger">
                <span>⚠️</span>
                <span><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_warning'])): ?>
            <div class="admin-alert admin-alert-warning">
                <span>⚠️</span>
                <span><?= htmlspecialchars($_SESSION['flash_warning']) ?></span>
            </div>
            <?php unset($_SESSION['flash_warning']); ?>
        <?php endif; ?>
