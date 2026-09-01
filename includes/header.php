<?php
/**
 * GlowCart Cosmetics - Global HTML Header Component
 */
if (!isset($page_title)) {
    $page_title = 'GlowCart Cosmetics | Premium Beauty & Makeup';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#d81b60">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="Discover premium makeup, skincare, and beauty products at GlowCart Cosmetics. Pure beauty, crafted for your glow.">
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💄</text></svg>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Top Announcement Bar -->
    <div class="top-bar">
        <div class="container">
            ✨ Free Shipping on Orders Over ₹500 | Use Code <strong>GLOW15</strong> for 15% Off! ✨
        </div>
    </div>
