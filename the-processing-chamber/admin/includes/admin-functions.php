<?php
// admin/includes/admin-functions.php
// Administrative helper functions for dashboard, reports and quick queries.
// These are intentionally thin wrappers over PDO queries and return arrays for templates.
// Do not echo or send headers from these helpers.
// Admin-specific functions

// Get admin dashboard stats
function getAdminStats() {
    global $pdo;
    
    $stats = [];
    
    // Today's orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
    $stats['today_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Today's revenue
    $stmt = $pdo->query("SELECT SUM(total) as total FROM orders WHERE DATE(created_at) = CURDATE() AND payment_status = 'completed'");
    $stats['today_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Low stock items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE stock < 5");
    $stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(total) as total FROM orders WHERE payment_status = 'completed'");
    $stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    return $stats;
}

// Get recent orders for admin
function getRecentOrders($limit = 5) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get low stock products for admin
function getLowStockProducts($limit = 5) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE stock < 5 ORDER BY stock ASC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get sales data for charts
function getSalesData($days = 30) {
    global $pdo;
    
    $sales_data = [];
    
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $sales_data[$date] = 0;
    }
    
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, SUM(total) as total 
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND payment_status = 'completed'
        GROUP BY DATE(created_at)
    ");
    $stmt->bindValue(1, $days, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($result as $row) {
        $sales_data[$row['date']] = (float)$row['total'];
    }
    
    return $sales_data;
}

// Get order status distribution
function getOrderStatusDistribution() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM orders 
        GROUP BY status
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get payment method distribution
function getPaymentMethodDistribution() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT payment_method, COUNT(*) as count 
        FROM orders 
        GROUP BY payment_method
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}