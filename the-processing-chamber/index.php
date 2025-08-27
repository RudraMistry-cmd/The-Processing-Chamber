<?php
/**
 * Home page
 *
 * Simple landing page that shows categories and featured products. This file
 * focuses on rendering and delegates data access to helpers (e.g., getAllCategories()).
 */
$page_title = "Home";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <h2>Power Your Dreams with Premium Hardware</h2>
    <p>Find the best computer components at competitive prices with fast delivery across India</p>
    <a href="<?php echo SITE_URL; ?>/pages/products.php" class="btn">Shop Now</a>
</section>

<!-- Categories Section -->
<h2 class="section-title">Shop by Category</h2>
<div class="categories">
    <?php
    $categories = getAllCategories();
    $icons = ['fa-microchip', 'fa-desktop', 'fa-memory', 'fa-hdd', 'fa-circuit-board', 'fa-fan', 'fa-bolt'];
    $i = 0;
    
    foreach ($categories as $category) {
        echo '<div class="category-card">';
        echo '<div class="category-icon">';
        echo '<i class="fas ' . $icons[$i % count($icons)] . '"></i>';
        echo '</div>';
        echo '<h3>' . htmlspecialchars($category['name']) . '</h3>';
        echo '<p>' . htmlspecialchars($category['description']) . '</p>';
        echo '<a href="' . SITE_URL . '/pages/products.php?category=' . $category['id'] . '" class="btn">Browse</a>';
        echo '</div>';
        $i++;
    }
    ?>
</div>

<!-- Featured Products -->
<h2 class="section-title">
    Featured Products
    <a href="<?php echo SITE_URL; ?>/pages/products.php" class="view-all">View All</a>
</h2>
<div class="products-grid">
    <?php
    $featured_products = getFeaturedProducts(8);
    
    if (count($featured_products) > 0) {
        foreach ($featured_products as $product) {
            echo '<div class="product-card">';
            echo '<div class="product-img">';
            echo '<img src="'  . SITE_URL . '/assets/images/products/' . htmlspecialchars($product['image']) . '" alt="' . htmlspecialchars($product['name']) .' " >';
            echo '</div>';
            echo '<div class="product-info">';
            echo '<h3 class="product-title">' . htmlspecialchars($product['name']) . '</h3>';
            echo '<div class="product-price">' . formatPrice($product['price']) . '</div>';
            echo '<div class="product-actions">';
            echo '<a href="' . SITE_URL . '/pages/product-detail.php?id=' . $product['id'] . '" class="btn">Details</a>';
            echo '<a href="' . SITE_URL . '/pages/cart.php?action=add&id=' . $product['id'] . '" class="btn">Add to Cart</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<p>No featured products found.</p>';
    }
    ?>
</div>

<?php
include 'includes/footer.php';
?>