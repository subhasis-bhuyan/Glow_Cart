<?php
/**
 * GlowCart Cosmetics - Product Details Page
 */
require_once __DIR__ . '/config/db.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id AND status = 'Active'");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        die("<div style='font-family:sans-serif;text-align:center;padding:50px;'><h2>Product Not Found</h2><p><a href='products.php'>Return to Shop</a></p></div>");
    }

    // Fetch related products in the same category
    $rel_stmt = $pdo->prepare("SELECT * FROM products WHERE category = :category AND id != :id AND status = 'Active' LIMIT 4");
    $rel_stmt->execute([
        ':category' => $product['category'],
        ':id'       => $product['id']
    ]);
    $related_products = $rel_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database query error.");
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$is_fav = is_logged_in() ? is_product_favorite($user_id, (int)$product['id']) : false;
$user_favorite_ids = is_logged_in() ? get_user_favorite_ids($user_id) : [];

$page_title = htmlspecialchars($product['name']) . ' | GlowCart Cosmetics';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$effective_price = !empty($product['discount_price']) && $product['discount_price'] < $product['price'] ? $product['discount_price'] : $product['price'];
$has_discount = !empty($product['discount_price']) && $product['discount_price'] < $product['price'];
$discount_pct = $has_discount ? round((($product['price'] - $product['discount_price']) / $product['price']) * 100) : 0;
?>

<main class="container">
    <!-- Breadcrumbs -->
    <div style="padding: 20px 0 10px; font-size: 13px; color: var(--text-muted);">
        <a href="index.php">Home</a> &gt; 
        <a href="products.php">Shop</a> &gt; 
        <a href="products.php?category=<?= urlencode($product['category']) ?>"><?= htmlspecialchars($product['category']) ?></a> &gt; 
        <span style="color: var(--text-main); font-weight: 500;"><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <div class="product-detail-layout">
        <!-- Gallery / Image Area -->
        <div class="product-gallery">
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="gallery-main-img" id="mainProductImg">
        </div>

        <!-- Product Purchase Information -->
        <div class="product-detail-info">
            <div class="detail-category"><?= htmlspecialchars($product['category']) ?></div>
            <h1 class="detail-title"><?= htmlspecialchars($product['name']) ?></h1>

            <div class="detail-rating">
                <div style="color: #fbc02d; font-size: 16px;">
                    <?= str_repeat('★', (int)round($product['rating'])) ?><?= str_repeat('☆', 5 - (int)round($product['rating'])) ?>
                </div>
                <strong style="font-size: 14px;"><?= number_format($product['rating'], 1) ?> / 5.0</strong>
                <span style="color: var(--text-muted); font-size: 13px;">(128 Verified Ratings)</span>
            </div>

            <!-- Price Breakdown -->
            <div class="detail-price-box">
                <div class="detail-price-row">
                    <span class="detail-price"><?= format_price($effective_price) ?></span>
                    <?php if ($has_discount): ?>
                        <span class="detail-original-price"><?= format_price($product['price']) ?></span>
                        <span class="detail-discount-tag"><?= $discount_pct ?>% OFF</span>
                    <?php endif; ?>
                </div>
                <small style="display: block; margin-top: 6px; color: var(--text-muted);">Inclusive of all taxes. Free shipping applied on orders above ₹500.</small>
            </div>

            <p class="detail-desc">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </p>

            <!-- Stock Status -->
            <div class="detail-stock-row">
                <strong style="font-size: 13px;">Availability:</strong>
                <?php if ($product['stock'] > 0): ?>
                    <span class="badge badge-in-stock">✓ In Stock (<?= $product['stock'] ?> units available)</span>
                <?php else: ?>
                    <span class="badge badge-out-of-stock">✕ Out of Stock</span>
                <?php endif; ?>
            </div>

            <!-- Add to Cart & Quantity Selector -->
            <?php if ($product['stock'] > 0): ?>
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 13px; font-weight: 500; margin-bottom: 8px;">Quantity:</label>
                    <div class="quantity-control">
                        <button type="button" class="qty-btn qty-minus">-</button>
                        <input type="number" id="detailQty" class="qty-input" value="1" min="1" max="<?= $product['stock'] ?>" readonly>
                        <button type="button" class="qty-btn qty-plus">+</button>
                    </div>
                </div>

                <div class="detail-actions" style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-primary btn-lg" onclick="handleDetailAddToCart()">
                        🛍️ Add to Cart
                    </button>
                    <button type="button" class="btn btn-secondary btn-lg" onclick="handleDetailBuyNow()">
                        ⚡ Buy Now
                    </button>
                    <button type="button" 
                            class="btn btn-outline btn-lg detail-fav-btn <?= $is_fav ? 'active' : '' ?>" 
                            id="detailFavBtn" 
                            onclick="toggleFavorite(<?= $product['id'] ?>, this)"
                            data-product-id="<?= $product['id'] ?>"
                            title="<?= $is_fav ? 'Remove from Favorites' : 'Add to Favorites' ?>">
                        <span class="fav-heart-icon"><?= $is_fav ? '❤️' : '🤍' ?></span> <span class="fav-btn-label"><?= $is_fav ? 'In Favorites' : 'Add to Favorites' ?></span>
                    </button>
                </div>
            <?php else: ?>
                <div style="background: var(--danger-bg); color: var(--danger); padding: 15px 20px; border-radius: var(--radius-md); margin-bottom: 25px; border: 1px solid rgba(198,40,40,0.2);">
                    <strong>This product is currently out of stock.</strong>
                    <p style="font-size: 13px; margin: 4px 0 0;">Please check back later or explore similar beauty items below.</p>
                </div>
                <div class="detail-actions" style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-outline btn-lg disabled" disabled>
                        ✕ Out of Stock
                    </button>
                    <button type="button" 
                            class="btn btn-outline btn-lg detail-fav-btn <?= $is_fav ? 'active' : '' ?>" 
                            id="detailFavBtn" 
                            onclick="toggleFavorite(<?= $product['id'] ?>, this)"
                            data-product-id="<?= $product['id'] ?>"
                            title="<?= $is_fav ? 'Remove from Favorites' : 'Add to Favorites' ?>">
                        <span class="fav-heart-icon"><?= $is_fav ? '❤️' : '🤍' ?></span> <span class="fav-btn-label"><?= $is_fav ? 'In Favorites' : 'Add to Favorites' ?></span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Beauty Trust Badges -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding-top: 20px; border-top: 1px solid var(--border-color); font-size: 13px;">
                <div>🌿 100% Organic & Cruelty-Free</div>
                <div>✨ Dermatologically Tested</div>
                <div>🔄 7 Days Easy Return Policy</div>
                <div>🔒 100% Secure Checkout</div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <section class="section" style="padding-top: 20px;">
            <div class="section-header">
                <div class="section-subtitle">You May Also Like</div>
                <h2 class="section-title">Related Beauty Items</h2>
            </div>

            <div class="products-grid">
                <?php foreach ($related_products as $rel): ?>
                    <?php $rel_is_fav = in_array((int)$rel['id'], $user_favorite_ids); ?>
                    <div class="product-card">
                        <div class="product-thumb">
                            <img src="<?= htmlspecialchars($rel['image']) ?>" alt="<?= htmlspecialchars($rel['name']) ?>" loading="lazy">
                            <button type="button" 
                                    class="card-fav-btn <?= $rel_is_fav ? 'active' : '' ?>" 
                                    onclick="toggleFavorite(<?= $rel['id'] ?>, this)" 
                                    title="<?= $rel_is_fav ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                                    data-product-id="<?= $rel['id'] ?>">
                                <?= $rel_is_fav ? '❤️' : '🤍' ?>
                            </button>
                        </div>
                        <div class="product-info">
                            <div class="product-category"><?= htmlspecialchars($rel['category']) ?></div>
                            <h3 class="product-title">
                                <a href="product_details.php?id=<?= $rel['id'] ?>"><?= htmlspecialchars($rel['name']) ?></a>
                            </h3>
                            <div class="product-price-stock">
                                <span class="current-price"><?= format_price($rel['discount_price'] ?? $rel['price']) ?></span>
                                <a href="product_details.php?id=<?= $rel['id'] ?>" class="btn btn-outline btn-sm">View</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<script>
function handleDetailAddToCart() {
    const qty = parseInt(document.getElementById('detailQty').value) || 1;
    addToCart(<?= $product['id'] ?>, qty);
}

function handleDetailBuyNow() {
    const qty = parseInt(document.getElementById('detailQty').value) || 1;
    addToCart(<?= $product['id'] ?>, qty, (success) => {
        if (success) {
            window.location.href = 'checkout.php';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
