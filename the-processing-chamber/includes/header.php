<?php
/**
 * Public header / sidebar include
 *
 * This file renders the left sidebar and page header used across public pages.
 * Keep this file lightweight: require configuration and helpers only. Any
 * heavy logic should remain in page controllers (the files in /pages/).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Default page title when individual pages do not set $page_title
if (!isset($page_title)) {
    $page_title = SITE_NAME;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" href="<?php echo SITE_URL; ?>/assets/images/logo.png" type="image/png">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Mobile Nav Toggle -->
    <div class="nav-toggle">
        <i class="fas fa-bars"></i>
    </div>

    <!-- Left Navigation -->
    <aside class="sidebar">
        <div class="logo-container">
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>/index.php" style="text-decoration:none; color:inherit;">
                    <h1 style="margin:0; font-size:1.25rem; font-weight:700;"><?php echo SITE_NAME; ?></h1>
                </a>
            </div>
            <p>Premium Computer Hardware</p>
        </div>

        <ul class="nav-menu">
            <li><a href="<?php echo SITE_URL; ?>/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="<?php echo SITE_URL; ?>/pages/products.php"><i class="fas fa-fire"></i> Popular Products</a></li>
            <li><a href="#"><i class="fas fa-tag"></i> Special Offers</a></li>
        </ul>

        <div class="nav-category">Product Categories</div>
        
        <ul class="nav-menu">
            <?php
            $categoryTree = getCategoryTree();
            foreach ($categoryTree as $parent) {
                // determine icon class with sensible fallbacks
                $iconClassRaw = trim((string)($parent['icon'] ?? ''));
                $iconClass = $iconClassRaw;
                if ($iconClass === '') {
                    $lowerName = strtolower($parent['name'] ?? '');
                    if (strpos($lowerName, 'mother') !== false) {
                        $iconClass = 'fas fa-microchip';
                    } elseif (strpos($lowerName, 'graphics') !== false || strpos($lowerName, 'gpu') !== false) {
                        $iconClass = 'fas fa-video';
                    } elseif (strpos($lowerName, 'memory') !== false || strpos($lowerName, 'ram') !== false) {
                        $iconClass = 'fas fa-memory';
                    } else {
                        $iconClass = 'fas fa-box';
                    }
                } else {
                    // normalize known unsupported names
                    if (stripos($iconClass, 'motherboard') !== false) {
                        $iconClass = 'fas fa-microchip';
                    }
                }

                echo '<li>';
                echo '<a href="' . SITE_URL . '/pages/products.php?category=' . $parent['id'] . '">';
                echo '<i class="' . htmlspecialchars($iconClass) . '"></i> ' . htmlspecialchars($parent['name']) . '</a>';

                // children
                if (!empty($parent['children'])) {
                    echo '<ul style="list-style:none; padding-left:18px; margin-top:8px;">';
                    foreach ($parent['children'] as $child) {
                        if (trim(strtolower($child['name'])) === trim(strtolower($parent['name']))) continue; // skip duplicate
                        $childIconRaw = trim((string)($child['icon'] ?? ''));
                        $childIcon = $childIconRaw;
                        if ($childIcon === '') {
                            $lowerChild = strtolower($child['name'] ?? '');
                            if (strpos($lowerChild, 'mother') !== false) $childIcon = 'fas fa-microchip';
                            elseif (strpos($lowerChild, 'graphics') !== false) $childIcon = 'fas fa-video';
                            else $childIcon = 'fas fa-angle-right';
                        } else {
                            if (stripos($childIcon, 'motherboard') !== false) $childIcon = 'fas fa-microchip';
                        }

                        echo '<li style="margin-bottom:6px;">';
                        echo '<a href="' . SITE_URL . '/pages/products.php?category=' . $child['id'] . '">';
                        echo '<i class="' . htmlspecialchars($childIcon) . '" style="margin-right:6px;"></i> ' . htmlspecialchars($child['name']);
                        echo '</a></li>';
                    }
                    echo '</ul>';
                }
                echo '</li>';
            }
            ?>
        </ul>

        <div class="nav-category">Account</div>
        
        <ul class="nav-menu">
            <?php if (isLoggedIn()): ?>
                <li><a href="<?php echo SITE_URL; ?>/pages/profile.php"><i class="fas fa-user"></i> My Account</a></li>
                <li><a href="<?php echo SITE_URL; ?>/pages/cart.php"><i class="fas fa-shopping-cart"></i> My Cart</a></li>
                <li><a href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            <?php else: ?>
                <li><a href="<?php echo SITE_URL; ?>/pages/login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <li><a href="<?php echo SITE_URL; ?>/pages/register.php"><i class="fas fa-user-plus"></i> Register</a></li>
            <?php endif; ?>
        </ul>

        <div class="nav-category">Support</div>
        
        <ul class="nav-menu">
            <li><a href="<?php echo SITE_URL; ?>/pages/contact.php"><i class="fas fa-phone"></i> Contact Us</a></li>
            <li><a href="#"><i class="fas fa-question-circle"></i> FAQ</a></li>
        </ul>

    <!-- theme toggle moved to header user actions for quick access -->
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="search-bar">
                <form action="<?php echo SITE_URL; ?>/pages/products.php" method="GET">
                    <input type="text" name="search" placeholder="Search for products, brands, and categories...">
                </form>
            </div>
            <div class="user-actions">
                <button id="headerThemeToggle" class="header-theme-toggle" aria-label="Toggle theme"><i class="fas fa-moon"></i></button>
                <button id="sidebarCollapseToggle" class="header-theme-toggle" aria-label="Toggle sidebar" style="margin-left:8px;"><i class="fas fa-angle-double-left"></i></button>
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>/pages/profile.php"><i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?></a>
                    <a href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/pages/login.php"><i class="fas fa-user"></i> Login</a>
                    <a href="<?php echo SITE_URL; ?>/pages/register.php"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/pages/cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo getCartCount(); ?></span>
                </a>
            </div>
        </div>