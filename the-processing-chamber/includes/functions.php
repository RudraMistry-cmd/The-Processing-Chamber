<?php
/**
 * Application helper functions
 *
 * These functions are intentionally procedural and small. They are general
 * utility functions that many pages across the site depend on. Keep changes
 * here backwards-compatible because many templates include this file.
 */

/**
 * Redirect to a path inside the application.
 *
 * @param string $page Relative path (example: 'pages/login.php' or 'admin/index.php')
 * @return void
 */
function redirect($page) {
    header("Location: " . SITE_URL . "/$page");
    exit();
}

/**
 * Return true if a user session exists.
 * Useful to guard pages and show different UI in headers.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Return true if the current user has the admin role.
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Fetch a category name by its ID.
 * Returns 'Uncategorized' when the id is missing or invalid.
 */
function getCategoryName($category_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    return $category ? $category['name'] : 'Uncategorized';
}

/**
 * Format a numeric price for display. Uses Indian Rupee symbol and two decimals.
 */
function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

/**
 * Return an array of featured products. Uses a limit parameter to cap results.
 * This function returns raw rows from the database; templates are responsible
 * for escaping output when rendering.
 */
function getFeaturedProducts($limit = 8) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE featured = 1 ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get products by category
function getProductsByCategory($category_id, $limit = 12) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $category_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all categories
function getAllCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Build a category tree (parents with nested 'children' arrays).
 * Useful for rendering nested menus. The implementation is iterative and
 * keeps the same array structure the rest of the app expects.
 */
function getCategoryTree() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY parent_id IS NULL DESC, parent_id, name");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tree = [];
    $byId = [];
    foreach ($rows as $r) {
        $r['children'] = [];
        $byId[$r['id']] = $r;
    }

    foreach ($byId as $id => $cat) {
        if (empty($cat['parent_id'])) {
            $tree[$id] = &$byId[$id];
        } else {
            $pid = $cat['parent_id'];
            if (isset($byId[$pid])) {
                $byId[$pid]['children'][] = &$byId[$id];
            } else {
                // parent missing, treat as top-level
                $tree[$id] = &$byId[$id];
            }
        }
    }

    // Return numeric-indexed array of parents
    return array_values($tree);
}

// Add to cart
function addToCart($product_id, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
}

// Remove from cart
function removeFromCart($product_id) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
}

// Get cart items with product details
function getCartItems() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [];
    }
    
    global $pdo;
    $cart_items = [];
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $_SESSION['cart'][$product['id']],
            'subtotal' => $product['price'] * $_SESSION['cart'][$product['id']]
        ];
    }
    
    return $cart_items;
}

// Get cart total
function getCartTotal() {
    $cart_items = getCartItems();
    $total = 0;
    
    foreach ($cart_items as $item) {
        $total += $item['subtotal'];
    }
    
    return $total;
}

// Get cart count
function getCartCount() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    
    return array_sum($_SESSION['cart']);
}



?>