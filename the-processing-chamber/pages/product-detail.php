<?php
/**
 * Product detail page
 *
 * Loads a single product by `id` (GET) and displays its details. Handles a
 * simple POST action to add the product to the cart (quantity limited by stock).
 *
 * Keep presentation and business logic separated: this file focuses on
 * orchestrating helpers and rendering templates.
 */
require_once __DIR__ . '/../includes/header.php';

$page_title = "Product Details";

// Check if product ID is provided
if (!isset($_GET['id'])) {
    redirect('products.php');
}

$product_id = intval($_GET['id']);

// Get product details
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<div class='alert alert-danger'>Product not found.</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Get specifications if they exist
$specifications = [];
if (!empty($product['specifications'])) {
    $specifications = json_decode($product['specifications'], true);
}

// Update page title
$page_title = $product['name'] . " - Product Details";

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = intval($_POST['quantity']);
    
    if ($quantity > 0 && $quantity <= $product['stock']) {
        addToCart($product_id, $quantity);
        $success = "Product added to cart successfully!";
    } else {
        $error = "Invalid quantity selected.";
    }
}
?>

<div class="product-detail-container">
    <div class="breadcrumb" style="margin-bottom: 20px;">
        <a href="index.php">Home</a> &gt; 
        <a href="products.php">Products</a> &gt; 
        <a href="products.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a> &gt; 
        <span><?php echo htmlspecialchars($product['name']); ?></span>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="product-detail" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- Product Image -->
        <div class="product-image">
            <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; border-radius: 8px;">
        </div>

        <!-- Product Info -->
        <div class="product-info">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="product-price" style="font-size: 1.5rem; color: var(--primary); font-weight: bold; margin: 15px 0;">
                <?php echo formatPrice($product['price']); ?>
            </div>

            <div class="product-stock" style="margin-bottom: 15px; color: <?php echo $product['stock'] > 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                <?php echo $product['stock'] > 0 ? 'In Stock (' . $product['stock'] . ' available)' : 'Out of Stock'; ?>
            </div>

            <div class="product-description" style="margin-bottom: 20px;">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <?php if ($product['stock'] > 0): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 100px;">
                </div>
                
                <button type="submit" name="add_to_cart" class="btn" style="margin-right: 10px;">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
                
                <a href="<?php echo SITE_URL; ?>/pages/cart.php?action=add&id=<?php echo $product['id']; ?>&quantity=1" class="btn" style="background-color: var(--accent);">
                    <i class="fas fa-bolt"></i> Buy Now
                </a>
            </form>
            <?php else: ?>
                <div class="alert alert-warning">This product is currently out of stock.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Specifications -->
    <?php if (!empty($specifications)): ?>
    <div class="product-specifications" style="margin-top: 40px;">
        <h2>Specifications</h2>
        <table style="width: 100%; border-collapse: collapse; background-color: var(--card-bg); border-radius: 8px; overflow: hidden;">
            <tbody>
                <?php foreach ($specifications as $key => $value): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 12px; font-weight: bold; width: 200px;"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($value); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Related Products -->
    <?php
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT 4");
    $stmt->execute([$product['category_id'], $product_id]);
    $related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($related_products) > 0):
    ?>
    <div class="related-products" style="margin-top: 40px;">
        <h2>Related Products</h2>
        <div class="products-grid">
            <?php foreach ($related_products as $related_product): ?>
                <div class="product-card">
                    <div class="product-img">
                        <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo htmlspecialchars($related_product['image']); ?>" alt="<?php echo htmlspecialchars($related_product['name']); ?>">
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($related_product['name']); ?></h3>
                        <div class="product-price"><?php echo formatPrice($related_product['price']); ?></div>
                        <div class="product-actions">
                            <a href="product-detail.php?id=<?php echo $related_product['id']; ?>" class="btn">Details</a>
                            <?php if ($related_product['stock'] > 0): ?>
                                <a href="<?php echo SITE_URL; ?>/pages/cart.php?action=add&id=<?php echo $related_product['id']; ?>" class="btn">Add to Cart</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>