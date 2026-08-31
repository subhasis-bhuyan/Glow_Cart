<?php
/**
 * GlowCart Cosmetics - Add New Product
 */
require_once __DIR__ . '/../config/db.php';
require_admin('login.php');

$admin_page_title = 'Add New Product | GlowCart Admin';
$errors = [];

$name = '';
$category = 'Lipstick';
$description = '';
$price = '';
$discount_price = '';
$stock = '20';
$image = 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=600&q=80';
$rating = '4.8';
$is_featured = 0;
$is_bestseller = 0;
$status = 'Active';

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
            $stmt = $pdo->prepare("
                INSERT INTO products (name, category, description, price, discount_price, stock, image, rating, is_featured, is_bestseller, status, created_at)
                VALUES (:name, :category, :description, :price, :discount_price, :stock, :image, :rating, :is_featured, :is_bestseller, :status, NOW())
            ");
            $stmt->execute([
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
                ':status'         => $status
            ]);

            $_SESSION['admin_flash_success'] = "Product '{$name}' created successfully!";
            header('Location: products.php');
            exit;

        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$categories = ['Lipstick', 'Foundation', 'Blush', 'Eyeshadow', 'Mascara', 'Skincare', 'Makeup Kits', 'Accessories'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Add New Product</h1>
        <p style="color: var(--admin-muted); font-size: 13px;">Create and publish a new cosmetics item in the catalog.</p>
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
    <form action="add_product.php" method="POST">
        <div class="admin-form-group">
            <label for="name">Product Title *</label>
            <input type="text" id="name" name="name" class="admin-form-control" placeholder="e.g. Ultra Satin Rose Lipstick" value="<?= htmlspecialchars($name) ?>" required>
        </div>

        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" class="admin-form-control" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($category === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-form-group">
                <label for="status">Catalog Status</label>
                <select id="status" name="status" class="admin-form-control">
                    <option value="Active" <?= ($status === 'Active') ? 'selected' : '' ?>>Active (Visible)</option>
                    <option value="Inactive" <?= ($status === 'Inactive') ? 'selected' : '' ?>>Inactive (Hidden)</option>
                </select>
            </div>
        </div>

        <div class="admin-form-group">
            <label for="description">Product Description *</label>
            <textarea id="description" name="description" class="admin-form-control" rows="4" placeholder="Detailed product formula, ingredients, and application tips..." required><?= htmlspecialchars($description) ?></textarea>
        </div>

        <div class="admin-form-row" style="grid-template-columns: 1fr 1fr 1fr;">
            <div class="admin-form-group">
                <label for="price">Regular Price (₹) *</label>
                <input type="number" id="price" name="price" class="admin-form-control" step="0.01" min="1" placeholder="899.00" value="<?= htmlspecialchars($price) ?>" required>
            </div>

            <div class="admin-form-group">
                <label for="discount_price">Discounted Price (₹)</label>
                <input type="number" id="discount_price" name="discount_price" class="admin-form-control" step="0.01" min="0" placeholder="699.00" value="<?= htmlspecialchars($discount_price) ?>">
            </div>

            <div class="admin-form-group">
                <label for="stock">Initial Stock Quantity *</label>
                <input type="number" id="stock" name="stock" class="admin-form-control" min="0" placeholder="25" value="<?= htmlspecialchars($stock) ?>" required>
            </div>
        </div>

        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="image">Image URL (HD Photography) *</label>
                <input type="url" id="image" name="image" class="admin-form-control" placeholder="https://images.unsplash.com/photo-..." value="<?= htmlspecialchars($image) ?>" required>
            </div>

            <div class="admin-form-group">
                <label for="rating">Initial Star Rating (1.0 to 5.0)</label>
                <input type="number" id="rating" name="rating" class="admin-form-control" step="0.1" min="1" max="5" value="<?= htmlspecialchars($rating) ?>">
            </div>
        </div>

        <div style="display: flex; gap: 25px; margin: 15px 0 25px;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="is_featured" value="1" <?= $is_featured ? 'checked' : '' ?> style="accent-color: var(--admin-primary); width: 16px; height: 16px;">
                <span>Mark as Featured Product (Home Page)</span>
            </label>

            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="is_bestseller" value="1" <?= $is_bestseller ? 'checked' : '' ?> style="accent-color: var(--admin-primary); width: 16px; height: 16px;">
                <span>Mark as Best Seller</span>
            </label>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="admin-btn admin-btn-primary" style="padding: 10px 24px;">
                ➕ Create & Publish Product
            </button>
            <a href="products.php" class="admin-btn admin-btn-outline" style="padding: 10px 20px;">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
