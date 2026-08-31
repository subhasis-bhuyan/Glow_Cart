<?php
/**
 * GlowCart Cosmetics - Customer Orders History
 */
require_once __DIR__ . '/config/db.php';
require_login('login.php');

$user_id = (int)$_SESSION['user_id'];

try {
    // Fetch orders for this customer ONLY
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :uid ORDER BY id DESC");
    $stmt->execute([':uid' => $user_id]);
    $orders = $stmt->fetchAll();

    // Group items by order
    $order_items = [];
    if (!empty($orders)) {
        $order_ids = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        
        $item_stmt = $pdo->prepare("
            SELECT oi.*, p.image 
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id IN ($placeholders)
        ");
        $item_stmt->execute($order_ids);
        $all_items = $item_stmt->fetchAll();

        foreach ($all_items as $item) {
            $order_items[$item['order_id']][] = $item;
        }
    }

} catch (PDOException $e) {
    die("Database query error.");
}

$page_title = 'My Orders | GlowCart Cosmetics';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container" style="padding: 40px 20px 70px;">
    <div style="max-width: 960px; margin: 0 auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1 style="font-size: 28px; margin-bottom: 4px;">My Orders</h1>
                <p style="font-size: 14px; color: var(--text-muted);">Track your recent beauty purchases, shipping status, and receipts.</p>
            </div>
            <a href="products.php" class="btn btn-outline btn-sm">🛍️ Shop More Products</a>
        </div>

        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <?php $items = $order_items[$order['id']] ?? []; ?>
                <div style="background: var(--surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); margin-bottom: 25px; overflow: hidden;">
                    
                    <!-- Order Header -->
                    <div style="background: var(--surface-alt); padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Order Placed</span>
                            <div style="font-size: 14px; font-weight: 500;"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
                        </div>

                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Total Amount</span>
                            <div style="font-size: 16px; font-weight: 700; color: var(--primary);"><?= format_price($order['total_amount']) ?></div>
                        </div>

                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Payment</span>
                            <div style="font-size: 13px;"><?= htmlspecialchars($order['payment_method']) ?></div>
                        </div>

                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Order ID</span>
                            <div style="font-size: 14px; font-weight: 600;">#GC-<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></div>
                        </div>

                        <div>
                            <span class="status-pill status-<?= htmlspecialchars($order['status']) ?>">
                                ● <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Order Items Breakdown -->
                    <div style="padding: 20px 24px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 12px 0; width: 60px;">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; background: var(--surface-alt); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 20px;">💄</div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 12px 15px;">
                                            <strong style="display: block;"><?= htmlspecialchars($item['product_name']) ?></strong>
                                            <span style="font-size: 12px; color: var(--text-muted);">Quantity: <?= $item['quantity'] ?> &bull; Unit Price: <?= format_price($item['price']) ?></span>
                                        </td>
                                        <td style="padding: 12px 0; text-align: right; font-weight: 600; color: var(--text-main);">
                                            <?= format_price($item['subtotal']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Delivery Address Info -->
                        <div style="margin-top: 15px; padding-top: 12px; border-top: 1px dashed var(--border-color); font-size: 13px; color: var(--text-muted);">
                            <strong>Shipping To:</strong> <?= htmlspecialchars($order['customer_name']) ?> &bull; <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> - <?= htmlspecialchars($order['pincode']) ?> &bull; 📱 <?= htmlspecialchars($order['phone']) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div style="background: var(--surface); padding: 70px 20px; text-align: center; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                <div style="font-size: 50px; margin-bottom: 15px;">📦</div>
                <h2 style="font-size: 22px; margin-bottom: 8px;">No Orders Found</h2>
                <p style="margin-bottom: 25px; color: var(--text-muted);">You haven't placed any cosmetics orders yet. Start exploring our beauty catalog today!</p>
                <a href="products.php" class="btn btn-primary btn-lg">Shop Now</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
