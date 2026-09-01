<?php
/**
 * GlowCart Cosmetics - Global Footer Component
 */
?>
    </main>

    <!-- Global Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand Info -->
                <div class="footer-brand">
                    <h3>💄 GLOWCART <span>COSMETICS</span></h3>
                    <p>Your premier destination for luxury makeup, organic skincare, and professional beauty essentials. Elevate your everyday glow with dermatologically tested, cruelty-free formulas.</p>
                    <p style="font-size: 13px; color: #888;">College Academic Project • BCA / MCA Demonstration</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="footer-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Shop All Products</a></li>
                        <li><a href="cart.php">Shopping Cart</a></li>
                        <li><a href="orders.php">My Orders</a></li>
                        <li><a href="admin/login.php">Admin Portal</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div>
                    <h4 class="footer-title">Categories</h4>
                    <ul class="footer-links">
                        <li><a href="products.php?category=Lipstick">Lipsticks & Gloss</a></li>
                        <li><a href="products.php?category=Foundation">Foundation & Powders</a></li>
                        <li><a href="products.php?category=Blush">Blush & Bronzers</a></li>
                        <li><a href="products.php?category=Eyeshadow">Eyeshadow Palettes</a></li>
                        <li><a href="products.php?category=Skincare">Serums & Toners</a></li>
                    </ul>
                </div>

                <!-- Customer Care & Security -->
                <div>
                    <h4 class="footer-title">Customer Care</h4>
                    <ul class="footer-links">
                        <li><a href="profile.php">My Account</a></li>
                        <li><a href="#">Shipping & Returns</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                    <div style="margin-top: 15px; font-size: 12px; color: #888;">
                        <p>📍 Bhubaneswar, Odisha, India</p>
                        <p>📧 support@glowcart.com</p>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                &copy; <?= date('Y') ?> GlowCart Cosmetics. All rights reserved. Built with PHP & MySQL.
            </div>
        </div>
    </footer>

    <!-- JavaScript Engine -->
    <script src="assets/js/script.js"></script>

    <!-- Trigger Session Flash Messages (if any) -->
    <?php if (isset($_SESSION['flash_success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast("<?= addslashes($_SESSION['flash_success']) ?>", "success");
            });
        </script>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast("<?= addslashes($_SESSION['flash_error']) ?>", "error");
            });
        </script>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
</body>
</html>
