<?php
/**
 * GlowCart Cosmetics - Product Deletion Handler
 */
require_once __DIR__ . '/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);

if ($product_id <= 0) {
    $_SESSION['flash_error'] = "Invalid product ID specified.";
    header('Location: products.php');
    exit;
}

try {
    // Check product exists
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = :id");
    $stmt->execute([':id' => $product_id]);
    $prod = $stmt->fetch();

    if (!$prod) {
        $_SESSION['flash_error'] = "Product #{$product_id} does not exist.";
        header('Location: products.php');
        exit;
    }

    $pdo->beginTransaction();

    // 1. Remove from favorites
    $fav_stmt = $pdo->prepare("DELETE FROM favorites WHERE product_id = :pid");
    $fav_stmt->execute([':pid' => $product_id]);

    // 2. Safely disassociate from order_items so historical orders maintain their product_name & price
    $item_stmt = $pdo->prepare("UPDATE order_items SET product_id = NULL WHERE product_id = :pid");
    $item_stmt->execute([':pid' => $product_id]);

    // 3. Delete product record
    $del_stmt = $pdo->prepare("DELETE FROM products WHERE id = :pid");
    $del_stmt->execute([':pid' => $product_id]);

    $pdo->commit();

    $_SESSION['flash_success'] = "Product '{$prod['name']}' (#{$product_id}) was successfully deleted from the catalog.";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = "Failed to delete product: " . $e->getMessage();
}

header('Location: products.php');
exit;
