<?php
/**
 * Admin products management
 *
 * Handles add/update/delete operations for products and renders the product list.
 * File is procedural and performs input validation and file uploads inline.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        // Add new product
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $category_id = intval($_POST['category_id']);
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Handle image upload
        $image = 'default.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/products/';
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = uniqid() . '.' . $file_extension;
            
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
        }
        
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, image, category_id, featured) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock, $image, $category_id, $featured]);
        
        $success = "Product added successfully!";
    } elseif (isset($_POST['update_product'])) {
        // Update product
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $category_id = intval($_POST['category_id']);
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        // Handle image upload if new image is provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/images/products/';
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = uniqid() . '.' . $file_extension;
            
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
            
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ?, category_id = ?, featured = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $stock, $image, $category_id, $featured, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, featured = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $stock, $category_id, $featured, $id]);
        }
        
        $success = "Product updated successfully!";
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Prevent deleting products that are referenced in orders (order_items)
    $check = $pdo->prepare("SELECT COUNT(*) as cnt FROM order_items WHERE product_id = ?");
    $check->execute([$id]);
    $count = (int) ($check->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

    if ($count > 0) {
    // Product is referenced by at least one order item; refuse to delete by default
    // Fetch a small list of referencing order IDs to show admin why the delete is blocked
    $refs = $pdo->prepare("SELECT DISTINCT order_id FROM order_items WHERE product_id = ? LIMIT 10");
    $refs->execute([$id]);
    $orderIds = array_map(function($r){ return $r['order_id']; }, $refs->fetchAll(PDO::FETCH_ASSOC));

    $error = "Cannot delete product because it's referenced in existing orders: ";
    $error .= implode(', ', $orderIds);
    $error .= ". You can archive the product or force-delete only after explicit confirmation (not recommended).";
    } else {
        // Attempt to remove product image file if it's not the default
        $imgStmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $imgStmt->execute([$id]);
        $prod = $imgStmt->fetch(PDO::FETCH_ASSOC);
        if ($prod && !empty($prod['image']) && $prod['image'] !== 'default.jpg') {
            $imgPath = __DIR__ . '/../assets/images/products/' . $prod['image'];
            if (file_exists($imgPath)) {
                @unlink($imgPath);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        $success = "Product deleted successfully!";
    }
}

// Handle explicit force-delete via POST (admin must confirm)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_delete_confirm']) && isset($_POST['force_delete_id'])) {
    $fid = intval($_POST['force_delete_id']);
    // Double-check references before forcing delete
    $chk = $pdo->prepare("SELECT COUNT(*) as cnt FROM order_items WHERE product_id = ?");
    $chk->execute([$fid]);
    $cnt = (int)($chk->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);

    if ($cnt > 0) {
        // Still referenced, refuse to hard-delete as it would break referential integrity
        $error = "Cannot force-delete: product still referenced by existing orders (count={$cnt}). Use archival workflow instead.";
    } else {
        // Safe to delete
        $imgStmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $imgStmt->execute([$fid]);
        $prod = $imgStmt->fetch(PDO::FETCH_ASSOC);
        if ($prod && !empty($prod['image']) && $prod['image'] !== 'default.jpg') {
            $imgPath = __DIR__ . '/../assets/images/products/' . $prod['image'];
            if (file_exists($imgPath)) @unlink($imgPath);
        }
        $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $del->execute([$fid]);
        $success = "Product force-deleted successfully.";
    }
}

// Handle edit action (load product for editing)
$editProduct = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $eid = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$eid]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$editProduct) {
        $error = "Product not found for editing.";
    }
}

// Get all products
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories
$categories = getAllCategories();

// Set page title
$page_title = "Manage Products";
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
            <li><a href="products.php" class="active"><i class="fas fa-box"></i> Products</a></li>
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
            <h1>Manage Products</h1>
            <div class="user-actions">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($error) && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])): ?>
            <div style="background: #fff6f6; border: 1px solid #ffd6d6; padding: 12px; margin-bottom:12px; border-radius:6px;">
                <strong>Delete blocked.</strong>
                <p>The product is referenced by existing orders. If you still want to remove the product record (not recommended), you may attempt a force-delete but it will only succeed if there are no remaining references.</p>
                <form method="POST" action="">
                    <input type="hidden" name="force_delete_id" value="<?php echo intval($_GET['id']); ?>">
                    <button type="submit" name="force_delete_confirm" onclick="return confirm('Force-delete the product only if you are sure it is not used in orders. This is irreversible. Continue?')" class="btn" style="background: var(--danger);">Force Delete (Unsafe)</button>
                    <a href="products.php" class="btn" style="margin-left:8px;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>

        <!-- Add / Edit Product Form -->
        <div class="form-container" style="margin-bottom: 30px;">
            <h2><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <?php if ($editProduct): ?>
                    <input type="hidden" name="id" value="<?php echo $editProduct['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required value="<?php echo $editProduct ? htmlspecialchars($editProduct['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" required><?php echo $editProduct ? htmlspecialchars($editProduct['description']) : ''; ?></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="price">Price (₹)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo $editProduct ? htmlspecialchars($editProduct['price']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stock</label>
                        <input type="number" id="stock" name="stock" min="0" required value="<?php echo $editProduct ? htmlspecialchars($editProduct['stock']) : ''; ?>">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($editProduct && $editProduct['category_id'] == $category['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <?php if ($editProduct && !empty($editProduct['image'])): ?>
                            <div style="margin-top:8px;"><small>Current: <?php echo htmlspecialchars($editProduct['image']); ?></small></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="featured" <?php echo ($editProduct && $editProduct['featured']) ? 'checked' : ''; ?>> Featured Product
                    </label>
                </div>
                
                <?php if ($editProduct): ?>
                    <button type="submit" name="update_product" class="btn">Update Product</button>
                    <a href="products.php" class="btn" style="margin-left:8px;">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="add_product" class="btn">Add Product</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Products List -->
        <h2 class="section-title">All Products</h2>
        
        <table style="width: 100%; border-collapse: collapse; background-color: var(--card-bg); border-radius: 8px; overflow: hidden; margin-bottom: 30px;">
            <thead>
                <tr style="background-color: var(--primary); color: white;">
                    <th style="padding: 12px; text-align: left;">ID</th>
                    <th style="padding: 12px; text-align: left;">Image</th>
                    <th style="padding: 12px; text-align: left;">Name</th>
                    <th style="padding: 12px; text-align: left;">Category</th>
                    <th style="padding: 12px; text-align: right;">Price</th>
                    <th style="padding: 12px; text-align: center;">Stock</th>
                    <th style="padding: 12px; text-align: center;">Featured</th>
                    <th style="padding: 12px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">#<?php echo $product['id']; ?></td>
                        <td style="padding: 12px;">
                            <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                        </td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($product['name']); ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td style="padding: 12px; text-align: right;">₹<?php echo number_format($product['price'], 2); ?></td>
                        <td style="padding: 12px; text-align: center; color: <?php echo $product['stock'] > 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                            <?php echo $product['stock']; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php echo $product['featured'] ? '<i class="fas fa-star" style="color: var(--warning);"></i>' : '<i class="far fa-star"></i>'; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" style="color: var(--info); margin-right: 10px;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?')" style="color: var(--danger);">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="padding: 20px; text-align: center;">No products found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <script src="../assets/js/script.js"></script>
</body>
</html>