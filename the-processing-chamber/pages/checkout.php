<?php
/**
 * Checkout flow
 *
 * This page enforces authentication and processes order creation on POST.
 * Keep validation simple here; complex payment integrations should be separated
 * into a service layer when the project grows.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

$page_title = "Checkout";

// Check if user is logged in
if (!isLoggedIn()) {
    // store the pages path so redirect() builds SITE_URL/pages/checkout.php
    $_SESSION['redirect_url'] = 'pages/checkout.php';
    redirect('pages/login.php');
}

// Check if cart is empty
$cart_items = getCartItems();
if (count($cart_items) === 0) {
    redirect('pages/cart.php');
}

// Process checkout form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];
    $shipping_address = trim($_POST['shipping_address']);
    
    // Validate inputs
    if (empty($shipping_address)) {
        $error = "Please enter your shipping address.";
    } else {
        // Create order
        try {
            $pdo->beginTransaction();
            
            // Insert order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, payment_method, shipping_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], getCartTotal(), $payment_method, $shipping_address]);
            $order_id = $pdo->lastInsertId();
            
            // Insert order items
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
                
                // Update product stock
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['id']]);
            }
            
            // Build a JSON snapshot of the ordered items so order-details can display them even
            // if product records change later. Store basic fields: id, name, quantity, price, subtotal, image.
            $items_snapshot = [];
            foreach ($cart_items as $item_snapshot) {
                $items_snapshot[] = [
                    'id' => $item_snapshot['id'],
                    'name' => $item_snapshot['name'],
                    'quantity' => $item_snapshot['quantity'],
                    'price' => $item_snapshot['price'],
                    'subtotal' => isset($item_snapshot['subtotal']) ? $item_snapshot['subtotal'] : ($item_snapshot['price'] * $item_snapshot['quantity']),
                    'image' => isset($item_snapshot['image']) ? $item_snapshot['image'] : ''
                ];
            }
            $items_json = json_encode($items_snapshot);
            // Update the orders row with the JSON snapshot (assumes orders.items column exists and is text)
            $stmt = $pdo->prepare("UPDATE orders SET items = ? WHERE id = ?");
            $stmt->execute([$items_json, $order_id]);
            
            $pdo->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            
            // Redirect to success page inside pages/
            redirect('pages/order-success.php?id=' . $order_id);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Order failed: " . $e->getMessage();
        }
    }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<h2 class="section-title">Checkout</h2>

<div class="checkout-container" style="display: flex; gap: 30px;">
    <!-- Order Summary -->
    <div class="order-summary" style="flex: 1;">
        <div style="background-color: var(--card-bg); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3>Order Summary</h3>
            
            <?php foreach ($cart_items as $item): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                        <div>
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p>Quantity: <?php echo $item['quantity']; ?></p>
                        </div>
                    </div>
                    <div><?php echo formatPrice($item['subtotal']); ?></div>
                </div>
            <?php endforeach; ?>
            
            <div style="display: flex; justify-content: space-between; margin-top: 15px; font-weight: bold; font-size: 1.2rem;">
                <span>Total:</span>
                <span><?php echo formatPrice(getCartTotal()); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Checkout Form -->
    <div class="checkout-form" style="flex: 1;">
        <div style="background-color: var(--card-bg); padding: 20px; border-radius: 8px;">
            <h3>Shipping & Payment</h3>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="shipping_address">Shipping Address</label>
                    <textarea id="shipping_address" name="shipping_address" rows="4" required><?php echo isset($_POST['shipping_address']) ? htmlspecialchars($_POST['shipping_address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="cod">Cash on Delivery</option>
                        <option value="card">Credit/Debit Card</option>
                        <option value="upi">UPI</option>
                        <option value="netbanking">Net Banking</option>
                    </select>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">Place Order</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>