<?php
/**
 * admin/inventory.php — Inventory management (admin)
 *
 * Provides low-stock alerts and allows staff to update product stock counts.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('../index.php');
}
// Handle inventory update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inventory'])) {
    $product_id = intval($_POST['product_id']);
    $stock = intval($_POST['stock']);
    
    $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmt->execute([$stock, $product_id]);
    
    $success = "Inventory updated successfully!";
}

// Get low stock products
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock < 5 ORDER BY p.stock ASC");
$low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all products for inventory management
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.stock ASC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title
$page_title = "Manage Inventory";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo $page_title; ?> - The Processing Chamber</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <div class="logo-icon">TPC</div>
                <h1>Admin Panel</h1>
            </div>
            <p>The Processing Chamber</p>
        </div>

        <ul class="nav-menu">
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="categories.php"><i class="fas fa-list"></i> Categories</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="inventory.php" class="active"><i class="fas fa-warehouse"></i> Inventory</a></li>
        </ul>

        <div class="nav-category">Site</div>
        
        <ul class="nav-menu">
            <!-- View Site removed per user request -->
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>

        <button class="theme-toggle" id="themeToggle">
            <i class="fas fa-moon"></i> <span>Dark Mode</span>
        </button>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="header">
            <h1>Manage Inventory</h1>
            <div class="user-actions">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Low Stock Alert -->
        <?php if (count($low_stock_products) > 0): ?>
        <div class="section" style="margin-bottom: 30px;">
            <h2 class="section-title" style="color: var(--danger);">
                <i class="fas fa-exclamation-triangle"></i> Low Stock Alert
            </h2>
            <div class="products-grid">
                <?php foreach ($low_stock_products as $product): ?>
                <div class="product-card">
                    <div class="product-img">
                        <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-price" style="color: var(--danger);">Stock: <?php echo $product['stock']; ?></div>
                        <div class="product-actions">
                            <form method="POST" action="" style="display: flex; gap: 10px;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="number" name="stock" value="<?php echo $product['stock']; ?>" min="0" style="width: 80px; padding: 5px;">
                                <button type="submit" name="update_inventory" class="btn" style="padding: 5px 10px;">Update</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- All Products Inventory -->
        <h2 class="section-title">All Products Inventory</h2>
        
        <table style="width: 100%; border-collapse: collapse; background-color: var(--card-bg); border-radius: 8px; overflow: hidden; margin-bottom: 30px;">
            <thead>
                <tr style="background-color: var(--primary); color: white;">
                    <th style="padding: 12px; text-align: left;">ID</th>
                    <th style="padding: 12px; text-align: left;">Product Name</th>
                    <th style="padding: 12px; text-align: left;">Category</th>
                    <th style="padding: 12px; text-align: right;">Price</th>
                    <th style="padding: 12px; text-align: center;">Current Stock</th>
                    <th style="padding: 12px; text-align: center;">Update Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">#<?php echo $product['id']; ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($product['name']); ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td style="padding: 12px; text-align: right;">₹<?php echo number_format($product['price'], 2); ?></td>
                        <td style="padding: 12px; text-align: center; color: <?php echo $product['stock'] > 5 ? 'var(--success)' : 'var(--danger)'; ?>;">
                            <?php echo $product['stock']; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <form method="POST" action="" style="display: flex; gap: 10px; justify-content: center;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="number" name="stock" value="<?php echo $product['stock']; ?>" min="0" style="width: 80px; padding: 5px;">
                                <button type="submit" name="update_inventory" class="btn" style="padding: 5px 10px;">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding: 20px; text-align: center;">No products found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <script src="../assets/js/script.js"></script>
</body>
</html>