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

            <!-- Search Bar with Integrated Voice Search -->
            <div class="nav-search">
                <form action="products.php" method="GET" class="search-form">
                    <input type="text" name="search" class="search-input" placeholder="Search lipstick, foundation, blush..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" autocomplete="off">
                    <button type="button" id="navMicBtn" class="mic-nav-btn" title="Voice Search (Speak)">
                        🎤
                    </button>
                    <button type="submit" class="search-submit-btn" title="Search">
                        🔍
                    </button>
                </form>
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
