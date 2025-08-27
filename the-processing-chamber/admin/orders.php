<?php
/**
 * admin/orders.php — Orders list and actions (admin)
 *
 * Simple admin list for orders with optional filters. Actions (status updates)
 * are handled at the top of the file before rendering to allow redirects.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $payment_status = $_POST['payment_status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
    $stmt->execute([$status, $payment_status, $order_id]);
    
    $success = "Order status updated successfully!";
}

// Handle order filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$payment_filter = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';

// Build query
$query = "SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

if (!empty($payment_filter)) {
    $query .= " AND o.payment_status = ?";
    $params[] = $payment_filter;
}

$query .= " ORDER BY o.created_at DESC";

// Get orders
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title
$page_title = "Manage Orders";
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
            <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a></li>
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
            <h1>Manage Orders</h1>
            <div class="user-actions">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Order Filters -->
        <div class="filters" style="background-color: var(--card-bg); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3>Filter Orders</h3>
            <form method="GET" action="">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="status">Order Status</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_status">Payment Status</label>
                        <select id="payment_status" name="payment_status">
                            <option value="">All Payment Statuses</option>
                            <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $payment_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo $payment_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn" style="margin-right: 10px;">Apply Filters</button>
                        <a href="orders.php" class="btn" style="background-color: var(--danger);">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Orders List -->
        <h2 class="section-title">All Orders</h2>
        
        <table style="width: 100%; border-collapse: collapse; background-color: var(--card-bg); border-radius: 8px; overflow: hidden; margin-bottom: 30px;">
            <thead>
                <tr style="background-color: var(--primary); color: white;">
                    <th style="padding: 12px; text-align: left;">Order ID</th>
                    <th style="padding: 12px; text-align: left;">Customer</th>
                    <th style="padding: 12px; text-align: right;">Amount</th>
                    <th style="padding: 12px; text-align: left;">Payment Method</th>
                    <th style="padding: 12px; text-align: left;">Order Status</th>
                    <th style="padding: 12px; text-align: left;">Payment Status</th>
                    <th style="padding: 12px; text-align: left;">Date</th>
                    <th style="padding: 12px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($orders) > 0): ?>
                    <?php foreach ($orders as $order): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">#<?php echo $order['id']; ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td style="padding: 12px; text-align: right;">₹<?php echo number_format($order['total'], 2); ?></td>
                        <td style="padding: 12px;"><?php echo strtoupper($order['payment_method']); ?></td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 8px; border-radius: 4px; background-color: 
                                <?php 
                                switch($order['status']) {
                                    case 'pending': echo 'var(--warning);'; break;
                                    case 'processing': echo 'var(--info);'; break;
                                    case 'shipped': echo 'var(--primary);'; break;
                                    case 'delivered': echo 'var(--success);'; break;
                                    case 'cancelled': echo 'var(--danger);'; break;
                                    case 'refunded': echo 'var(--secondary);'; break;
                                    default: echo 'var(--gray);';
                                }
                                ?>
                            "><?php echo ucfirst($order['status']); ?></span>
                        </td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 8px; border-radius: 4px; background-color: 
                                <?php 
                                switch($order['payment_status']) {
                                    case 'pending': echo 'var(--warning);'; break;
                                    case 'completed': echo 'var(--success);'; break;
                                    case 'failed': echo 'var(--danger);'; break;
                                    default: echo 'var(--gray);';
                                }
                                ?>
                            "><?php echo ucfirst($order['payment_status']); ?></span>
                        </td>
                        <td style="padding: 12px;"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" style="color: var(--info); margin-right: 10px;">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="orders.php?action=edit&id=<?php echo $order['id']; ?>" style="color: var(--warning);">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="padding: 20px; text-align: center;">No orders found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <script src="../assets/js/script.js"></script>
</body>
</html>