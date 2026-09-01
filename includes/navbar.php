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
                <!-- Shopping Cart Icon -->
                <a href="cart.php" class="cart-icon-btn" title="Shopping Cart">
                    🛍️
                    <span class="cart-badge"><?= $cart_count ?></span>
                </a>

                <!-- User Profile / Auth State -->
                <?php if (is_logged_in()): ?>
                    <div class="user-dropdown">
                        <button type="button" class="user-dropdown-btn">
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
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <a href="login.php" class="btn btn-outline btn-sm">Login</a>
                        <a href="signup.php" class="btn btn-primary btn-sm">Sign Up</a>
                    </div>
                <?php endif; ?>

                <!-- Mobile Hamburger Toggle -->
                <button type="button" class="mobile-nav-toggle" aria-label="Toggle navigation">
                    ☰
                </button>
            </div>
        </nav>
    </div>
</header>
