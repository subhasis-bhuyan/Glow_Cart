<?php
/**
 * GlowCart Cosmetics - Home Page
 */
$page_title = 'GlowCart Cosmetics | Discover Your Perfect Glow';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$user_favorite_ids = is_logged_in() ? get_user_favorite_ids((int)$_SESSION['user_id']) : [];

// Fetch Featured Products
try {
    $featured_stmt = $pdo->prepare("SELECT * FROM products WHERE is_featured = 1 AND status = 'Active' ORDER BY id DESC LIMIT 4");
    $featured_stmt->execute();
    $featured_products = $featured_stmt->fetchAll();
} catch (PDOException $e) {
    $featured_products = [];
}

// Fetch Best Seller Products
try {
    $bestseller_stmt = $pdo->prepare("SELECT * FROM products WHERE is_bestseller = 1 AND status = 'Active' ORDER BY id ASC LIMIT 4");
    $bestseller_stmt->execute();
    $bestseller_products = $bestseller_stmt->fetchAll();
} catch (PDOException $e) {
    $bestseller_products = [];
}

// Fetch New Arrivals
try {
    $new_stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'Active' ORDER BY id DESC LIMIT 4");
    $new_stmt->execute();
    $new_products = $new_stmt->fetchAll();
} catch (PDOException $e) {
    $new_products = [];
}
?>

<main>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-grid">
                <div class="hero-content">
                    <div class="hero-tag">✨ NEW SUMMER BEAUTY COLLECTION 2026</div>
                    <h1 class="hero-title">Discover Your <span>Perfect Glow</span></h1>
                    <p class="hero-subtitle">Premium makeup and beauty products for every look. Formulated with skin-loving clean ingredients, rich pigmentation, and 24-hour long wear.</p>
                    
                    <div class="hero-btns">
                        <a href="products.php" class="btn btn-primary btn-lg">🛍️ SHOP NOW</a>
                        <a href="#categories" class="btn btn-secondary btn-lg">EXPLORE COLLECTION</a>
                    </div>

                    <div class="hero-features">
                        <div class="hero-feature-item">
                            <div class="hero-feature-icon">🌿</div>
                            <div>100% Cruelty Free</div>
                        </div>
                        <div class="hero-feature-item">
                            <div class="hero-feature-icon">🚚</div>
                            <div>Free Fast Delivery</div>
                        </div>
                        <div class="hero-feature-item">
                            <div class="hero-feature-icon">✨</div>
                            <div>100% Genuine Products</div>
                        </div>
                    </div>
                </div>

                <div class="hero-image-wrapper">
                    <img src="https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&q=80" alt="GlowCart Cosmetics Luxury Collection" class="hero-image">
                    
                    <div class="hero-floating-card">
                        <div style="font-size: 28px;">💄</div>
                        <div>
                            <strong style="font-size: 14px; display: block; color: var(--text-main);">Luxury Lip Matte</strong>
                            <small style="color: var(--primary); font-weight: 600;">Rated 4.9 ★ (1.2k+ Reviews)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section id="categories" class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-subtitle">Curated For You</div>
                <h2 class="section-title">Shop by Category</h2>
                <p>Explore our wide selection of cosmetic categories designed to highlight your natural radiance.</p>
            </div>

            <div class="category-grid">
                <a href="products.php?category=Lipstick" class="category-card">
                    <div class="category-icon">💄</div>
                    <div class="category-name">Lipstick</div>
                </a>
                <a href="products.php?category=Foundation" class="category-card">
                    <div class="category-icon">🧴</div>
                    <div class="category-name">Foundation</div>
                </a>
                <a href="products.php?category=Blush" class="category-card">
                    <div class="category-icon">🌸</div>
                    <div class="category-name">Blush</div>
                </a>
                <a href="products.php?category=Eyeshadow" class="category-card">
                    <div class="category-icon">🎨</div>
                    <div class="category-name">Eyeshadow</div>
                </a>
                <a href="products.php?category=Mascara" class="category-card">
                    <div class="category-icon">👁️</div>
                    <div class="category-name">Mascara</div>
                </a>
                <a href="products.php?category=Skincare" class="category-card">
                    <div class="category-icon">✨</div>
                    <div class="category-name">Skincare</div>
                </a>
                <a href="products.php?category=Makeup Kits" class="category-card">
                    <div class="category-icon">🎁</div>
                    <div class="category-name">Makeup Kits</div>
                </a>
                <a href="products.php?category=Accessories" class="category-card">
                    <div class="category-icon">🖌️</div>
                    <div class="category-name">Accessories</div>
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="section section-alt">
        <div class="container">
            <div class="section-header">
                <div class="section-subtitle">Handpicked Luxury</div>
                <h2 class="section-title">Featured Products</h2>
                <p>Our top recommended beauty picks crafted with high-definition pigments and all-day comfort.</p>
            </div>

            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
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
                            <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                            <h3 class="product-title">
                                <a href="product_details.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a>
                            </h3>

                            <div class="product-rating">
                                ★ <?= number_format($product['rating'], 1) ?>
                                <span>(Verified Beauty Rating)</span>
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
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="products.php" class="btn btn-outline btn-lg">View All Products &rarr;</a>
            </div>
        </div>
    </section>

    <!-- Promotional Special Offer Banner -->
    <section class="section" style="background: linear-gradient(135deg, #d81b60, #880e4f); color: #ffffff;">
        <div class="container" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 30px;">
            <div style="max-width: 600px;">
                <span class="badge badge-sale" style="margin-bottom: 12px; background: #ffffff; color: #d81b60;">SPECIAL LIMITED TIME DEAL</span>
                <h2 style="font-size: 38px; color: #ffffff; margin-bottom: 15px;">Get 15% Off Your First Order</h2>
                <p style="color: #ffdce6; font-size: 16px; margin-bottom: 20px;">Use coupon code <strong style="background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 4px; color: #ffffff;">GLOW15</strong> at checkout on all makeup kits and serums.</p>
                <a href="products.php?category=Makeup Kits" class="btn btn-secondary btn-lg" style="background: #ffffff; color: #d81b60; border-color: #ffffff;">Shop Festive Kits</a>
            </div>
            <div>
                <img src="https://images.unsplash.com/photo-1516975080664-ed2fc6a32937?auto=format&fit=crop&w=400&q=80" alt="GlowCart Cosmetics Gift Box" style="border-radius: var(--radius-lg); box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 320px;">
            </div>
        </div>
    </section>

    <!-- Best Sellers Section -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <div class="section-subtitle">Customer Favorites</div>
                <h2 class="section-title">Best Sellers</h2>
                <p>The beauty essentials our customers cannot stop raving about.</p>
            </div>

            <div class="products-grid">
                <?php foreach ($bestseller_products as $product): ?>
                    <?php $is_fav = in_array((int)$product['id'], $user_favorite_ids); ?>
                    <div class="product-card">
                        <div class="product-thumb">
                            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                            <div class="product-badges">
                                <span class="badge badge-sale">Best Seller</span>
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
                            <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                            <h3 class="product-title">
                                <a href="product_details.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a>
                            </h3>

                            <div class="product-rating">
                                ★ <?= number_format($product['rating'], 1) ?>
                                <span>(500+ Sold)</span>
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
        </div>
    </section>

    <!-- Customer Reviews / Testimonials -->
    <section class="section section-alt">
        <div class="container">
            <div class="section-header">
                <div class="section-subtitle">Real Experiences</div>
                <h2 class="section-title">What Our Customers Say</h2>
                <p>Join over 10,000+ satisfied beauty enthusiasts across India.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px;">
                <div style="background: var(--surface); padding: 30px; border-radius: var(--radius-md); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                    <div style="color: #fbc02d; margin-bottom: 10px;">★★★★★</div>
                    <p style="font-style: italic; margin-bottom: 15px;">"The Velvet Matte Rose Lipstick stays vibrant all day long without drying my lips. Ordering on this site was so smooth and effortless!"</p>
                    <strong>— Priya Sharma</strong>
                    <div style="font-size: 12px; color: var(--text-muted);">Verified Buyer • Mumbai</div>
                </div>

                <div style="background: var(--surface); padding: 30px; border-radius: var(--radius-md); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                    <div style="color: #fbc02d; margin-bottom: 10px;">★★★★★</div>
                    <p style="font-style: italic; margin-bottom: 15px;">"The Hydra-Glow Vitamin C serum completely transformed my dull skin in just two weeks. Outstanding quality and packaging!"</p>
                    <strong>— Ananya Das</strong>
                    <div style="font-size: 12px; color: var(--text-muted);">Verified Buyer • Bhubaneswar</div>
                </div>

                <div style="background: var(--surface); padding: 30px; border-radius: var(--radius-md); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                    <div style="color: #fbc02d; margin-bottom: 10px;">★★★★★</div>
                    <p style="font-style: italic; margin-bottom: 15px;">"Ordered the bridal kit as a gift. The eyeshadow pigments blend effortlessly with zero fallout. 10/10 recommend GlowCart!"</p>
                    <strong>— Sneha Mukherjee</strong>
                    <div style="font-size: 12px; color: var(--text-muted);">Verified Buyer • Kolkata</div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
