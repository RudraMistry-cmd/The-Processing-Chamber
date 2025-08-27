<?php
/**
 * admin/index.php — Admin dashboard
 *
 * Lightweight dashboard page. This file prepares simple stats and renders the
 * admin template. Keep heavy queries or business logic in helpers where possible.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/admin-functions.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('admin/login.php');
}

// Get dashboard stats
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
$today_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT SUM(total) as total FROM orders WHERE DATE(created_at) = CURDATE()");
$today_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock < 5");
$low_stock = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get recent orders
$stmt = $pdo->query("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get low stock products
$stmt = $pdo->query("SELECT * FROM products WHERE stock < 5 ORDER BY stock ASC LIMIT 5");
$low_stock_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total revenue (completed payments)
$stmt = $pdo->query("SELECT SUM(total) as total_revenue FROM orders WHERE payment_status = 'completed'");
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

// Sales data for last 30 days (uses admin helper)
$sales_data = getSalesData(30);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - The Processing Chamber</title>
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
            <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="categories.php"><i class="fas fa-list"></i> Categories</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="inventory.php"><i class="fas fa-warehouse"></i> Inventory</a></li>
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
            <h1>Admin Dashboard</h1>
            <div class="user-actions">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 20px; border-radius: 8px;">
                <h3>Today's Orders</h3>
                <p style="font-size: 2rem; font-weight: bold;"><?php echo $today_orders; ?></p>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--success), var(--info)); color: white; padding: 20px; border-radius: 8px;">
                <h3>Today's Revenue</h3>
                <p style="font-size: 2rem; font-weight: bold;">₹<?php echo number_format($today_revenue, 2); ?></p>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--warning), var(--accent)); color: white; padding: 20px; border-radius: 8px;">
                <h3>Low Stock Items</h3>
                <p style="font-size: 2rem; font-weight: bold;"><?php echo $low_stock; ?></p>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--info), var(--primary)); color: white; padding: 20px; border-radius: 8px;">
                <h3>Total Users</h3>
                <p style="font-size: 2rem; font-weight: bold;"><?php echo $total_users; ?></p>
            </div>
        </div>

        <!-- Revenue Summary -->
        <div style="display:flex; gap:20px; align-items:center; margin-bottom: 30px;">
            <div style="flex:1; background: linear-gradient(135deg, #0f172a, #0b1220); color: white; padding: 18px; border-radius:8px;">
                <h3>Total Revenue (completed)</h3>
                <p style="font-size:2rem; font-weight:bold;">₹<?php echo number_format($total_revenue,2); ?></p>
            </div>

            <div style="flex:2; background: var(--card-bg); padding: 12px; border-radius:8px;">
                <h3>Sales (last 30 days)</h3>
                <?php
                // Simple inline sparkline using SVG
                $values = array_values($sales_data);
                $max = max($values) ?: 1;
                $w = 600; $h = 120; $pts = [];
                foreach ($values as $i => $v) {
                    $x = ($i / max(1, count($values)-1)) * $w;
                    $y = $h - ($v / $max) * $h;
                    $pts[] = "$x,$y";
                }
                ?>
                <svg width="100%" viewBox="0 0 <?php echo $w; ?> <?php echo $h; ?>" preserveAspectRatio="none" style="height:120px;">
                    <polyline points="<?php echo implode(' ', $pts); ?>" fill="none" stroke="var(--primary)" stroke-width="2" />
                </svg>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <?php if (count($low_stock_products) > 0): ?>
        <div class="section" style="margin-bottom: 30px;">
            <h2 class="section-title">Low Stock Alert</h2>
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
                            <a href="inventory.php?action=edit&id=<?php echo $product['id']; ?>" class="btn">Restock</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Orders -->
        <div class="section">
            <h2 class="section-title">Recent Orders</h2>
            <table style="width: 100%; border-collapse: collapse; background-color: var(--card-bg); border-radius: 8px; overflow: hidden;">
                <thead>
                    <tr style="background-color: var(--primary); color: white;">
                        <th style="padding: 12px; text-align: left;">Order ID</th>
                        <th style="padding: 12px; text-align: left;">Customer</th>
                        <th style="padding: 12px; text-align: left;">Amount</th>
                        <th style="padding: 12px; text-align: left;">Status</th>
                        <th style="padding: 12px; text-align: left;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_orders) > 0): ?>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px;">#<?php echo $order['id']; ?></td>
                            <td style="padding: 12px;"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td style="padding: 12px;">₹<?php echo number_format($order['total'], 2); ?></td>
                            <td style="padding: 12px;">
                                <span style="padding: 4px 8px; border-radius: 4px; background-color: 
                                    <?php 
                                    switch($order['status']) {
                                        case 'pending': echo 'var(--warning);'; break;
                                        case 'processing': echo 'var(--info);'; break;
                                        case 'shipped': echo 'var(--primary);'; break;
                                        case 'delivered': echo 'var(--success);'; break;
                                        case 'cancelled': echo 'var(--danger);'; break;
                                        default: echo 'var(--gray);';
                                    }
                                    ?>
                                "><?php echo ucfirst($order['status']); ?></span>
                            </td>
                            <td style="padding: 12px;"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="padding: 20px; text-align: center;">No recent orders</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
</body>
</html>