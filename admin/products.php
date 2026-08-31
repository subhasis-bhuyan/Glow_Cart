<?php
/**
 * GlowCart Cosmetics - Admin Product Management
 */
require_once __DIR__ . '/../config/db.php';
require_admin('login.php');

$admin_page_title = 'Product Management | GlowCart Admin';

// Handle Status Toggle Quick Action
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $pid = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE products SET status = IF(status = 'Active', 'Inactive', 'Active') WHERE id = :id");
        $stmt->execute([':id' => $pid]);
        $_SESSION['admin_flash_success'] = 'Product status updated successfully.';
    } catch (PDOException $e) {
        $_SESSION['admin_flash_error'] = 'Could not toggle product status.';
    }
    header('Location: products.php');
    exit;
}

// Search and Category Filter
$search = clean_input($_GET['search'] ?? '');
$category = clean_input($_GET['category'] ?? '');

$where = ["1 = 1"];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE :search OR category LIKE :search_cat)";
    $params[':search'] = "%{$search}%";
    $params[':search_cat'] = "%{$search}%";
}

if (!empty($category)) {
    $where[] = "category = :category";
    $params[':category'] = $category;
}

$sql = "SELECT * FROM products WHERE " . implode(' AND ', $where) . " ORDER BY id DESC";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Distinct categories for filter
    $cat_list = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Database query error.");
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Manage Products</h1>
        <p style="color: var(--admin-muted); font-size: 13px;">View, edit, restock, or remove cosmetics from catalog.</p>
    </div>

    <a href="add_product.php" class="admin-btn admin-btn-primary">
        ➕ Add New Product
    </a>
</div>

<!-- Search & Filter Bar -->
<div style="background: var(--admin-surface); padding: 18px 20px; border-radius: var(--radius); border: 1px solid var(--admin-border); margin-bottom: 25px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
    <form action="products.php" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; flex: 1;">
        <input type="text" name="search" class="admin-form-control" style="max-width: 280px;" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">

        <select name="category" class="admin-form-control" style="max-width: 200px;" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($cat_list as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= ($category === $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">Filter</button>
        <?php if (!empty($search) || !empty($category)): ?>
            <a href="products.php" class="admin-btn admin-btn-outline admin-btn-sm">Clear</a>
        <?php endif; ?>
    </form>

    <div style="font-size: 13px; color: var(--admin-muted);">
        Total: <strong><?= count($products) ?></strong> products
    </div>
</div>

<!-- Products Table -->
<div class="card-table">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Thumbnail</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Rating</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>#<?= $p['id'] ?></td>
                        <td>
                            <img src="<?= htmlspecialchars($p['image']) ?>" alt="" class="admin-product-thumb">
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($p['name']) ?></strong>
                            <?php if ($p['is_featured']): ?>
                                <span style="font-size: 10px; background: #fce4ec; color: #d81b60; padding: 2px 6px; border-radius: 3px; margin-left: 4px;">Featured</span>
                            <?php endif; ?>
                            <?php if ($p['is_bestseller']): ?>
                                <span style="font-size: 10px; background: #fff3e0; color: #e65100; padding: 2px 6px; border-radius: 3px; margin-left: 4px;">Best Seller</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['category']) ?></td>
                        <td>
                            <strong><?= format_price($p['discount_price'] ?? $p['price']) ?></strong>
                            <?php if (!empty($p['discount_price']) && $p['discount_price'] < $p['price']): ?>
                                <div style="font-size: 11px; text-decoration: line-through; color: var(--admin-muted);">
                                    <?= format_price($p['price']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['stock'] > 10): ?>
                                <span class="admin-badge admin-badge-active"><?= $p['stock'] ?> in stock</span>
                            <?php elseif ($p['stock'] > 0): ?>
                                <span class="admin-badge" style="background: #fff3e0; color: #e65100;"><?= $p['stock'] ?> low stock</span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge-inactive">Out of stock (0)</span>
                            <?php endif; ?>
                        </td>
                        <td>★ <?= number_format($p['rating'], 1) ?></td>
                        <td>
                            <a href="products.php?action=toggle_status&id=<?= $p['id'] ?>" class="admin-badge <?= ($p['status'] === 'Active') ? 'admin-badge-active' : 'admin-badge-inactive' ?>" title="Click to toggle status">
                                <?= htmlspecialchars($p['status']) ?>
                            </a>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <a href="edit_product.php?id=<?= $p['id'] ?>" class="admin-btn admin-btn-outline admin-btn-sm" title="Edit">
                                    ✏️ Edit
                                </a>
                                <a href="delete_product.php?id=<?= $p['id'] ?>" class="admin-btn admin-btn-danger admin-btn-sm" onclick="return confirmAdminDelete('Are you sure you want to permanently delete \'<?= addslashes($p['name']) ?>\'?');" title="Delete">
                                    🗑️
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px; color: var(--admin-muted);">
                        No products match your criteria.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
