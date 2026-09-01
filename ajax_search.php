<?php
/**
 * GlowCart Cosmetics - AJAX Live Search Endpoint
 * Returns matching products and orders in real-time as user types
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/db.php';

$raw_query = $_GET['q'] ?? $_GET['search'] ?? $_POST['q'] ?? $_POST['search'] ?? '';
$query = trim((string)$raw_query);

if (empty($query)) {
    echo json_encode([
        'success'     => true,
        'query'       => '',
        'total_count' => 0,
        'products'    => [],
        'orders'      => []
    ]);
    exit;
}

$products_results = [];
$orders_results = [];

try {
    // ----------------------------------------------------------------------
    // 1. Search Active Products
    // ----------------------------------------------------------------------
    // Multi-tier relevance ranking:
    // Tier 1: exact name match
    // Tier 2: name starts with query
    // Tier 3: name contains query
    // Tier 4: category contains query
    // Tier 5: description contains query
    $search_pattern = "%{$query}%";
    $search_start   = "{$query}%";

    $product_sql = "
        SELECT id, name, category, description, price, discount_price, stock, image, rating, is_featured, is_bestseller,
               CASE
                   WHEN LOWER(name) = LOWER(:exact_q) THEN 100
                   WHEN LOWER(name) LIKE LOWER(:start_q) THEN 80
                   WHEN LOWER(name) LIKE LOWER(:like_q) THEN 60
                   WHEN LOWER(category) LIKE LOWER(:like_q_cat) THEN 40
                   WHEN LOWER(description) LIKE LOWER(:like_q_desc) THEN 20
                   ELSE 0
               END AS relevance
        FROM products
        WHERE status = 'Active'
          AND (
              name LIKE :where_name
              OR category LIKE :where_cat
              OR description LIKE :where_desc
          )
        ORDER BY relevance DESC, id DESC
        LIMIT 8
    ";

    $prod_stmt = $pdo->prepare($product_sql);
    $prod_stmt->execute([
        ':exact_q'      => $query,
        ':start_q'      => $search_start,
        ':like_q'       => $search_pattern,
        ':like_q_cat'   => $search_pattern,
        ':like_q_desc'  => $search_pattern,
        ':where_name'   => $search_pattern,
        ':where_cat'    => $search_pattern,
        ':where_desc'   => $search_pattern,
    ]);

    $raw_products = $prod_stmt->fetchAll();

    foreach ($raw_products as $p) {
        $price = (float)$p['price'];
        $discount_price = (!empty($p['discount_price']) && (float)$p['discount_price'] < $price) ? (float)$p['discount_price'] : null;
        $current_price = $discount_price ?? $price;
        $has_discount = $discount_price !== null;
        $discount_percent = $has_discount ? round((($price - $discount_price) / $price) * 100) : 0;
        $stock = (int)$p['stock'];

        $products_results[] = [
            'id'                       => (int)$p['id'],
            'name'                     => $p['name'],
            'category'                 => $p['category'],
            'price'                    => $price,
            'discount_price'           => $discount_price,
            'current_price'            => $current_price,
            'formatted_price'          => format_price($current_price),
            'formatted_original_price' => $has_discount ? format_price($price) : null,
            'has_discount'             => $has_discount,
            'discount_percent'         => $discount_percent,
            'image'                    => $p['image'],
            'rating'                   => (float)$p['rating'],
            'stock'                    => $stock,
            'in_stock'                 => ($stock > 0),
            'url'                      => "product_details.php?id=" . (int)$p['id']
        ];
    }

    // ----------------------------------------------------------------------
    // 2. Search Orders (if query matches Order format or customer logged in)
    // ----------------------------------------------------------------------
    // Extract potential numeric order ID from strings like "GC-00001", "#GC-1", "GC1", "12"
    $extracted_id = 0;
    if (preg_match('/(?:GC-?|#GC-?|#)?(\d+)/i', $query, $matches)) {
        $extracted_id = (int)$matches[1];
    }

    if (is_logged_in()) {
        $user_id = (int)$_SESSION['user_id'];
        $order_where = ["user_id = :uid"];
        $order_params = [':uid' => $user_id];

        if ($extracted_id > 0) {
            $order_where[] = "(id = :order_id OR id IN (SELECT order_id FROM order_items WHERE product_name LIKE :item_q))";
            $order_params[':order_id'] = $extracted_id;
            $order_params[':item_q'] = "%{$query}%";
        } else {
            $order_where[] = "(status LIKE :status_q OR id IN (SELECT order_id FROM order_items WHERE product_name LIKE :item_q))";
            $order_params[':status_q'] = "%{$query}%";
            $order_params[':item_q'] = "%{$query}%";
        }

        $order_sql = "SELECT id, total_amount, payment_method, payment_status, status, created_at FROM orders WHERE " . implode(' AND ', $order_where) . " ORDER BY id DESC LIMIT 4";
        $order_stmt = $pdo->prepare($order_sql);
        $order_stmt->execute($order_params);
        $raw_orders = $order_stmt->fetchAll();

        foreach ($raw_orders as $ord) {
            $orders_results[] = [
                'id'              => (int)$ord['id'],
                'order_code'      => '#GC-' . str_pad($ord['id'], 5, '0', STR_PAD_LEFT),
                'total_amount'    => (float)$ord['total_amount'],
                'formatted_total' => format_price($ord['total_amount']),
                'status'          => $ord['status'],
                'payment_method'  => $ord['payment_method'],
                'created_at'      => $ord['created_at'],
                'formatted_date'  => date('d M Y', strtotime($ord['created_at'])),
                'url'             => "orders.php?search=" . urlencode('#GC-' . str_pad($ord['id'], 5, '0', STR_PAD_LEFT))
            ];
        }
    }

    $total_count = count($products_results) + count($orders_results);

    echo json_encode([
        'success'     => true,
        'query'       => $query,
        'total_count' => $total_count,
        'products'    => $products_results,
        'orders'      => $orders_results
    ]);
    exit;

} catch (PDOException $e) {
    echo json_encode([
        'success'     => false,
        'message'     => 'Database search error: ' . $e->getMessage(),
        'products'    => [],
        'orders'      => []
    ]);
    exit;
}
