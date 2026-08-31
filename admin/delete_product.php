<?php
/**
 * GlowCart Cosmetics - Delete Product Handler
 */
require_once __DIR__ . '/../config/db.php';
require_admin('login.php');

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    try {
        // Set product_id to NULL in order_items to preserve customer order history
        $pdo->prepare("UPDATE order_items SET product_id = NULL WHERE product_id = :id")->execute([':id' => $product_id]);

        // Delete the product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);

        $_SESSION['admin_flash_success'] = 'Product deleted successfully.';
    } catch (PDOException $e) {
        $_SESSION['admin_flash_error'] = 'Could not delete product. ' . $e->getMessage();
    }
}

header('Location: products.php');
exit;
