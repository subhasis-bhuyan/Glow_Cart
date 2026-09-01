<?php
/**
 * GlowCart Cosmetics - Responsive Navigation Bar
 */
require_once __DIR__ . '/../config/db.php';
$cart_count = get_cart_count();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="navbar-wrapper">
    <div class="container">
        <nav class="navbar">
            <!-- Brand Logo -->
            <a href="index.php" class="brand-logo">
                💄 GLOWCART <span>COSMETICS</span>
            </a>

            <!-- Navigation Links -->
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>">Home</a></li>
                <li><a href="products.php" class="nav-link <?= ($current_page == 'products.php' && empty($_GET['category'])) ? 'active' : '' ?>">Shop</a></li>
                <li><a href="products.php?category=Lipstick" class="nav-link <?= (isset($_GET['category']) && $_GET['category'] == 'Lipstick') ? 'active' : '' ?>">Lipsticks</a></li>
                <li><a href="products.php?category=Skincare" class="nav-link <?= (isset($_GET['category']) && $_GET['category'] == 'Skincare') ? 'active' : '' ?>">Skincare</a></li>
                <li><a href="products.php?category=Makeup Kits" class="nav-link <?= (isset($_GET['category']) && $_GET['category'] == 'Makeup Kits') ? 'active' : '' ?>">Kits</a></li>
            </ul>

            <!-- Search Bar with Live Instant Results Display -->
            <div class="nav-search" id="navSearchWrapper">
                <form action="products.php" method="GET" class="search-form" id="navSearchForm" autocomplete="off">
                    <span class="search-icon-left" aria-hidden="true">🔍</span>
                    <input type="text" name="search" id="navSearchInput" class="search-input" placeholder="Search lipstick, foundation, blush, orders..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" autocomplete="off" aria-label="Search products and orders" aria-autocomplete="list" aria-controls="searchDropdownMenu">
                    <button type="button" class="search-clear-btn" id="navSearchClear" title="Clear search" aria-label="Clear search input" style="display: <?= !empty($_GET['search']) ? 'flex' : 'none' ?>;">✕</button>
                    <div class="search-spinner" id="navSearchSpinner" aria-hidden="true" style="display: none;"></div>
                    <button type="submit" class="search-submit-btn" title="Search" aria-label="Submit Search">
                        <span>Search</span>
                    </button>
                </form>
                <!-- Live Instant Search Results Dropdown -->
                <div class="search-dropdown-menu" id="searchDropdownMenu" role="listbox" aria-label="Live Search Suggestions" style="display: none;"></div>
            </div>

            <!-- Nav Actions: Cart & Customer Profile -->
            <div class="nav-actions">
                <!-- Mobile Search Toggle Button -->
                <button type="button" class="mobile-search-toggle-btn" id="mobileSearchToggleBtn" aria-label="Toggle search bar" title="Search">
                    🔍
                </button>

                <!-- Shopping Cart Icon -->
                <a href="cart.php" class="cart-icon-btn" title="Shopping Cart" aria-label="Shopping Cart with <?= $cart_count ?> items">
                    🛍️
                    <span class="cart-badge"><?= $cart_count ?></span>
                </a>

                <!-- User Profile / Auth State (Desktop) -->
                <?php if (is_logged_in()): ?>
                    <div class="user-dropdown desktop-only-auth">
                        <button type="button" class="user-dropdown-btn" aria-haspopup="true" aria-expanded="false">
                            👤 Hi, <?= htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'User')[0]) ?> ▾
                        </button>
                        <ul class="user-dropdown-menu">
                            <li><a href="profile.php"><span>👤</span> My Profile</a></li>
                            <li><a href="profile.php#favorites"><span>❤️</span> My Favorites</a></li>
                            <li><a href="orders.php"><span>📦</span> My Orders</a></li>
                            <li class="dropdown-divider"></li>
                            <li><a href="logout.php" style="color: var(--danger);"><span>🚪</span> Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="desktop-only-auth" style="display: flex; gap: 8px; align-items: center;">
                        <a href="login.php" class="btn btn-outline btn-sm">Login</a>
                        <a href="signup.php" class="btn btn-primary btn-sm">Sign Up</a>
                    </div>
                <?php endif; ?>

                <!-- Mobile Hamburger Toggle -->
                <button type="button" class="mobile-nav-toggle" id="mobileNavToggleBtn" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobileDrawer">
                    <span class="hamburger-bar"></span>
                    <span class="hamburger-bar"></span>
                    <span class="hamburger-bar"></span>
                </button>
            </div>
        </nav>
    </div>
</header>

<!-- Off-Canvas Mobile Navigation Drawer & Backdrop -->
<div class="mobile-drawer-overlay" id="mobileDrawerOverlay" aria-hidden="true"></div>
<aside class="mobile-drawer" id="mobileDrawer" role="dialog" aria-modal="true" aria-label="Mobile Navigation Menu">
    <div class="drawer-header">
        <div class="drawer-user-info">
            <?php if (is_logged_in()): ?>
                <div class="drawer-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <div class="drawer-user-name">Hi, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
                    <div class="drawer-user-sub"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></div>
                </div>
            <?php else: ?>
                <div class="drawer-avatar">✨</div>
                <div>
                    <div class="drawer-user-name">Welcome to GlowCart</div>
                    <div style="margin-top: 4px;">
                        <a href="login.php" class="drawer-auth-link">Login</a> &bull; 
                        <a href="signup.php" class="drawer-auth-link">Sign Up</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <button type="button" class="drawer-close-btn" id="drawerCloseBtn" aria-label="Close navigation menu">✕</button>
    </div>

    <!-- Drawer Navigation Links -->
    <div class="drawer-body">
        <div class="drawer-section-title">Beauty Catalog</div>
        <ul class="drawer-links-list">
            <li><a href="index.php" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>"><span>🏠</span> Home</a></li>
            <li><a href="products.php" class="<?= ($current_page == 'products.php' && empty($_GET['category'])) ? 'active' : '' ?>"><span>🛍️</span> Shop All Products</a></li>
            <li><a href="products.php?category=Lipstick" class="<?= (isset($_GET['category']) && $_GET['category'] == 'Lipstick') ? 'active' : '' ?>"><span>💄</span> Lipsticks & Gloss</a></li>
            <li><a href="products.php?category=Foundation" class="<?= (isset($_GET['category']) && $_GET['category'] == 'Foundation') ? 'active' : '' ?>"><span>🧴</span> Foundations & Compact</a></li>
            <li><a href="products.php?category=Blush" class="<?= (isset($_GET['category']) && $_GET['category'] == 'Blush') ? 'active' : '' ?>"><span>🌸</span> Blush & Bronzers</a></li>
            <li><a href="products.php?category=Eyeshadow" class="<?= (isset($_GET['category']) && $_GET['category'] == 'Eyeshadow') ? 'active' : '' ?>"><span>🎨</span> Eyeshadow Palettes</a></li>
            <li><a href="products.php?category=Skincare" class="<?= (isset($_GET['category']) && $_GET['category'] == 'Skincare') ? 'active' : '' ?>"><span>🌿</span> Serums & Skincare</a></li>
            <li><a href="products.php?category=Makeup Kits" class="<?= (isset($_GET['category']) && $_GET['category'] == 'Makeup Kits') ? 'active' : '' ?>"><span>🎁</span> Bridal & Makeup Kits</a></li>
            <li><a href="products.php?category=Accessories" class="<?= (isset($_GET['category']) && $_GET['category'] == 'Accessories') ? 'active' : '' ?>"><span>🖌️</span> Brushes & Accessories</a></li>
        </ul>

        <div class="drawer-section-title" style="margin-top: 20px;">My Account</div>
        <ul class="drawer-links-list">
            <li><a href="cart.php"><span>🛍️</span> Shopping Cart (<span class="cart-badge-inline"><?= $cart_count ?></span>)</a></li>
            <?php if (is_logged_in()): ?>
                <li><a href="profile.php"><span>👤</span> My Profile & Address</a></li>
                <li><a href="profile.php#favorites"><span>❤️</span> My Liked / Favorites</a></li>
                <li><a href="orders.php"><span>📦</span> My Orders & Receipts</a></li>
                <li><a href="logout.php" style="color: var(--danger);"><span>🚪</span> Log Out</a></li>
            <?php else: ?>
                <li><a href="login.php"><span>🔑</span> Customer Login</a></li>
                <li><a href="signup.php"><span>✨</span> Create New Account</a></li>
            <?php endif; ?>
        </ul>

        <div class="drawer-promo-card">
            <div style="font-size: 11px; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px;">Special Offer</div>
            <div style="font-size: 13px; font-weight: 600; color: var(--text-main); margin-top: 2px;">Use code GLOW15 for 15% off</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">On all orders over ₹500</div>
        </div>
    </div>
</aside>

<!-- Mobile Bottom Sticky Navigation Bar (App Bar) -->
<nav class="mobile-bottom-nav" aria-label="Mobile primary navigation">
    <a href="index.php" class="mobile-bottom-nav-item <?= ($current_page == 'index.php') ? 'active' : '' ?>" title="Home">
        <span class="bottom-nav-icon">🏠</span>
        <span class="bottom-nav-label">Home</span>
    </a>
    <a href="products.php" class="mobile-bottom-nav-item <?= ($current_page == 'products.php') ? 'active' : '' ?>" title="Shop">
        <span class="bottom-nav-icon">🛍️</span>
        <span class="bottom-nav-label">Shop</span>
    </a>
    <button type="button" class="mobile-bottom-nav-item" id="bottomNavSearchBtn" title="Search">
        <span class="bottom-nav-icon">🔍</span>
        <span class="bottom-nav-label">Search</span>
    </button>
    <a href="profile.php#favorites" class="mobile-bottom-nav-item" title="Favorites">
        <span class="bottom-nav-icon">❤️</span>
        <span class="bottom-nav-label">Favorites</span>
    </a>
    <a href="cart.php" class="mobile-bottom-nav-item <?= ($current_page == 'cart.php') ? 'active' : '' ?>" title="Cart">
        <span class="bottom-nav-icon" style="position: relative;">
            🛒
            <span class="bottom-nav-badge cart-badge"><?= $cart_count ?></span>
        </span>
        <span class="bottom-nav-label">Cart</span>
    </a>
</nav>
