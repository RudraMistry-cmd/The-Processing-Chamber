<?php
/**
 * Products listing
 *
 * Displays products with optional filtering and sorting via GET parameters.
 * The query building here is intentionally explicit and procedural to make
 * it easy for developers to reason about parameter binding and SQL.
 */
require_once __DIR__ . '/../includes/header.php';

$page_title = "Products";

// Get filters from URL
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;

// Build query
$query = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($category_id > 0) {
    $query .= " AND category_id = ?";
    $params[] = $category_id;
}

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($min_price > 0) {
    $query .= " AND price >= ?";
    $params[] = $min_price;
}

if ($max_price > 0) {
    $query .= " AND price <= ?";
    $params[] = $max_price;
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'name':
        $query .= " ORDER BY name ASC";
        break;
    default:
        $query .= " ORDER BY created_at DESC";
        break;
}

// Get products
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category tree for filter (parents with nested children)
$categories = getCategoryTree();
?>

<h2 class="section-title">Products</h2>

<div class="products-container" style="display: flex; gap: 20px;">
    <!-- Filters Sidebar -->
    <div class="filters" style="flex: 0 0 250px;">
        <div class="filter-card" style="background-color: var(--card-bg); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3>Filters</h3>
            
            <form method="GET" action="">
                <?php if (isset($_GET['category'])): ?>
                    <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                <?php endif; ?>
                
                <?php if (isset($_GET['search'])): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Price Range</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" name="min_price" placeholder="Min" value="<?php echo $min_price; ?>" style="width: 100%;">
                        <input type="number" name="max_price" placeholder="Max" value="<?php echo $max_price; ?>" style="width: 100%;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Sort By</label>
                    <select name="sort" onchange="this.form.submit()" style="width: 100%;">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                    </select>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">Apply Filters</button>
                <a href="products.php" class="btn" style="width: 100%; margin-top: 10px; background-color: var(--danger);">Reset Filters</a>
            </form>
        </div>
        
        <div class="categories-list" style="background-color: var(--card-bg); padding: 20px; border-radius: 8px;">
            <h3>Categories</h3>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px;">
                    <a href="products.php" style="color: var(--body-text); text-decoration: none; font-weight: <?php echo $category_id == 0 ? 'bold' : 'normal'; ?>;">All Categories</a>
                </li>
                <?php foreach ($categories as $parent): ?>
                    <li style="margin-bottom: 10px; padding: 6px 0;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="width:22px; text-align:center;">
                                <?php if (!empty($parent['icon'])): ?>
                                    <i class="<?php echo htmlspecialchars($parent['icon']); ?>"></i>
                                <?php else: ?>
                                    <i class="fas fa-cog"></i>
                                <?php endif; ?>
                            </span>
                            <a href="products.php?category=<?php echo $parent['id']; ?>" style="color: var(--body-text); text-decoration: none; font-weight: <?php echo $category_id == $parent['id'] ? 'bold' : 'normal'; ?>;">
                                <?php echo htmlspecialchars($parent['name']); ?>
                            </a>
                        </div>
                        <?php if (!empty($parent['children'])): ?>
                            <ul style="list-style:none; padding-left: 28px; margin-top:8px;">
                                <?php foreach ($parent['children'] as $child): ?>
                                    <?php if (trim(strtolower($child['name'])) === trim(strtolower($parent['name']))) continue; // skip duplicate-named child ?>
                                    <li style="margin-bottom:6px;">
                                        <a href="products.php?category=<?php echo $child['id']; ?>" style="color: var(--body-text); text-decoration: none; font-weight: <?php echo $category_id == $child['id'] ? 'bold' : 'normal'; ?>;">
                                            <?php if (!empty($child['icon'])): ?><i class="<?php echo htmlspecialchars($child['icon']); ?>" style="margin-right:8px;"></i><?php endif; ?>
                                            <?php echo htmlspecialchars($child['name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <!-- Products Grid -->
    <div class="products-list" style="flex: 1;">
        <?php if (!empty($search)): ?>
            <p>Search results for: <strong><?php echo htmlspecialchars($search); ?></strong></p>
        <?php endif; ?>
        
        <?php if (count($products) > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-img">
                            <img src="<?php echo SITE_URL; ?>/assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">

                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                            <div class="product-stock" style="margin-bottom: 10px; color: <?php echo $product['stock'] > 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                                <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                            </div>
                            <div class="product-actions">
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn">Details</a>
                                <?php if ($product['stock'] > 0): ?>
                                <a href="<?php echo SITE_URL; ?>/pages/cart.php?action=add&id=<?php echo $product['id']; ?>" class="btn">Add to Cart</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <h3>No products found</h3>
                <p>Try adjusting your filters or search terms</p>
                <a href="products.php" class="btn">View All Products</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>