<?php
/**
 * GlowCart Cosmetics - Add New Product Form & Handler
 */
require_once __DIR__ . '/auth_check.php';

$admin_page_title = 'Add New Product | GlowCart Admin';
$admin_header_title = 'Add Product to Catalog';
$active_tab = 'product_add';

$errors = [];
$name = '';
$category = 'Lipstick';
$custom_category = '';
$description = '';
$price = '';
$discount_price = '';
$stock = '25';
$image = 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80';
$rating = '4.5';
$is_featured = 0;
$is_bestseller = 0;
$status = 'Active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name'] ?? '');
    $category_select = clean_input($_POST['category'] ?? '');
    $custom_category = clean_input($_POST['custom_category'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $price = clean_input($_POST['price'] ?? '');
    $discount_price = clean_input($_POST['discount_price'] ?? '');
    $stock = clean_input($_POST['stock'] ?? '');
    $image = clean_input($_POST['image'] ?? '');
    $rating = (float)($_POST['rating'] ?? 4.5);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;
    $status = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';

    // Resolve category
    $final_category = ($category_select === '__custom__' && !empty($custom_category)) 
        ? $custom_category 
        : $category_select;

    // Validation
    if (empty($name)) {
        $errors[] = "Product title/name is required.";
    }
    if (empty($final_category)) {
        $errors[] = "Please select or specify a category.";
    }
    if (!is_numeric($price) || (float)$price <= 0) {
        $errors[] = "Price must be a valid positive number.";
    }
    if (!empty($discount_price) && (!is_numeric($discount_price) || (float)$discount_price < 0)) {
        $errors[] = "Discount price must be a valid positive number.";
    }
    if (!empty($discount_price) && is_numeric($price) && (float)$discount_price >= (float)$price) {
        $errors[] = "Discount price must be less than the regular price.";
    }
    if (!is_numeric($stock) || (int)$stock < 0) {
        $errors[] = "Inventory stock must be zero or a positive whole number.";
    }

    // Handle Local Image Upload if provided
    if (isset($_FILES['product_image_file']) && $_FILES['product_image_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['product_image_file']['tmp_name'];
        $file_name = $_FILES['product_image_file']['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($file_ext, $allowed)) {
            $upload_dir = __DIR__ . '/../assets/images/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_file_name = 'prod_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $image = 'assets/images/products/' . $new_file_name;
            }
        } else {
            $errors[] = "Invalid image file format. Allowed formats: JPG, PNG, WEBP, GIF.";
        }
    }

    if (empty($image)) {
        $image = 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products (name, category, description, price, discount_price, stock, image, rating, is_featured, is_bestseller, status, created_at)
                VALUES (:name, :category, :description, :price, :discount_price, :stock, :image, :rating, :is_featured, :is_bestseller, :status, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                ':name'           => $name,
                ':category'       => $final_category,
                ':description'    => $description,
                ':price'          => (float)$price,
                ':discount_price' => !empty($discount_price) ? (float)$discount_price : null,
                ':stock'          => (int)$stock,
                ':image'          => $image,
                ':rating'         => $rating,
                ':is_featured'    => $is_featured,
                ':is_bestseller'  => $is_bestseller,
                ':status'         => $status
            ]);

            $_SESSION['flash_success'] = "Product '{$name}' was successfully added to the catalog!";
            header('Location: products.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Database insert error: " . $e->getMessage();
        }
    }
}

// Fetch existing categories for dropdown
try {
    $cat_stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category ASC");
    $existing_categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN) ?: [
        'Lipstick', 'Foundation', 'Blush', 'Eyeshadow', 'Mascara', 'Skincare', 'Makeup Kits', 'Accessories'
    ];
} catch (PDOException $e) {
    $existing_categories = ['Lipstick', 'Foundation', 'Blush', 'Eyeshadow', 'Mascara', 'Skincare', 'Makeup Kits', 'Accessories'];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/includes/topbar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Add New Product</h1>
        <p style="color: var(--admin-muted); font-size: 13px; margin-top: 4px;">Fill in details to list a new cosmetic or beauty item.</p>
    </div>
    <div>
        <a href="products.php" class="admin-btn admin-btn-outline">
            &larr; Back to Catalog
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="admin-alert admin-alert-danger">
        <div>
            <strong>Please correct the following errors:</strong>
            <ul style="margin-left: 20px; margin-top: 4px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="admin-form-card">
    <form action="product_add.php" method="POST" enctype="multipart/form-data">
        <!-- Product Title -->
        <div class="admin-form-group">
            <label for="name">Product Name *</label>
            <input type="text" id="name" name="name" class="admin-form-control" placeholder="e.g. Velvet Rose Matte Lipstick" value="<?= htmlspecialchars($name) ?>" required>
        </div>

        <div class="admin-form-row">
            <!-- Category -->
            <div class="admin-form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" class="admin-form-control" onchange="toggleCustomCategory(this.value)" required>
                    <?php foreach ($existing_categories as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= ($category === $c) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="__custom__" <?= ($category === '__custom__') ? 'selected' : '' ?>>+ Add New Category...</option>
                </select>
                <input type="text" id="custom_category" name="custom_category" class="admin-form-control" placeholder="Enter new category name..." value="<?= htmlspecialchars($custom_category) ?>" style="margin-top: 8px; display: <?= ($category === '__custom__') ? 'block' : 'none' ?>;">
            </div>

            <!-- Inventory Stock -->
            <div class="admin-form-group">
                <label for="stock">Inventory Stock Quantity *</label>
                <input type="number" id="stock" name="stock" class="admin-form-control" placeholder="25" min="0" value="<?= htmlspecialchars($stock) ?>" required>
            </div>
        </div>

        <div class="admin-form-row">
            <!-- Regular Price -->
            <div class="admin-form-group">
                <label for="price">Regular Price (₹) *</label>
                <input type="number" step="0.01" id="price" name="price" class="admin-form-control" placeholder="899.00" value="<?= htmlspecialchars($price) ?>" required>
            </div>

            <!-- Discount Price -->
            <div class="admin-form-group">
                <label for="discount_price">Discount / Sale Price (₹) <span style="color: var(--admin-muted); font-weight: normal;">(Optional)</span></label>
                <input type="number" step="0.01" id="discount_price" name="discount_price" class="admin-form-control" placeholder="699.00" value="<?= htmlspecialchars($discount_price) ?>">
            </div>
        </div>

        <!-- Description -->
        <div class="admin-form-group">
            <label for="description">Description & Beauty Benefits</label>
            <textarea id="description" name="description" class="admin-form-control" rows="4" placeholder="Describe the formula, ingredients (e.g. Vitamin E, Jojoba Oil), finish, and wear time..."><?= htmlspecialchars($description) ?></textarea>
        </div>

        <!-- Image URL & File Upload -->
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="image">Image Web URL (Unsplash or Direct Link)</label>
                <input type="url" id="image" name="image" class="admin-form-control" placeholder="https://images.unsplash.com/..." value="<?= htmlspecialchars($image) ?>" oninput="updateImagePreview(this.value)">
                <div style="font-size: 11px; color: var(--admin-muted); margin-top: 4px;">Or upload an image file below:</div>
                <input type="file" name="product_image_file" class="admin-form-control" accept="image/*" style="margin-top: 4px;">
            </div>

            <div class="admin-form-group" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <label style="align-self: flex-start;">Image Preview</label>
                <div class="admin-image-preview">
                    <img id="imgPreview" src="<?= htmlspecialchars($image) ?>" alt="Preview" onerror="this.src='https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=200';">
                </div>
            </div>
        </div>

        <div class="admin-form-row">
            <!-- Customer Rating -->
            <div class="admin-form-group">
                <label for="rating">Star Rating (1.0 to 5.0)</label>
                <input type="number" step="0.1" min="1.0" max="5.0" id="rating" name="rating" class="admin-form-control" value="<?= htmlspecialchars($rating) ?>">
            </div>

            <!-- Status -->
            <div class="admin-form-group">
                <label for="status">Catalog Visibility Status</label>
                <select id="status" name="status" class="admin-form-control">
                    <option value="Active" <?= ($status === 'Active') ? 'selected' : '' ?>>Active (Visible on store)</option>
                    <option value="Inactive" <?= ($status === 'Inactive') ? 'selected' : '' ?>>Inactive (Hidden from shoppers)</option>
                </select>
            </div>
        </div>

        <!-- Badges / Flags -->
        <div style="display: flex; gap: 25px; margin-bottom: 25px; padding: 15px; background: #f8fafc; border-radius: 6px; border: 1px solid var(--admin-border);">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;">
                <input type="checkbox" name="is_featured" value="1" <?= ($is_featured == 1) ? 'checked' : '' ?> style="accent-color: var(--admin-primary); width: 16px; height: 16px;">
                <span>⭐ Feature on Homepage</span>
            </label>

            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;">
                <input type="checkbox" name="is_bestseller" value="1" <?= ($is_bestseller == 1) ? 'checked' : '' ?> style="accent-color: var(--admin-primary); width: 16px; height: 16px;">
                <span>🔥 Mark as Bestseller</span>
            </label>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 12px;">
            <button type="submit" class="admin-btn admin-btn-primary" style="padding: 10px 24px;">
                <span>💾</span> Save & Publish Product
            </button>
            <a href="products.php" class="admin-btn admin-btn-outline" style="padding: 10px 20px;">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
function toggleCustomCategory(val) {
    const customInput = document.getElementById('custom_category');
    if (val === '__custom__') {
        customInput.style.display = 'block';
        customInput.required = true;
        customInput.focus();
    } else {
        customInput.style.display = 'none';
        customInput.required = false;
    }
}

function updateImagePreview(url) {
    const preview = document.getElementById('imgPreview');
    if (url && url.trim() !== '') {
        preview.src = url.trim();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
