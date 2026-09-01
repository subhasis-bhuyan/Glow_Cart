<?php
/**
 * GlowCart Cosmetics - Customer Profile & Account Management
 */
require_once __DIR__ . '/config/db.php';
require_login('login.php');

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = clean_input($_POST['name'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $address = clean_input($_POST['address'] ?? '');
    $city = clean_input($_POST['city'] ?? '');
    $state = clean_input($_POST['state'] ?? '');
    $pincode = clean_input($_POST['pincode'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (empty($name) || empty($phone)) {
        $error_msg = 'Name and Phone Number cannot be empty.';
    } else {
        try {
            if (!empty($new_password)) {
                if (strlen($new_password) < 8) {
                    $error_msg = 'New password must be at least 8 characters long.';
                } else {
                    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET name = :name, phone = :phone, address = :address, city = :city, state = :state, pincode = :pincode, password = :password
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':name'     => $name,
                        ':phone'    => $phone,
                        ':address'  => $address,
                        ':city'     => $city,
                        ':state'    => $state,
                        ':pincode'  => $pincode,
                        ':password' => $hashed,
                        ':id'       => $user_id
                    ]);
                    $_SESSION['user_name'] = $name;
                    $success_msg = 'Profile & Password updated successfully!';
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = :name, phone = :phone, address = :address, city = :city, state = :state, pincode = :pincode
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':name'     => $name,
                    ':phone'    => $phone,
                    ':address'  => $address,
                    ':city'     => $city,
                    ':state'    => $state,
                    ':pincode'  => $pincode,
                    ':id'       => $user_id
                ]);
                $_SESSION['user_name'] = $name;
                $success_msg = 'Profile details updated successfully!';
            }
        } catch (PDOException $e) {
            $error_msg = 'Could not update profile. Please try again.';
        }
    }
}

// Fetch Latest User Profile Data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch();

    // Order statistics
    $order_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :uid");
    $order_count_stmt->execute([':uid' => $user_id]);
    $total_orders = (int)$order_count_stmt->fetchColumn();

    // Fetch User Favorites / Liked Products
    $fav_stmt = $pdo->prepare("
        SELECT f.id AS fav_id, f.created_at AS favorited_at, p.*
        FROM favorites f
        JOIN products p ON f.product_id = p.id
        WHERE f.user_id = :uid AND p.status = 'Active'
        ORDER BY f.id DESC
    ");
    $fav_stmt->execute([':uid' => $user_id]);
    $favorites = $fav_stmt->fetchAll();
    $total_favorites = count($favorites);

} catch (PDOException $e) {
    die("Database query error.");
}

$page_title = 'My Profile & Favorites | GlowCart Cosmetics';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container" style="padding: 40px 20px 70px;">
    <div style="max-width: 960px; margin: 0 auto;">
        
        <!-- Profile Header Card -->
        <div style="background: linear-gradient(135deg, #fff5f7, #fdf6f0); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 30px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: var(--primary); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; box-shadow: var(--shadow-md);">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <div>
                    <h1 style="font-size: 26px; margin-bottom: 4px;"><?= htmlspecialchars($user['name']) ?></h1>
                    <div style="font-size: 13px; color: var(--text-muted);">
                        📧 <?= htmlspecialchars($user['email']) ?> &bull; 📱 <?= htmlspecialchars($user['phone']) ?>
                    </div>
                    <div style="font-size: 12px; color: var(--rose-gold-dark); margin-top: 4px;">
                        Member since <?= date('F d, Y', strtotime($user['created_at'])) ?>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary" onclick="switchProfileTab('favorites')" id="headerFavBtn">
                    ❤️ My Favorites (<span class="fav-count-display"><?= $total_favorites ?></span>)
                </button>
                <a href="orders.php" class="btn btn-outline">📦 My Orders (<?= $total_orders ?>)</a>
                <a href="logout.php" class="btn btn-outline" style="color: var(--danger); border-color: var(--danger);">🚪 Logout</a>
            </div>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div style="background: var(--success-bg); color: var(--success); padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid rgba(46,125,50,0.2);">
                ✓ <?= htmlspecialchars($success_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div style="background: var(--danger-bg); color: var(--danger); padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid rgba(198,40,40,0.2);">
                ⚠️ <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- Profile Navigation Tabs -->
        <div class="profile-tabs-wrapper">
            <button type="button" class="profile-tab-btn active" id="tabBtnDetails" onclick="switchProfileTab('details')">
                <span>✏️</span> Account & Shipping Details
            </button>
            <button type="button" class="profile-tab-btn" id="tabBtnFavorites" onclick="switchProfileTab('favorites')">
                <span>❤️</span> My Favorite Products (<span class="fav-count-display"><?= $total_favorites ?></span>)
            </button>
        </div>

        <!-- Tab 1: Account Details Form Card -->
        <div class="profile-tab-pane active" id="paneDetails">
            <div class="form-card">
                <h2 class="form-title"><span>✏️</span> Edit Account & Shipping Details</h2>

                <form action="profile.php" method="POST">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address (Read-only)</label>
                            <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background: var(--surface-alt); cursor: not-allowed;">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" pattern="[0-9]{10}" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="••••••••" minlength="8">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address" class="form-label">Street Address</label>
                        <input type="text" id="address" name="address" class="form-control" placeholder="House/Flat No., Street Name, Area" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                    </div>

                    <div class="form-grid-2" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="form-group">
                            <label for="city" class="form-label">City</label>
                            <input type="text" id="city" name="city" class="form-control" placeholder="e.g. Bhubaneswar" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="state" class="form-label">State</label>
                            <input type="text" id="state" name="state" class="form-control" placeholder="e.g. Odisha" value="<?= htmlspecialchars($user['state'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="pincode" class="form-label">PIN Code</label>
                            <input type="text" id="pincode" name="pincode" class="form-control" placeholder="751024" value="<?= htmlspecialchars($user['pincode'] ?? '') ?>">
                        </div>
                    </div>

                    <div style="margin-top: 15px; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary btn-lg">
                            💾 Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tab 2: Favorites / Liked Products Tab -->
        <div class="profile-tab-pane" id="paneFavorites">
            <div class="form-card">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                    <h2 class="form-title" style="margin-bottom: 0; padding-bottom: 0; border-bottom: none;">
                        <span>❤️</span> My Favorite Products
                    </h2>
                    <span style="font-size: 13px; color: var(--text-muted);">
                        <span class="fav-count-display"><?= $total_favorites ?></span> item<?= $total_favorites === 1 ? '' : 's' ?> saved
                    </span>
                </div>

                <!-- Favorites Grid Container -->
                <div id="favoritesGridContainer">
                    <?php if (!empty($favorites)): ?>
                        <div class="profile-favorites-grid">
                            <?php foreach ($favorites as $fav): ?>
                                <?php 
                                    $effective_price = (!empty($fav['discount_price']) && $fav['discount_price'] < $fav['price']) ? $fav['discount_price'] : $fav['price'];
                                    $has_discount = (!empty($fav['discount_price']) && $fav['discount_price'] < $fav['price']);
                                ?>
                                <div class="favorite-item-card" id="favorite-card-<?= $fav['id'] ?>">
                                    <div class="favorite-thumb-wrapper">
                                        <a href="product_details.php?id=<?= $fav['id'] ?>">
                                            <img src="<?= htmlspecialchars($fav['image']) ?>" alt="<?= htmlspecialchars($fav['name']) ?>" loading="lazy">
                                        </a>
                                        <?php if ($has_discount): ?>
                                            <?php $pct = round((($fav['price'] - $fav['discount_price']) / $fav['price']) * 100); ?>
                                            <span class="badge badge-sale favorite-discount-badge"><?= $pct ?>% OFF</span>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="favorite-remove-corner-btn" onclick="removeFromFavorites(<?= $fav['id'] ?>, document.getElementById('favorite-card-<?= $fav['id'] ?>'))" title="Remove from favorites">
                                            ✕
                                        </button>
                                    </div>

                                    <div class="favorite-info-wrapper">
                                        <div class="product-category"><?= htmlspecialchars($fav['category']) ?></div>
                                        <h3 class="product-title">
                                            <a href="product_details.php?id=<?= $fav['id'] ?>"><?= htmlspecialchars($fav['name']) ?></a>
                                        </h3>

                                        <div class="product-rating">
                                            ★ <?= number_format($fav['rating'], 1) ?>
                                            <span>(<?= $fav['stock'] > 0 ? "{$fav['stock']} in stock" : 'Sold out' ?>)</span>
                                        </div>

                                        <div class="product-price-stock" style="margin-bottom: 12px;">
                                            <div class="price-wrapper">
                                                <span class="current-price"><?= format_price($effective_price) ?></span>
                                                <?php if ($has_discount): ?>
                                                    <span class="original-price"><?= format_price($fav['price']) ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <div>
                                                <?php if ($fav['stock'] > 0): ?>
                                                    <span class="badge badge-in-stock">✓ In Stock</span>
                                                <?php else: ?>
                                                    <span class="badge badge-out-of-stock">✕ Out of Stock</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div style="display: flex; gap: 8px;">
                                            <?php if ($fav['stock'] > 0): ?>
                                                <button type="button" class="btn btn-primary btn-block btn-sm" onclick="addToCart(<?= $fav['id'] ?>, 1)">
                                                    🛍️ Add to Cart
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline btn-block btn-sm disabled" disabled>
                                                    ✕ Out of Stock
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="removeFromFavorites(<?= $fav['id'] ?>, document.getElementById('favorite-card-<?= $fav['id'] ?>'))" title="Remove from favorites" style="color: var(--danger); border-color: var(--border-color); padding: 8px 12px;">
                                                💔
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="favorites-empty-state" id="favoritesEmptyState">
                            <div class="empty-heart-icon">❤️</div>
                            <h3>No Favorite Products Yet</h3>
                            <p>You haven't liked or favorited any cosmetic products yet. Browse our catalog and click the ❤️ icon on items you love to save them here!</p>
                            <a href="products.php" class="btn btn-primary btn-lg" style="margin-top: 15px;">
                                🛍️ Explore Beauty Collection
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
// Switch between Account Details and Favorites Tabs
function switchProfileTab(tabName) {
    const tabBtnDetails = document.getElementById('tabBtnDetails');
    const tabBtnFavorites = document.getElementById('tabBtnFavorites');
    const paneDetails = document.getElementById('paneDetails');
    const paneFavorites = document.getElementById('paneFavorites');

    if (tabName === 'favorites') {
        tabBtnDetails.classList.remove('active');
        tabBtnFavorites.classList.add('active');
        paneDetails.classList.remove('active');
        paneFavorites.classList.add('active');
        window.location.hash = 'favorites';
    } else {
        tabBtnFavorites.classList.remove('active');
        tabBtnDetails.classList.add('active');
        paneFavorites.classList.remove('active');
        paneDetails.classList.add('active');
        history.replaceState(null, null, ' ');
    }
}

// Auto open favorites tab if URL contains #favorites
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.hash === '#favorites') {
        switchProfileTab('favorites');
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

