-- GlowCart Cosmetics Database Schema & Seed Data
-- Database: glowcart_db

CREATE DATABASE IF NOT EXISTS `glowcart_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `glowcart_db`;

-- Drop existing tables in reverse dependency order if re-importing
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ========================================================
-- 1. Users Table (Customer Registration & Authentication)
-- ========================================================
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(50) DEFAULT NULL,
  `state` VARCHAR(50) DEFAULT NULL,
  `pincode` VARCHAR(10) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- 2. Admins Table
-- ========================================================
CREATE TABLE `admins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- 3. Products Table
-- ========================================================
CREATE TABLE `products` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `discount_price` DECIMAL(10,2) DEFAULT NULL,
  `stock` INT(11) NOT NULL DEFAULT 0,
  `image` VARCHAR(255) NOT NULL,
  `rating` DECIMAL(2,1) NOT NULL DEFAULT 4.5,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_bestseller` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- 4. Orders Table
-- ========================================================
CREATE TABLE `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  `city` VARCHAR(50) NOT NULL,
  `state` VARCHAR(50) NOT NULL,
  `pincode` VARCHAR(10) NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `status` ENUM('Pending','Confirmed','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_orders_user` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- 5. Order Items Table
-- ========================================================
CREATE TABLE `order_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `product_id` INT(11) DEFAULT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` INT(11) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order` (`order_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- 6. Favorites Table (Customer Wishlist / Liked Products)
-- ========================================================
CREATE TABLE `favorites` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product_unique` (`user_id`, `product_id`),
  KEY `fk_favorites_user` (`user_id`),
  KEY `fk_favorites_product` (`product_id`),
  CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_favorites_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SEED DATA
-- ========================================================

-- Default Admin User (Password: admin123)
-- Hash generated via password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `admins` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'Admin GlowCart', 'admin@glowcart.com', '$2y$10$w8T0P1zD8yT90a0K1C6DqeZk8gO9vH0k1Pq6y0mZ2eF.6B3t1QJeq', NOW());

-- Default Demo Customer (Password: password123)
-- Hash generated via password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `address`, `city`, `state`, `pincode`, `created_at`) VALUES
(1, 'Subhasis Nayak', 'subhasis@example.com', '9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '124 Lotus Garden, Patia', 'Bhubaneswar', 'Odisha', '751024', NOW());

-- Pre-populate 12 Premium Cosmetics Products across all categories
INSERT INTO `products` (`id`, `name`, `category`, `description`, `price`, `discount_price`, `stock`, `image`, `rating`, `is_featured`, `is_bestseller`, `status`, `created_at`) VALUES
(1, 'Velvet Matte Rose Lipstick', 'Lipstick', 'Long-lasting, ultra-creamy matte lipstick enriched with vitamin E and jojoba oil for soft, hydrated, bold lips all day.', 899.00, 699.00, 35, 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?auto=format&fit=crop&w=600&q=80', 4.8, 1, 1, 'Active', NOW()),
(2, 'Luminous Radiance Liquid Foundation', 'Foundation', 'Lightweight medium-to-full buildable coverage liquid foundation with SPF 20 for an all-day natural glowing complexion.', 1299.00, 999.00, 20, 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=600&q=80', 4.7, 1, 1, 'Active', NOW()),
(3, 'Soft Peach Silk Powder Blush', 'Blush', 'Silky, blendable powder blush that delivers a natural flush of radiant color with a subtle satin shimmer.', 749.00, 549.00, 18, 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=600&q=80', 4.6, 1, 0, 'Active', NOW()),
(4, 'Golden Sunset 12-Shade Eyeshadow Palette', 'Eyeshadow', 'A versatile palette featuring 12 buttery matte, metallic, and duo-chrome warm sunset shades for day-to-night eye looks.', 1499.00, 1199.00, 15, 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=600&q=80', 4.9, 1, 1, 'Active', NOW()),
(5, 'Extreme Length Waterproof Mascara', 'Mascara', 'Smudge-proof, volumizing waterproof mascara with an hourglass wand that lifts and separates every lash.', 699.00, 499.00, 25, 'https://images.unsplash.com/photo-1560365163-3e8d64e762ef?auto=format&fit=crop&w=600&q=80', 4.5, 0, 1, 'Active', NOW()),
(6, 'Hydra-Glow Vitamin C Face Serum', 'Skincare', 'Potent brightening serum formulated with 10% Vitamin C, Hyaluronic Acid, and Niacinamide to restore skin glow.', 1199.00, 899.00, 30, 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=600&q=80', 4.9, 1, 1, 'Active', NOW()),
(7, 'Ultimate Bridal Glam Makeup Kit', 'Makeup Kits', 'Complete luxury vanity kit including foundation, primer, 2 lipsticks, compact, blush, eyeliner, and blending sponge.', 3499.00, 2699.00, 10, 'https://images.unsplash.com/photo-1516975080664-ed2fc6a32937?auto=format&fit=crop&w=600&q=80', 4.8, 1, 1, 'Active', NOW()),
(8, 'Professional 10-Piece Rose Gold Brush Set', 'Accessories', 'Ultra-soft vegan synthetic bristles with premium rose gold ferrules and marble handles for seamless blending.', 1299.00, 899.00, 22, 'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=600&q=80', 4.7, 0, 0, 'Active', NOW()),
(9, 'Ruby Shine Hydrating Lip Gloss', 'Lipstick', 'High-shine, non-sticky lip gloss enriched with shea butter and coconut oil for plump, juicy lips.', 599.00, 399.00, 40, 'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?auto=format&fit=crop&w=600&q=80', 4.4, 0, 1, 'Active', NOW()),
(10, 'Matte Finish Oil-Control Compact Powder', 'Foundation', 'Micro-fine pressed powder that blurs pores, absorbs excess oil, and sets makeup for up to 12 hours.', 649.00, 499.00, 12, 'https://images.unsplash.com/photo-1590156221185-c42152e9928d?auto=format&fit=crop&w=600&q=80', 4.6, 0, 0, 'Active', NOW()),
(11, 'Rosewater Soothing Face Mist & Toner', 'Skincare', 'Pure steam-distilled Bulgarian rosewater mist that instantly hydrates, calms, and balances the skin pH.', 499.00, 349.00, 0, 'https://images.unsplash.com/photo-1608248597359-bbcf39ff1a60?auto=format&fit=crop&w=600&q=80', 4.5, 0, 0, 'Active', NOW()),
(12, 'Precision Micro Eyeliner Pen - Midnight Black', 'Accessories', 'Waterproof 0.1mm micro-tip liquid eyeliner pen for sharp, smudge-resistant wings and clean lines.', 549.00, 399.00, 50, 'https://images.unsplash.com/photo-1583241800698-e8ab01830a07?auto=format&fit=crop&w=600&q=80', 4.7, 0, 1, 'Active', NOW());
