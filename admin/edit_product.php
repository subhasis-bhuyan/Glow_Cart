<?php
/**
 * GlowCart Cosmetics - Edit Product Details & Stock
 */
require_once __DIR__ . '/../config/db.php';
require_admin('login.php');

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        $_SESSION['admin_flash_error'] = 'Product not found.';
        header('Location: products.php');
        exit;
    }
} catch (PDOException $e) {
    die("Database query error.");
}

$admin_page_title = 'Edit Product #' . $product_id . ' | GlowCart Admin';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = clean_input($_POST['name'] ?? '');
    $category       = clean_input($_POST['category'] ?? '');
    $description    = clean_input($_POST['description'] ?? '');
    $price          = (float)($_POST['price'] ?? 0);
    $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
    $stock          = (int)($_POST['stock'] ?? 0);
    $image          = clean_input($_POST['image'] ?? '');
    $rating         = (float)($_POST['rating'] ?? 4.5);
    $is_featured    = isset($_POST['is_featured']) ? 1 : 0;
    $is_bestseller  = isset($_POST['is_bestseller']) ? 1 : 0;
    $status         = clean_input($_POST['status'] ?? 'Active');

    if (empty($name)) $errors[] = 'Product name is required.';
    if (empty($category)) $errors[] = 'Category is required.';
    if (empty($description)) $errors[] = 'Description is required.';
    if ($price <= 0) $errors[] = 'Price must be greater than zero.';
    if ($stock < 0) $errors[] = 'Stock cannot be negative.';
    if (empty($image)) $errors[] = 'Product image URL is required.';

    if (empty($errors)) {
        try {
            $upd = $pdo->prepare("
                UPDATE products 
                SET name = :name, category = :category, description = :description, price = :price, discount_price = :discount_price,
                    stock = :stock, image = :image, rating = :rating, is_featured = :is_featured, is_bestseller = :is_bestseller, status = :status
                WHERE id = :id
            ");
            $upd->execute([
                ':name'           => $name,
                ':category'       => $category,
                ':description'    => $description,
                ':price'          => $price,
                ':discount_price' => $discount_price,
                ':stock'          => $stock,
                ':image'          => $image,
                ':rating'         => $rating,
                ':is_featured'    => $is_featured,
                ':is_bestseller'  => $is_bestseller,
                ':status'         => $status,
                ':id'             => $product_id
            ]);

            $_SESSION['admin_flash_success'] = "Product '{$name}' updated successfully!";
            header('Location: products.php');
            exit;

        } catch (PDOException $e) {
            $errors[] = 'Database update error: ' . $e->getMessage();
        }
    }
}

$categories = ['Lipstick', 'Foundation', 'Blush', 'Eyeshadow', 'Mascara', 'Skincare', 'Makeup Kits', 'Accessories'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Product #<?= $product['id'] ?></h1>
        <p style="color: var(--admin-muted); font-size: 13px;">Modify pricing, inventory stock, or description.</p>
    </div>
    <a href="products.php" class="admin-btn admin-btn-outline">&larr; Back to Products</a>
</div>

<?php if (!empty($errors)): ?>
    <div style="background: #ffebee; color: #c62828; padding: 14px 18px; border-radius: 6px; margin-bottom: 25px; font-size: 13px;">
        <ul style="margin-left: 18px;">
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-form-card">
    <form action="edit_product.php?id=<?= $product['id'] ?>" method="POST">
        <div class="admin-form-group">
            <label for="name">Product Title *</label>
            <input type="text" id="name" name="name" class="admin-form-control" value="<?= htmlspecialchars($_POST['name'] ?? $product['name']) ?>" required>
        </div>

        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" class="admin-form-control" required>
                    <?php 
                    $current_cat = $_POST['category'] ?? $product['category'];
                    foreach ($categories as $cat): 
                    ?>
                        <option value="<?= $cat ?>" <?= ($current_cat === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-form-group">
                <label for="status">Catalog Status</label>
                <?php $current_status = $_POST['status'] ?? $product['status']; ?>
                <select id="status" name="status" class="admin-form-control">
                    <option value="Active" <?= ($current_status === 'Active') ? 'selected' : '' ?>>Active (Visible in Store)</option>
                    <option value="Inactive" <?= ($current_status === 'Inactive') ? 'selected' : '' ?>>Inactive (Hidden)</option>
                </select>
            </div>
        </div>

        <div class="admin-form-group">
            <label for="description">Product Description *</label>
            <textarea id="description" name="description" class="admin-form-control" rows="4" required><?= htmlspecialchars($_POST['description'] ?? $product['description']) ?></textarea>
        </div>

        <div class="admin-form-row" style="grid-template-columns: 1fr 1fr 1fr;">
            <div class="admin-form-group">
                <label for="price">Regular Price (₹) *</label>
                <input type="number" id="price" name="price" class="admin-form-control" step="0.01" min="1" value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>" required>
            </div>

            <div class="admin-form-group">
                <label for="discount_price">Discounted Price (₹)</label>
                <input type="number" id="discount_price" name="discount_price" class="admin-form-control" step="0.01" min="0" value="<?= htmlspecialchars($_POST['discount_price'] ?? $product['discount_price']) ?>">
            </div>

            <div class="admin-form-group">
                <label for="stock">Current Stock Quantity *</label>
                <input type="number" id="stock" name="stock" class="admin-form-control" min="0" value="<?= htmlspecialchars($_POST['stock'] ?? $product['stock']) ?>" required>
            </div>
        </div>

        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="image">Image URL *</label>
                <input type="url" id="image" name="image" class="admin-form-control" value="<?= htmlspecialchars($_POST['image'] ?? $product['image']) ?>" required>
            </div>

            <div class="admin-form-group">
                <label for="rating">Star Rating (1.0 to 5.0)</label>
                <input type="number" id="rating" name="rating" class="admin-form-control" step="0.1" min="1" max="5" value="<?= htmlspecialchars($_POST['rating'] ?? $product['rating']) ?>">
            </div>
        </div>

        <div style="display: flex; gap: 25px; margin: 15px 0 25px;">
            <?php 
            $is_feat = isset($_POST['is_featured']) ? 1 : $product['is_featured'];
            $is_best = isset($_POST['is_bestseller']) ? 1 : $product['is_bestseller'];
            ?>
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="is_featured" value="1" <?= $is_feat ? 'checked' : '' ?> style="accent-color: var(--admin-primary); width: 16px; height: 16px;">
                <span>Mark as Featured Product</span>
            </label>

            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="is_bestseller" value="1" <?= $is_best ? 'checked' : '' ?> style="accent-color: var(--admin-primary); width: 16px; height: 16px;">
                <span>Mark as Best Seller</span>
            </label>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="admin-btn admin-btn-primary" style="padding: 10px 24px;">
                💾 Save Product Changes
            </button>
            <a href="products.php" class="admin-btn admin-btn-outline" style="padding: 10px 20px;">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
