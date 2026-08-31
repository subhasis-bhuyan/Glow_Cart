<?php
/**
 * GlowCart Cosmetics - Admin Order Management
 */
require_once __DIR__ . '/../config/db.php';
require_admin('login.php');

$admin_page_title = 'Order Management | GlowCart Admin';

// Handle Order Status Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id   = (int)($_POST['order_id'] ?? 0);
    $new_status = clean_input($_POST['status'] ?? 'Pending');

    $valid_statuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];

    if ($order_id > 0 && in_array($new_status, $valid_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $new_status, ':id' => $order_id]);
            $_SESSION['admin_flash_success'] = "Order #GC-" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . " status updated to '{$new_status}'.";
        } catch (PDOException $e) {
            $_SESSION['admin_flash_error'] = 'Could not update order status.';
        }
    }
    header('Location: orders.php');
    exit;
}

// Filter parameters
$status_filter = clean_input($_GET['status'] ?? '');
$search = clean_input($_GET['search'] ?? '');

$where = ["1 = 1"];
$params = [];

if (!empty($status_filter)) {
    $where[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $where[] = "(customer_name LIKE :search OR email LIKE :search_email OR phone LIKE :search_phone OR id = :search_id)";
    $params[':search'] = "%{$search}%";
    $params[':search_email'] = "%{$search}%";
    $params[':search_phone'] = "%{$search}%";
    $params[':search_id'] = is_numeric($search) ? (int)$search : 0;
}

$sql = "SELECT * FROM orders WHERE " . implode(' AND ', $where) . " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
    die("Database query error: " . $e->getMessage());
}

$statuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Manage Customer Orders</h1>
        <p style="color: var(--admin-muted); font-size: 13px;">Review order details, verify delivery addresses, and update shipment status.</p>
    </div>
</div>

<!-- Filters Bar -->
<div style="background: var(--admin-surface); padding: 18px 20px; border-radius: var(--radius); border: 1px solid var(--admin-border); margin-bottom: 25px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
    <form action="orders.php" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; flex: 1;">
        <input type="text" name="search" class="admin-form-control" style="max-width: 280px;" placeholder="Search customer, phone, ID..." value="<?= htmlspecialchars($search) ?>">

        <select name="status" class="admin-form-control" style="max-width: 180px;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= ($status_filter === $s) ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">Filter</button>
        <?php if (!empty($search) || !empty($status_filter)): ?>
            <a href="orders.php" class="admin-btn admin-btn-outline admin-btn-sm">Clear</a>
        <?php endif; ?>
    </form>

    <div style="font-size: 13px; color: var(--admin-muted);">
        Total: <strong><?= count($orders) ?></strong> orders
    </div>
</div>

<!-- Orders List -->
<?php if (!empty($orders)): ?>
    <?php foreach ($orders as $order): ?>
        <?php $items = $order_items[$order['id']] ?? []; ?>
        <div style="background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--radius); margin-bottom: 25px; box-shadow: var(--shadow-sm); overflow: hidden;">
            
            <!-- Order Header -->
            <div style="background: #f8fafc; padding: 18px 24px; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div>
                    <span style="font-size: 12px; color: var(--admin-muted); text-transform: uppercase;">Order Number</span>
                    <h3 style="font-size: 18px; color: var(--admin-primary); margin: 0;">#GC-<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h3>
                    <small style="color: var(--admin-muted);"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></small>
                </div>

                <div>
                    <span style="font-size: 12px; color: var(--admin-muted); text-transform: uppercase;">Customer Info</span>
                    <div style="font-weight: 600;"><?= htmlspecialchars($order['customer_name']) ?></div>
                    <div style="font-size: 12px; color: var(--admin-muted);">📧 <?= htmlspecialchars($order['email']) ?> &bull; 📱 <?= htmlspecialchars($order['phone']) ?></div>
                </div>

                <div>
                    <span style="font-size: 12px; color: var(--admin-muted); text-transform: uppercase;">Total & Payment</span>
                    <div style="font-size: 17px; font-weight: 700; color: var(--admin-text);"><?= format_price($order['total_amount']) ?></div>
                    <div style="font-size: 12px; color: var(--admin-muted);"><?= htmlspecialchars($order['payment_method']) ?> (<?= htmlspecialchars($order['payment_status']) ?>)</div>
                </div>

                <!-- Status Update Form -->
                <div>
                    <form action="orders.php" method="POST" style="display: flex; align-items: center; gap: 8px;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                        <select name="status" class="admin-form-control" style="padding: 6px 10px; font-size: 12px; font-weight: 600; width: 130px;">
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?= $st ?>" <?= ($order['status'] === $st) ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm" title="Save Status Change">
                            💾 Save
                        </button>
                    </form>
                </div>
            </div>

            <!-- Ordered Items & Shipping Details -->
            <div style="padding: 20px 24px;">
                <h4 style="font-size: 13px; text-transform: uppercase; color: var(--admin-muted); margin-bottom: 10px;">Ordered Products</h4>
                
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr style="border-bottom: 1px dashed var(--admin-border);">
                                <td style="padding: 8px 0; width: 44px;">
                                    <?php if (!empty($it['image'])): ?>
                                        <img src="<?= htmlspecialchars($it['image']) ?>" alt="" style="width: 36px; height: 36px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
                                    <?php else: ?>
                                        <div style="width: 36px; height: 36px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">💄</div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 8px 12px;">
                                    <strong><?= htmlspecialchars($it['product_name']) ?></strong>
                                </td>
                                <td style="padding: 8px 12px; color: var(--admin-muted);">
                                    <?= $it['quantity'] ?> &times; <?= format_price($it['price']) ?>
                                </td>
                                <td style="padding: 8px 0; text-align: right; font-weight: 600;">
                                    <?= format_price($it['subtotal']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #f1f5f9; font-size: 13px; color: var(--admin-muted);">
                    📍 <strong>Shipping Address:</strong> <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> - <?= htmlspecialchars($order['pincode']) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div style="background: var(--admin-surface); padding: 50px; text-align: center; border-radius: var(--radius); border: 1px solid var(--admin-border);">
        <div style="font-size: 40px; margin-bottom: 10px;">📦</div>
        <h3 style="font-size: 18px; margin-bottom: 6px;">No Orders Found</h3>
        <p style="color: var(--admin-muted); font-size: 13px;">No orders match the selected filters or search terms.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
