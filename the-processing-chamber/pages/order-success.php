<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php
$page_title = "Order Success";

// Check if order ID is provided
if (!isset($_GET['id'])) {
    redirect('index.php');
}

$order_id = intval($_GET['id']);

// Get order details
$stmt = $pdo->prepare("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Verify that the order belongs to the current user (if not admin)
if (!isAdmin() && $order['user_id'] != $_SESSION['user_id']) {
    redirect('index.php');
}

// Get order items
$stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image as product_image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="order-success-container">
    <div style="text-align: center; padding: 40px 20px; background-color: var(--card-bg); border-radius: 8px; margin-bottom: 30px;">
        <div style="font-size: 4rem; color: var(--success); margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>Order Placed Successfully!</h1>
        <p>Thank you for your order. Your order details are shown below.</p>
        <p>Order ID: <strong>#<?php echo $order_id; ?></strong></p>
        
        <div style="margin-top: 30px;">
            <a href="products.php" class="btn" style="margin-right: 10px;">Continue Shopping</a>
            <a href="profile.php" class="btn">View Order History</a>
        </div>
    </div>

    <!-- Order Details -->
    <div class="order-details">
        <h2 class="section-title">Order Details</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <!-- Order Summary -->
            <div style="background-color: var(--card-bg); padding: 20px; border-radius: 8px;">
                <h3>Order Information</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
                    <div><strong>Order ID:</strong></div>
                    <div>#<?php echo $order_id; ?></div>
                    
                    <div><strong>Order Date:</strong></div>
                    <div><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></div>
                    
                    <div><strong>Order Status:</strong></div>
                    <div>
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
                    </div>
                    
                    <div><strong>Payment Method:</strong></div>
                    <div><?php echo strtoupper($order['payment_method']); ?></div>
                    
                    <div><strong>Payment Status:</strong></div>
                    <div>
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
                    </div>
                </div>
            </div>
            
            <!-- Shipping Address -->
            <div style="background-color: var(--card-bg); padding: 20px; border-radius: 8px;">
                <h3>Shipping Address</h3>
                <p style="margin-top: 15px; white-space: pre-wrap;"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
            </div>
        </div>
        
        <!-- Order Items -->
        <h3>Order Items</h3>
        <table style="width: 100%; border-collapse: collapse; background-color: var(--card-bg); border-radius: 8px; overflow: hidden; margin-bottom: 30px;">
            <thead>
                <tr style="background-color: var(--primary); color: white;">
                    <th style="padding: 12px; text-align: left;">Product</th>
                    <th style="padding: 12px; text-align: center;">Price</th>
                    <th style="padding: 12px; text-align: center;">Quantity</th>
                    <th style="padding: 12px; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 12px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                            <div>
                                <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 12px; text-align: center;"><?php echo formatPrice($item['price']); ?></td>
                    <td style="padding: 12px; text-align: center;"><?php echo $item['quantity']; ?></td>
                    <td style="padding: 12px; text-align: right;"><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="padding: 12px; text-align: right; font-weight: bold;">Total:</td>
                    <td style="padding: 12px; text-align: right; font-weight: bold;"><?php echo formatPrice($order['total']); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>