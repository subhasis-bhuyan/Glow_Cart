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
                    <button type="button" class="btn btn-outline btn-lg" onclick="shareCurrentProduct('<?= addslashes($product['name']) ?>', 'Check out <?= addslashes($product['name']) ?> on GlowCart!')" title="Share product">
                        📤 Share
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
                    <button type="button" class="btn btn-outline btn-lg" onclick="shareCurrentProduct('<?= addslashes($product['name']) ?>', 'Check out <?= addslashes($product['name']) ?> on GlowCart!')" title="Share product">
                        📤 Share
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

    <!-- Interactive Tabs Accordion Section -->
    <div class="detail-tabs-wrapper">
        <div class="detail-tabs-nav" role="tablist">
            <button type="button" class="detail-tab-btn active" data-tab-target="tabOverview" role="tab">
                ✨ Overview & Key Benefits
            </button>
            <button type="button" class="detail-tab-btn" data-tab-target="tabIngredients" role="tab">
                🌿 Clean Ingredients
            </button>
            <button type="button" class="detail-tab-btn" data-tab-target="tabHowToUse" role="tab">
                💡 How to Apply
            </button>
            <button type="button" class="detail-tab-btn" data-tab-target="tabReviews" role="tab">
                ⭐ Verified Reviews (128)
            </button>
        </div>

        <div class="detail-tab-pane active" id="tabOverview" role="tabpanel">
            <h3 style="font-size: 20px; margin-bottom: 12px;">Why You Will Love It</h3>
            <p style="margin-bottom: 16px; font-size: 15px; color: var(--text-main);">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 20px;">
                <div style="background: var(--surface-alt); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <strong style="color: var(--primary); font-size: 15px; display: block; margin-bottom: 4px;">⏰ 24-Hour Long Wear</strong>
                    <span style="font-size: 13px; color: var(--text-muted);">Smudge-proof, transfer-resistant formula designed for all-day radiance.</span>
                </div>
                <div style="background: var(--surface-alt); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <strong style="color: var(--primary); font-size: 15px; display: block; margin-bottom: 4px;">🌱 Clean & Vegan Formula</strong>
                    <span style="font-size: 13px; color: var(--text-muted);">Free from parabens, phthalates, sulfates, and synthetic fragrances.</span>
                </div>
                <div style="background: var(--surface-alt); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <strong style="color: var(--primary); font-size: 15px; display: block; margin-bottom: 4px;">💧 Hydrating Botanical Infusion</strong>
                    <span style="font-size: 13px; color: var(--text-muted);">Enriched with active antioxidants, Vitamin E, and organic botanicals.</span>
                </div>
            </div>
        </div>

        <div class="detail-tab-pane" id="tabIngredients" role="tabpanel">
            <h3 style="font-size: 20px; margin-bottom: 12px;">Formulated With Pure Skin-Loving Ingredients</h3>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 15px;">
                We hold our formulas to the highest ethical and clinical cosmetic standards. Every batch is 100% cruelty-free, PETA-certified, and ethically sourced.
            </p>
            <div style="background: var(--surface-alt); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); font-size: 13.5px; line-height: 1.8;">
                <strong>Hero Active Ingredients:</strong> Cold-pressed Organic Jojoba Seed Oil, Pure Vitamin E (Tocopherol Acetate), Sodium Hyaluronate (Multi-molecular weight Hyaluronic Acid), Shea Butter, Plant-derived Squalane, Damask Rose Floral Extract, Niacinamide (Vitamin B3), Microfine Mineral Pigments.
            </div>
        </div>

        <div class="detail-tab-pane" id="tabHowToUse" role="tabpanel">
            <h3 style="font-size: 20px; margin-bottom: 12px;">Pro Makeup Artist Application Guide</h3>
            <ol style="margin-left: 20px; line-height: 2; font-size: 14.5px; color: var(--text-main);">
                <li><strong>Prep & Prime:</strong> Start with clean, moisturized skin. Lightly dab on lip balm or hydrating face mist.</li>
                <li><strong>Apply Gently:</strong> Glide the applicator evenly from the center outward along your natural contours.</li>
                <li><strong>Build & Blend:</strong> Layer for higher pigmentation or blend gently with fingertip or brush for an effortless daytime glow.</li>
                <li><strong>Set & Lock:</strong> Allow 30 seconds to settle for transfer-resistant, comfortable all-day hold.</li>
            </ol>
        </div>

        <div class="detail-tab-pane" id="tabReviews" role="tabpanel">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
                <div>
                    <div style="font-size: 32px; font-weight: 700; color: var(--text-main); line-height: 1;">
                        <?= number_format($product['rating'], 1) ?> <span style="font-size: 18px; color: var(--text-muted); font-weight: 400;">/ 5.0</span>
                    </div>
                    <div style="color: #fbc02d; font-size: 18px; margin: 4px 0;">★ ★ ★ ★ ★</div>
                    <span style="font-size: 13px; color: var(--text-muted);">Based on 128 verified customer reviews</span>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="background: var(--surface-alt); padding: 18px 20px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <strong>Priya S.</strong>
                        <span style="font-size: 12px; color: var(--text-muted);">Verified Purchase &bull; 2 days ago</span>
                    </div>
                    <div style="color: #fbc02d; font-size: 13px; margin-bottom: 6px;">★★★★★</div>
                    <p style="font-size: 13.5px; margin: 0;">"Absolutely obsessed with the formula! It feels weightless on the skin and lasts through meals without drying or cracking."</p>
                </div>

                <div style="background: var(--surface-alt); padding: 18px 20px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <strong>Ananya D.</strong>
                        <span style="font-size: 12px; color: var(--text-muted);">Verified Purchase &bull; 1 week ago</span>
                    </div>
                    <div style="color: #fbc02d; font-size: 13px; margin-bottom: 6px;">★★★★★</div>
                    <p style="font-size: 13.5px; margin: 0;">"The pigmentation is so rich and buttery! GlowCart shipping was super fast and arrived in pristine luxury packaging."</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky Mobile Purchase Bar -->
    <?php if ($product['stock'] > 0): ?>
        <div class="detail-sticky-bar" id="detailStickyBar">
            <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: var(--radius-sm); flex-shrink: 0;">
                <div style="min-width: 0;">
                    <div style="font-size: 12.5px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($product['name']) ?></div>
                    <strong style="color: var(--primary); font-size: 14px;"><?= format_price($effective_price) ?></strong>
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="handleDetailAddToCart()" style="flex-shrink: 0; padding: 9px 18px;">
                🛍️ Add to Cart
            </button>
        </div>
    <?php endif; ?>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <section class="section" style="padding-top: 40px;">
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
                            <div class="product-actions-overlay">
                                <a href="product_details.php?id=<?= $rel['id'] ?>" class="action-icon-btn action-qv-btn" data-quickview-id="<?= $rel['id'] ?>" title="Quick View">👁️</a>
                                <button type="button" 
                                        class="action-icon-btn action-fav-btn <?= $rel_is_fav ? 'active' : '' ?>" 
                                        onclick="toggleFavorite(<?= $rel['id'] ?>, this)" 
                                        title="<?= $rel_is_fav ? 'Remove from Favorites' : 'Add to Favorites' ?>"
                                        data-product-id="<?= $rel['id'] ?>">
                                    <?= $rel_is_fav ? '❤️' : '🤍' ?>
                                </button>
                                <?php if ($rel['stock'] > 0): ?>
                                    <button type="button" class="action-icon-btn" onclick="addToCart(<?= $rel['id'] ?>, 1)" title="Add to Cart">🛍️</button>
                                <?php endif; ?>
                            </div>
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
