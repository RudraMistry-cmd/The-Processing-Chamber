<?php
/**
 * Shopping cart page
 *
 * Responsibilities:
 * - Handle lightweight cart actions (add/remove/update) via GET parameters.
 * - Render the current user's cart using helpers from includes/functions.php.
 *
 * Notes for maintainers:
 * - Action handling occurs before the header include so redirects can send headers.
 * - This file is intentionally procedural and simple to match the rest of the codebase.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = "Shopping Cart";

// Handle cart actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $action = $_GET['action'];

    switch ($action) {
        case 'add':
            $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
            addToCart($product_id, $quantity);
            break;

        case 'remove':
            removeFromCart($product_id);
            break;

        case 'update':
            $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                removeFromCart($product_id);
            }
            break;
    }

    // Redirect to avoid form resubmission
    redirect('pages/cart.php');
}

// Include header after action handling so redirects can send headers
require_once __DIR__ . '/../includes/header.php';

// Get cart items
$cart_items = getCartItems();
$cart_total = getCartTotal();
?>

<h2 class="section-title">Shopping Cart</h2>

<?php if (count($cart_items) > 0): ?>
    <div class="cart-container">
        <div class="cart-items">
            <table style="width: 100%; border-collapse: collapse; background-color: var(--card-bg); border-radius: 8px; overflow: hidden;">
                <thead>
                    <tr style="background-color: var(--primary); color: white;">
                        <th style="padding: 12px; text-align: left;">Product</th>
                        <th style="padding: 12px; text-align: center;">Price</th>
                        <th style="padding: 12px; text-align: center;">Quantity</th>
                        <th style="padding: 12px; text-align: right;">Subtotal</th>
                        <th style="padding: 12px; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                <div>
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px; text-align: center;"><?php echo formatPrice($item['price']); ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <form method="GET" action="<?php echo SITE_URL; ?>/pages/cart.php" style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" style="width: 60px; padding: 5px;">
                                <button type="submit" style="background: none; border: none; cursor: pointer; color: var(--primary);">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </form>
                        </td>
                        <td style="padding: 12px; text-align: right;"><?php echo formatPrice($item['subtotal']); ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="<?php echo SITE_URL; ?>/pages/cart.php?action=remove&id=<?php echo $item['id']; ?>" style="color: var(--danger);">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="cart-summary" style="margin-top: 30px; display: flex; justify-content: flex-end;">
            <div style="background-color: var(--card-bg); padding: 20px; border-radius: 8px; width: 300px;">
                <h3>Cart Summary</h3>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <span>Subtotal:</span>
                    <span><?php echo formatPrice($cart_total); ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <span>Shipping:</span>
                    <span>₹0.00</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-weight: bold; font-size: 1.2rem;">
                    <span>Total:</span>
                    <span><?php echo formatPrice($cart_total); ?></span>
                </div>
                
                <a href="<?php echo SITE_URL; ?>/pages/checkout.php" class="btn" style="width: 100%;">Proceed to Checkout</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div style="text-align: center; padding: 40px;">
        <h3>Your cart is empty</h3>
        <p>Add some products to your cart to continue shopping</p>
                <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn">Continue Shopping</a>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>