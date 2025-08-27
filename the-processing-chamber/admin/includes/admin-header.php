<?php
// Admin header template
// Include configuration
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Set default page title if not set
if (!isset($page_title)) {
    $page_title = "Admin Panel";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo $page_title; ?> - The Processing Chamber</title>
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
                    <h1 style="margin:0; font-size:1.1rem; font-weight:700;">Admin Panel</h1>
                </div>
            <p>The Processing Chamber</p>
        </div>

        <ul class="nav-menu">
            <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>"><i class="fas fa-list"></i> Categories</a></li>
            <li><a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Inventory</a></li>
        </ul>

        <div class="nav-category">Site</div>
        
        <ul class="nav-menu">
            <!-- View Site removed per user request -->
            <li><a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>

        <button class="theme-toggle" id="themeToggle">
            <i class="fas fa-moon"></i> <span>Dark Mode</span>
        </button>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <h1><?php echo $page_title; ?></h1>
            <div class="user-actions">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>  