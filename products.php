<?php
/**
 * GlowCart Cosmetics - Product Catalog, Search & Filtering
 */
$page_title = 'Shop Cosmetics & Beauty | GlowCart';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

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
    $where[] = "COALESCE(discount_price, price) >= :min_price";
    $params[':min_price'] = $min_price;
}

if ($max_price !== '') {
    $where[] = "COALESCE(discount_price, price) <= :max_price";
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
?>

<main class="container">
    <div class="shop-layout">
        <!-- Filter & Search Sidebar -->
        <aside class="filter-sidebar">
            <div class="filter-header">
                <h3 style="font-size: 16px; margin: 0;">Filters</h3>
                <a href="products.php" style="font-size: 12px; color: var(--primary);">Clear All</a>
            </div>

            <form action="products.php" method="GET" id="filterForm">
                <!-- Preserve Search if present -->
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>

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
                        <input type="number" name="min_price" class="price-input" placeholder="Min" value="<?= htmlspecialchars($min_price) ?>" min="0">
                        <span>-</span>
                        <input type="number" name="max_price" class="price-input" placeholder="Max" value="<?= htmlspecialchars($max_price) ?>" min="0">
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
            <!-- Top Controls Bar -->
            <div class="shop-top-bar">
                <div>
                    <h1 style="font-size: 24px; margin-bottom: 4px;">
                        <?php if (!empty($category)): ?>
                            <?= htmlspecialchars($category) ?>
                        <?php elseif (!empty($search)): ?>
                            Search: "<?= htmlspecialchars($search) ?>"
                        <?php else: ?>
                            All Beauty Products
                        <?php endif; ?>
                    </h1>
                    <span style="font-size: 13px; color: var(--text-muted);"><?= count($products) ?> items found</span>
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

            <!-- Products Grid -->
            <?php if (!empty($products)): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-thumb">
                                <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                                <div class="product-badges">
                                    <?php if (!empty($product['discount_price']) && $product['discount_price'] < $product['price']): ?>
                                        <?php $pct = round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>
                                        <span class="badge badge-sale"><?= $pct ?>% OFF</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-actions-overlay">
                                    <a href="product_details.php?id=<?= $product['id'] ?>" class="action-icon-btn" title="View Details">👁️</a>
                                    <?php if ($product['stock'] > 0): ?>
                                        <button type="button" class="action-icon-btn" onclick="addToCart(<?= $product['id'] ?>, 1)" title="Add to Cart">🛍️</button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="product-info">
                                <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                                <h3 class="product-title">
                                    <a href="product_details.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a>
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
                <div style="background: var(--surface); padding: 50px 30px; text-align: center; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                    <div style="font-size: 48px; margin-bottom: 15px;">🔍</div>
                    <h3 style="font-size: 20px; margin-bottom: 10px;">No Products Found</h3>
                    <p style="margin-bottom: 20px;">We could not find any cosmetic products matching your current search or filter criteria.</p>
                    <a href="products.php" class="btn btn-primary">Reset All Filters</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
