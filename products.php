<?php
/**
 * GlowCart Cosmetics - Product Catalog, Search & Filtering
 */
$page_title = 'Shop Cosmetics & Beauty | GlowCart';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$user_favorite_ids = is_logged_in() ? get_user_favorite_ids((int)$_SESSION['user_id']) : [];

// Fetch unique categories for filter list
try {
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE status = 'Active' ORDER BY category ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}

// Build Filter & Search Query
$search = clean_input($_GET['search'] ?? '');
$category = clean_input($_GET['category'] ?? '');
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : '';
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : '';
$in_stock_only = isset($_GET['in_stock']) && $_GET['in_stock'] == '1';
$sort = clean_input($_GET['sort'] ?? 'newest');

$where = ["status = 'Active'"];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE :search OR category LIKE :search_cat OR description LIKE :search_desc)";
    $params[':search'] = "%{$search}%";
    $params[':search_cat'] = "%{$search}%";
    $params[':search_desc'] = "%{$search}%";
}

if (!empty($category)) {
    $where[] = "category = :category";
    $params[':category'] = $category;
}

if ($min_price !== '') {
    $where[] = "COALESCE(discount_price, price) >= (:min_price + 0)";
    $params[':min_price'] = $min_price;
}

if ($max_price !== '') {
    $where[] = "COALESCE(discount_price, price) <= (:max_price + 0)";
    $params[':max_price'] = $max_price;
}

if ($in_stock_only) {
    $where[] = "stock > 0";
}

// Sorting logic
$order_by = "id DESC";
if ($sort === 'price_asc') {
    $order_by = "COALESCE(discount_price, price) ASC";
} elseif ($sort === 'price_desc') {
    $order_by = "COALESCE(discount_price, price) DESC";
} elseif ($sort === 'popular') {
    $order_by = "rating DESC, id DESC";
} elseif ($sort === 'newest') {
    $order_by = "id DESC";
}

$sql = "SELECT * FROM products WHERE " . implode(' AND ', $where) . " ORDER BY {$order_by}";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

// Track active filters for chips & customized diagnostics
$active_filters = [];

if (!empty($search)) {
    $q = $_GET;
    unset($q['search']);
    $active_filters[] = [
        'key' => 'search',
        'label' => 'Keyword: "' . htmlspecialchars($search) . '"',
        'remove_url' => 'products.php' . (!empty($q) ? '?' . http_build_query($q) : '')
    ];
}

if (!empty($category)) {
    $q = $_GET;
    unset($q['category']);
    $active_filters[] = [
        'key' => 'category',
        'label' => 'Category: ' . htmlspecialchars($category),
        'remove_url' => 'products.php' . (!empty($q) ? '?' . http_build_query($q) : '')
    ];
}

if ($min_price !== '' || $max_price !== '') {
    $q = $_GET;
    unset($q['min_price'], $q['max_price']);
    $price_label = 'Price: ';
    if ($min_price !== '' && $max_price !== '') {
        $price_label .= '₹' . number_format($min_price) . ' - ₹' . number_format($max_price);
    } elseif ($min_price !== '') {
        $price_label .= 'Min ₹' . number_format($min_price);
    } else {
        $price_label .= 'Up to ₹' . number_format($max_price);
    }
    $active_filters[] = [
        'key' => 'price',
        'label' => $price_label,
        'remove_url' => 'products.php' . (!empty($q) ? '?' . http_build_query($q) : '')
    ];
}

if ($in_stock_only) {
    $q = $_GET;
    unset($q['in_stock']);
    $active_filters[] = [
        'key' => 'stock',
        'label' => 'In Stock Only',
        'remove_url' => 'products.php' . (!empty($q) ? '?' . http_build_query($q) : '')
    ];
}

// Fallback recommendations when no products match the current filters
$recommended_products = [];
if (empty($products)) {
    try {
        $rec_stmt = $pdo->query("SELECT * FROM products WHERE status = 'Active' ORDER BY is_bestseller DESC, rating DESC, id DESC LIMIT 4");
        $recommended_products = $rec_stmt->fetchAll();
    } catch (PDOException $e) {
        $recommended_products = [];
    }
}
?>

<main class="container">
    <div class="mobile-filter-overlay" id="mobileFilterOverlay" aria-hidden="true"></div>
    <div class="shop-layout">
        <!-- Filter & Search Sidebar -->
        <aside class="filter-sidebar">
            <div class="filter-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h3 style="font-size: 16px; margin: 0;">Filters</h3>
                    <a href="products.php" style="font-size: 12px; color: var(--primary);">Clear All</a>
                </div>
                <button type="button" class="mobile-filter-drawer-close" id="mobileFilterCloseBtn" aria-label="Close filters">✕</button>
            </div>

            <form action="products.php" method="GET" id="filterForm">
                <!-- Search Query Input in Sidebar -->
                <div class="filter-group">
                    <div class="filter-title">Search Keywords</div>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="text" name="search" id="sidebarSearchInput" class="price-input" style="width: 100%; padding-left: 28px; padding-right: 28px; height: 38px;" placeholder="Search cosmetics..." value="<?= htmlspecialchars($search) ?>">
                        <span style="position: absolute; left: 8px; font-size: 13px; color: var(--text-muted); pointer-events: none;">🔍</span>
                        <?php if (!empty($search)): ?>
                            <a href="products.php<?= !empty($category) ? '?category=' . urlencode($category) : '' ?>" style="position: absolute; right: 8px; font-size: 12px; color: var(--text-muted); text-decoration: none; padding: 2px 5px;" title="Clear search">✕</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Categories -->
                <div class="filter-group">
                    <div class="filter-title">Category</div>
                    <ul class="filter-list">
                        <li class="filter-list-item">
                            <label class="filter-label">
                                <input type="radio" name="category" value="" <?= empty($category) ? 'checked' : '' ?> onchange="document.getElementById('filterForm').submit()">
                                <span>All Categories</span>
                            </label>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li class="filter-list-item">
                                <label class="filter-label">
                                    <input type="radio" name="category" value="<?= htmlspecialchars($cat) ?>" <?= ($category === $cat) ? 'checked' : '' ?> onchange="document.getElementById('filterForm').submit()">
                                    <span><?= htmlspecialchars($cat) ?></span>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Price Range -->
                <div class="filter-group">
                    <div class="filter-title">Price Range (₹)</div>
                    <div class="price-range-inputs">
                        <input type="number" name="min_price" id="minPriceInput" class="price-input" placeholder="Min" value="<?= htmlspecialchars($min_price) ?>" min="0">
                        <span>-</span>
                        <input type="number" name="max_price" id="maxPriceInput" class="price-input" placeholder="Max" value="<?= htmlspecialchars($max_price) ?>" min="0">
                    </div>
                    <div class="price-presets-wrap">
                        <span class="price-presets-title">Quick Presets:</span>
                        <div class="price-preset-chips">
                            <button type="button" class="price-preset-btn <?= ($min_price === (float)0 && $max_price === (float)500) ? 'active' : '' ?>" onclick="applyPricePreset(0, 500)">Under ₹500</button>
                            <button type="button" class="price-preset-btn <?= ($min_price === (float)500 && $max_price === (float)1000) ? 'active' : '' ?>" onclick="applyPricePreset(500, 1000)">₹500 - 1K</button>
                            <button type="button" class="price-preset-btn <?= ($min_price === (float)1000 && $max_price === (float)2500) ? 'active' : '' ?>" onclick="applyPricePreset(1000, 2500)">₹1K - 2.5K</button>
                            <button type="button" class="price-preset-btn <?= ($min_price === (float)2500 && $max_price === (float)5000) ? 'active' : '' ?>" onclick="applyPricePreset(2500, 5000)">₹2.5K - 5K</button>
                        </div>
                    </div>
                </div>

                <!-- Stock Availability -->
                <div class="filter-group">
                    <div class="filter-title">Availability</div>
                    <label class="filter-label">
                        <input type="checkbox" name="in_stock" value="1" <?= $in_stock_only ? 'checked' : '' ?> onchange="document.getElementById('filterForm').submit()">
                        <span>In Stock Only</span>
                    </label>
                </div>

                <!-- Sort Field (hidden, syncs with top bar) -->
                <input type="hidden" name="sort" id="hiddenSort" value="<?= htmlspecialchars($sort) ?>">

                <button type="submit" class="btn btn-primary btn-block btn-sm">Apply Filters</button>
            </form>
        </aside>

        <!-- Product Grid Area -->
        <section>
            <!-- Mobile Filter Trigger Bar -->
            <div class="mobile-filter-trigger-bar">
                <button type="button" class="mobile-filter-open-btn" id="mobileFilterOpenBtn">
                    <span>⚡</span> Filter & Categories
                    <?php if (!empty($active_filters)): ?>
                        <span class="cart-badge-inline"><?= count($active_filters) ?></span>
                    <?php endif; ?>
                </button>
                <div style="font-size: 13px; color: var(--text-muted); font-weight: 500;">
                    <?= count($products) ?> items found
                </div>
            </div>

            <!-- Top Controls Bar -->
            <div class="shop-top-bar">
                <div>
                    <h1 style="font-size: 24px; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <?php if (!empty($category)): ?>
                            <?= htmlspecialchars($category) ?>
                        <?php elseif (!empty($search)): ?>
                            Search: "<span style="color: var(--primary);"><?= htmlspecialchars($search) ?></span>"
                            <a href="products.php" class="badge badge-sale" style="font-size: 11px; text-decoration: none; padding: 3px 8px;" title="Clear search query">✕ Clear</a>
                        <?php else: ?>
                            All Beauty Products
                        <?php endif; ?>
                    </h1>
                    <span style="font-size: 13px; color: var(--text-muted);"><?= count($products) ?> <?= count($products) === 1 ? 'item' : 'items' ?> found</span>
                </div>

                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="sortSelect" style="font-size: 13px; color: var(--text-muted);">Sort By:</label>
                    <select id="sortSelect" class="sort-select" onchange="document.getElementById('hiddenSort').value = this.value; document.getElementById('filterForm').submit();">
                        <option value="newest" <?= ($sort === 'newest') ? 'selected' : '' ?>>Newest Arrivals</option>
                        <option value="price_asc" <?= ($sort === 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= ($sort === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="popular" <?= ($sort === 'popular') ? 'selected' : '' ?>>Popular & Highest Rated</option>
                    </select>
                </div>
            </div>

            <!-- Active Filter Badges Bar -->
            <?php if (!empty($active_filters)): ?>
                <div class="active-filter-chips-bar">
                    <span class="active-chips-title">Active Filters:</span>
                    <div class="active-chips-list">
                        <?php foreach ($active_filters as $af): ?>
                            <a href="<?= $af['remove_url'] ?>" class="active-filter-chip" title="Click to remove this filter">
                                <span><?= $af['label'] ?></span>
                                <span class="chip-close-icon" aria-hidden="true">&times;</span>
                            </a>
                        <?php endforeach; ?>
                        <a href="products.php" class="active-filter-reset-all" title="Reset all applied filters">Reset All</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Products Grid -->
            <?php if (!empty($products)): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <?php $is_fav = in_array((int)$product['id'], $user_favorite_ids); ?>
                        <div class="product-card">
                            <div class="product-thumb">
                                <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                                <div class="product-badges">
                                    <?php if (!empty($product['discount_price']) && $product['discount_price'] < $product['price']): ?>
                                        <?php $pct = round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>
                                        <span class="badge badge-sale"><?= $pct ?>% OFF</span>
                                    <?php endif; ?>
                                </div>
                                <button type="button" 
                                        class="card-fav-btn <?= $is_fav ? 'active' : '' ?>" 
                                        onclick="toggleFavorite(<?= $product['id'] ?>, this)" 
                                        title="<?= $is_fav ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                                        data-product-id="<?= $product['id'] ?>">
                                    <?= $is_fav ? '❤️' : '🤍' ?>
                                </button>
                                <div class="product-actions-overlay">
                                    <a href="product_details.php?id=<?= $product['id'] ?>" class="action-icon-btn action-qv-btn" data-quickview-id="<?= $product['id'] ?>" title="Quick View">👁️</a>
                                    <button type="button" 
                                            class="action-icon-btn action-fav-btn <?= $is_fav ? 'active' : '' ?>" 
                                            onclick="toggleFavorite(<?= $product['id'] ?>, this)" 
                                            title="<?= $is_fav ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                                            data-product-id="<?= $product['id'] ?>">
                                        <?= $is_fav ? '❤️' : '🤍' ?>
                                    </button>
                                    <?php if ($product['stock'] > 0): ?>
                                        <button type="button" class="action-icon-btn" onclick="addToCart(<?= $product['id'] ?>, 1)" title="Add to Cart">🛍️</button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="product-info">
                                <div class="product-category"><?= highlight_text($product['category'], $search) ?></div>
                                <h3 class="product-title">
                                    <a href="product_details.php?id=<?= $product['id'] ?>"><?= highlight_text($product['name'], $search) ?></a>
                                </h3>

                                <div class="product-rating">
                                    ★ <?= number_format($product['rating'], 1) ?>
                                    <span>(<?= $product['stock'] > 0 ? "{$product['stock']} in stock" : 'Sold out' ?>)</span>
                                </div>

                                <div class="product-price-stock">
                                    <div class="price-wrapper">
                                        <span class="current-price"><?= format_price($product['discount_price'] ?? $product['price']) ?></span>
                                        <?php if (!empty($product['discount_price']) && $product['discount_price'] < $product['price']): ?>
                                            <span class="original-price"><?= format_price($product['price']) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <?php if ($product['stock'] > 0): ?>
                                            <span class="badge badge-in-stock">✓ In Stock</span>
                                        <?php else: ?>
                                            <span class="badge badge-out-of-stock">✕ Out of Stock</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="product-btn-row">
                                    <?php if ($product['stock'] > 0): ?>
                                        <button type="button" class="btn btn-primary btn-block btn-sm" onclick="addToCart(<?= $product['id'] ?>, 1)">
                                            🛍️ Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-outline btn-block btn-sm disabled" disabled>
                                            ✕ Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Customized Luxury Empty State -->
                <div class="shop-empty-state">
                    <div class="empty-state-visual">
                        <div class="empty-state-halo"></div>
                        <div class="empty-state-badge">
                            <span class="empty-main-icon">💄</span>
                            <span class="empty-sparkle sparkle-1">✨</span>
                            <span class="empty-sparkle sparkle-2">🌸</span>
                            <span class="empty-sparkle sparkle-3">✨</span>
                        </div>
                    </div>

                    <h2 class="empty-state-title">No Matching Products Found</h2>

                    <p class="empty-state-desc">
                        <?php if (!empty($search) && ($min_price !== '' || $max_price !== '')): ?>
                            We couldn't find any beauty products matching "<strong><?= htmlspecialchars($search) ?></strong>" within your selected price range (<?= $price_label ?? '' ?>).
                        <?php elseif (!empty($search)): ?>
                            We couldn't find any cosmetic products matching "<strong><?= htmlspecialchars($search) ?></strong>". Try checking for spelling mistakes or searching by broader keywords like <em>lipstick</em>, <em>serum</em>, or <em>palette</em>.
                        <?php elseif ($min_price !== '' || $max_price !== ''): ?>
                            No beauty products currently match the price range <strong><?= $price_label ?? '' ?></strong>. Try broadening your price range or resetting the filter.
                        <?php elseif (!empty($category)): ?>
                            We couldn't find active products in the <strong><?= htmlspecialchars($category) ?></strong> category matching your criteria.
                        <?php elseif ($in_stock_only): ?>
                            All products in this selection are currently sold out. Uncheck "In Stock Only" to view all items.
                        <?php else: ?>
                            We could not find any cosmetic products matching your current filter criteria.
                        <?php endif; ?>
                    </p>

                    <!-- Active Filter Chips Inside Empty State for Easy Single-Click Dismissal -->
                    <?php if (!empty($active_filters)): ?>
                        <div class="empty-active-chips-wrap">
                            <span class="empty-chips-label">Filters preventing results:</span>
                            <div class="active-chips-list">
                                <?php foreach ($active_filters as $af): ?>
                                    <a href="<?= $af['remove_url'] ?>" class="active-filter-chip" title="Remove this filter">
                                        <span><?= $af['label'] ?></span>
                                        <span class="chip-close-icon" aria-hidden="true">&times;</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Contextual Quick Action Buttons -->
                    <div class="empty-actions-row">
                        <a href="products.php" class="btn btn-primary empty-action-btn">
                            🔄 Reset All Filters
                        </a>
                        <?php if ($min_price !== '' || $max_price !== ''): ?>
                            <?php 
                                $q_no_price = $_GET; 
                                unset($q_no_price['min_price'], $q_no_price['max_price']); 
                            ?>
                            <a href="products.php<?= !empty($q_no_price) ? '?' . http_build_query($q_no_price) : '' ?>" class="btn btn-outline empty-action-btn">
                                💰 Clear Price Filter
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($search)): ?>
                            <?php 
                                $q_no_search = $_GET; 
                                unset($q_no_search['search']); 
                            ?>
                            <a href="products.php<?= !empty($q_no_search) ? '?' . http_build_query($q_no_search) : '' ?>" class="btn btn-outline empty-action-btn">
                                🔍 Clear Search Term
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Category Shortcuts -->
                    <div class="empty-quick-categories">
                        <span class="empty-quick-title">Explore Popular Categories:</span>
                        <div class="empty-cat-chips">
                            <a href="products.php?category=Lipstick" class="empty-cat-chip">💄 Lipsticks</a>
                            <a href="products.php?category=Skincare" class="empty-cat-chip">✨ Skincare</a>
                            <a href="products.php?category=Eyeshadow" class="empty-cat-chip">👁️ Eyeshadow</a>
                            <a href="products.php?category=Blush" class="empty-cat-chip">🌸 Blush</a>
                            <a href="products.php?category=Makeup+Kits" class="empty-cat-chip">🎁 Makeup Kits</a>
                            <a href="products.php?category=Foundation" class="empty-cat-chip">✨ Foundation</a>
                        </div>
                    </div>
                </div>

                <!-- Fallback Curated Bestsellers Showcase -->
                <?php if (!empty($recommended_products)): ?>
                    <div class="empty-fallback-showcase">
                        <div class="empty-fallback-header">
                            <div class="empty-fallback-pill">Trending Recommendations</div>
                            <h3 class="empty-fallback-title">Beauty Bestsellers You'll Love</h3>
                            <p class="empty-fallback-desc">Don't leave empty-handed! Explore our highest-rated cosmetics chosen by beauty lovers.</p>
                        </div>

                        <div class="products-grid">
                            <?php foreach ($recommended_products as $rec_prod): ?>
                                <?php $is_fav = in_array((int)$rec_prod['id'], $user_favorite_ids); ?>
                                <div class="product-card">
                                    <div class="product-thumb">
                                        <img src="<?= htmlspecialchars($rec_prod['image']) ?>" alt="<?= htmlspecialchars($rec_prod['name']) ?>" loading="lazy">
                                        <div class="product-badges">
                                            <?php if (!empty($rec_prod['discount_price']) && $rec_prod['discount_price'] < $rec_prod['price']): ?>
                                                <?php $pct = round((($rec_prod['price'] - $rec_prod['discount_price']) / $rec_prod['price']) * 100); ?>
                                                <span class="badge badge-sale"><?= $pct ?>% OFF</span>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" 
                                                class="card-fav-btn <?= $is_fav ? 'active' : '' ?>" 
                                                onclick="toggleFavorite(<?= $rec_prod['id'] ?>, this)" 
                                                title="<?= $is_fav ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                                                data-product-id="<?= $rec_prod['id'] ?>">
                                            <?= $is_fav ? '❤️' : '🤍' ?>
                                        </button>
                                        <div class="product-actions-overlay">
                                            <a href="product_details.php?id=<?= $rec_prod['id'] ?>" class="action-icon-btn action-qv-btn" data-quickview-id="<?= $rec_prod['id'] ?>" title="Quick View">👁️</a>
                                            <button type="button" 
                                                    class="action-icon-btn action-fav-btn <?= $is_fav ? 'active' : '' ?>" 
                                                    onclick="toggleFavorite(<?= $rec_prod['id'] ?>, this)" 
                                                    title="<?= $is_fav ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                                                    data-product-id="<?= $rec_prod['id'] ?>">
                                                <?= $is_fav ? '❤️' : '🤍' ?>
                                            </button>
                                            <?php if ($rec_prod['stock'] > 0): ?>
                                                <button type="button" class="action-icon-btn" onclick="addToCart(<?= $rec_prod['id'] ?>, 1)" title="Add to Cart">🛍️</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="product-info">
                                        <div class="product-category"><?= htmlspecialchars($rec_prod['category']) ?></div>
                                        <h3 class="product-title">
                                            <a href="product_details.php?id=<?= $rec_prod['id'] ?>"><?= htmlspecialchars($rec_prod['name']) ?></a>
                                        </h3>

                                        <div class="product-rating">
                                            ★ <?= number_format($rec_prod['rating'], 1) ?>
                                            <span>(<?= $rec_prod['stock'] > 0 ? "{$rec_prod['stock']} in stock" : 'Sold out' ?>)</span>
                                        </div>

                                        <div class="product-price-stock">
                                            <div class="price-wrapper">
                                                <span class="current-price"><?= format_price($rec_prod['discount_price'] ?? $rec_prod['price']) ?></span>
                                                <?php if (!empty($rec_prod['discount_price']) && $rec_prod['discount_price'] < $rec_prod['price']): ?>
                                                    <span class="original-price"><?= format_price($rec_prod['price']) ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <div>
                                                <?php if ($rec_prod['stock'] > 0): ?>
                                                    <span class="badge badge-in-stock">✓ In Stock</span>
                                                <?php else: ?>
                                                    <span class="badge badge-out-of-stock">✕ Out of Stock</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="product-btn-row">
                                            <?php if ($rec_prod['stock'] > 0): ?>
                                                <button type="button" class="btn btn-primary btn-block btn-sm" onclick="addToCart(<?= $rec_prod['id'] ?>, 1)">
                                                    🛍️ Add to Cart
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline btn-block btn-sm disabled" disabled>
                                                    ✕ Out of Stock
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
function applyPricePreset(min, max) {
    const minInput = document.getElementById('minPriceInput');
    const maxInput = document.getElementById('maxPriceInput');
    if (minInput && maxInput) {
        minInput.value = min > 0 ? min : '';
        maxInput.value = max > 0 ? max : '';
        document.getElementById('filterForm').submit();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
