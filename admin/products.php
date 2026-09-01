<?php
/**
 * GlowCart Cosmetics - Admin Product Catalog Management
 */
require_once __DIR__ . '/auth_check.php';

$admin_page_title = 'Manage Products | GlowCart Admin';
$admin_header_title = 'Product Catalog';
$active_tab = 'products';

// Handle Quick Toggle Status (Active <-> Inactive)
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $toggle_id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT status FROM products WHERE id = :id");
        $stmt->execute([':id' => $toggle_id]);
        $current_status = $stmt->fetchColumn();

        if ($current_status !== false) {
            $new_status = ($current_status === 'Active') ? 'Inactive' : 'Active';
            $upd = $pdo->prepare("UPDATE products SET status = :status WHERE id = :id");
            $upd->execute([':status' => $new_status, ':id' => $toggle_id]);
            $_SESSION['flash_success'] = "Product status successfully updated to {$new_status}.";
        }
    } catch (PDOException $e) {
        $_SESSION['flash_error'] = "Failed to update product status: " . $e->getMessage();
    }
    header('Location: products.php');
    exit;
}

// Search and Filter parameters
$search = clean_input($_GET['search'] ?? '');
$category = clean_input($_GET['category'] ?? '');
$stock_status = clean_input($_GET['stock_status'] ?? '');
$status_filter = clean_input($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE :search OR description LIKE :search_desc)";
    $params[':search'] = "%{$search}%";
    $params[':search_desc'] = "%{$search}%";
}

if (!empty($category)) {
    $where[] = "category = :category";
    $params[':category'] = $category;
}

if (!empty($status_filter)) {
    $where[] = "status = :status";
    $params[':status'] = $status_filter;
}

if ($stock_status === 'low') {
    $where[] = "stock <= 5 AND stock > 0";
} elseif ($stock_status === 'out') {
    $where[] = "stock = 0";
} elseif ($stock_status === 'in_stock') {
    $where[] = "stock > 5";
}

$where_clause = implode(" AND ", $where);

// Fetch products
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE {$where_clause} ORDER BY id DESC");
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Fetch distinct categories for filter dropdown
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $total_count = count($products);
} catch (PDOException $e) {
    $products = [];
    $categories = [];
    $total_count = 0;
    $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Catalog Inventory (<?= $total_count ?>)</h1>
        <p style="color: var(--admin-muted); font-size: 13px; margin-top: 4px;">Add, edit, restock, or remove cosmetics and skincare products.</p>
    </div>
    <div>
        <a href="product_add.php" class="admin-btn admin-btn-primary">
            <span>➕</span> Add New Product
        </a>
    </div>
</div>

<!-- Search & Filtering Toolbar -->
<div class="admin-toolbar">
    <form action="products.php" method="GET" class="admin-toolbar-group" style="flex: 1;">
        <div class="admin-search-wrapper">
            <span class="admin-search-icon">🔍</span>
            <input type="text" name="search" placeholder="Search product name or desc..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <select name="category" class="admin-select" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>" <?= ($category === $cat) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="stock_status" class="admin-select" onchange="this.form.submit()">
            <option value="">All Stock Levels</option>
            <option value="in_stock" <?= ($stock_status === 'in_stock') ? 'selected' : '' ?>>Healthy (> 5)</option>
            <option value="low" <?= ($stock_status === 'low') ? 'selected' : '' ?>>Low Stock (≤ 5)</option>
            <option value="out" <?= ($stock_status === 'out') ? 'selected' : '' ?>>Out of Stock (0)</option>
        </select>

        <select name="status" class="admin-select" onchange="this.form.submit()">
            <option value="">All Visibility</option>
            <option value="Active" <?= ($status_filter === 'Active') ? 'selected' : '' ?>>Active Only</option>
            <option value="Inactive" <?= ($status_filter === 'Inactive') ? 'selected' : '' ?>>Inactive Only</option>
        </select>

        <button type="submit" class="admin-btn admin-btn-outline">Filter</button>
        <?php if (!empty($search) || !empty($category) || !empty($stock_status) || !empty($status_filter)): ?>
            <a href="products.php" class="admin-btn admin-btn-outline" style="color: var(--admin-danger);">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Products Table -->
<div class="card-table">
    <?php if (!empty($products)): ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Thumb</th>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Price (₹)</th>
                        <th>Stock</th>
                        <th>Tags</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($p['image']) ?>" alt="" class="admin-product-thumb" onerror="this.src='https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=100';">
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--admin-text); max-width: 250px;">
                                    <?= htmlspecialchars($p['name']) ?>
                                </div>
                                <div style="font-size: 11px; color: var(--admin-muted); margin-top: 2px;">
                                    ID: #<?= (int)$p['id'] ?> &bull; Rating: <?= number_format((float)$p['rating'], 1) ?> ★
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 12px; font-weight: 500; background: #f1f5f9; padding: 3px 8px; border-radius: 4px; color: #475569;">
                                    <?= htmlspecialchars($p['category']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 600;">
                                    <?= format_price($p['discount_price'] ?: $p['price']) ?>
                                </div>
                                <?php if (!empty($p['discount_price']) && $p['discount_price'] < $p['price']): ?>
                                    <div style="font-size: 11px; color: var(--admin-muted); text-decoration: line-through;">
                                        <?= format_price($p['price']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['stock'] == 0): ?>
                                    <span class="admin-badge admin-badge-cancelled">Out of Stock</span>
                                <?php elseif ($p['stock'] <= 5): ?>
                                    <span class="admin-badge admin-badge-warning"><?= (int)$p['stock'] ?> Left (Low)</span>
                                <?php else: ?>
                                    <span class="stock-healthy"><?= (int)$p['stock'] ?> units</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                    <?php if (!empty($p['is_featured'])): ?>
                                        <span class="admin-badge" style="background: #fef3c7; color: #92400e; font-size: 10px;" title="Featured on Homepage">⭐ Featured</span>
                                    <?php endif; ?>
                                    <?php if (!empty($p['is_bestseller'])): ?>
                                        <span class="admin-badge" style="background: #fce7f3; color: #9d174d; font-size: 10px;" title="Bestseller Badge">🔥 Bestseller</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <a href="products.php?action=toggle_status&id=<?= $p['id'] ?>" title="Click to toggle active state">
                                    <?php if ($p['status'] === 'Active'): ?>
                                        <span class="admin-badge admin-badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="admin-badge admin-badge-inactive">Inactive</span>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="product_edit.php?id=<?= $p['id'] ?>" class="admin-btn admin-btn-outline admin-btn-sm" title="Edit Product">
                                        ✏️ Edit
                                    </a>
                                    <form action="product_delete.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete \'<?= htmlspecialchars(addslashes($p['name'])) ?>\'? This action cannot be undone.');">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="admin-btn admin-btn-danger admin-btn-sm" title="Delete Product">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty-state">
            <span class="icon">🔍</span>
            <h3>No products found</h3>
            <p>Try clearing your search filters or add a new cosmetic product.</p>
            <div style="margin-top: 15px;">
                <a href="product_add.php" class="admin-btn admin-btn-primary">Add New Product</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
