<?php
// Admin authentication
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';
// Redirect if not admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        // Add new category
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
        
        if (empty($name)) {
            $error = "Category name is required.";
        } else {
            $icon = trim($_POST['icon'] ?? '');
            $stmt = $pdo->prepare("INSERT INTO categories (name, description, parent_id, icon) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $description, $parent_id, $icon]);
            
            $success = "Category added successfully!";
        }
    } elseif (isset($_POST['update_category'])) {
        // Update category
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
        
        if (empty($name)) {
            $error = "Category name is required.";
        } else {
            $icon = trim($_POST['icon'] ?? '');
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, parent_id = ?, icon = ? WHERE id = ?");
            $stmt->execute([$name, $description, $parent_id, $icon, $id]);
            
            $success = "Category updated successfully!";
        }
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Check if category has products
    $stmt = $pdo->prepare("SELECT COUNT(*) as product_count FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['product_count'] > 0) {
        $error = "Cannot delete category with products. Please reassign or delete the products first.";
    } else {
        // Check if category has subcategories
        $stmt = $pdo->prepare("SELECT COUNT(*) as subcategory_count FROM categories WHERE parent_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['subcategory_count'] > 0) {
            $error = "Cannot delete category with subcategories. Please delete or reassign the subcategories first.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            $success = "Category deleted successfully!";
        }
    }
}

// Get all categories
$stmt = $pdo->query("SELECT c1.*, c2.name as parent_name FROM categories c1 LEFT JOIN categories c2 ON c1.parent_id = c2.id ORDER BY c1.name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get parent categories for dropdown
$stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
$parent_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Edit mode: if action=edit load the category data to prefill the form
$edit_mode = false;
$edit_category = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $s = $pdo->prepare("SELECT * FROM categories WHERE id = ? LIMIT 1");
    $s->execute([$edit_id]);
    $edit_category = $s->fetch(PDO::FETCH_ASSOC);
    if ($edit_category) {
        $edit_mode = true;
    }
}

// Set page title
$page_title = "Manage Categories";
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
            <li><a href="categories.php" class="active"><i class="fas fa-list"></i> Categories</a></li>
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
            <h1>Manage Categories</h1>
            <div class="user-actions">
                <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add Category Form -->
    <div class="form-container" style="margin-bottom: 30px;">
        <h2><?php echo $edit_mode ? 'Edit Category' : 'Add New Category'; ?></h2>
        <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Category Name</label>
            <input type="text" id="name" name="name" required value="<?php echo $edit_mode ? htmlspecialchars($edit_category['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="icon">Icon (CSS class, optional)</label>
                    <input type="text" id="icon" name="icon" placeholder="e.g. fas fa-headphones" value="<?php echo $edit_mode ? htmlspecialchars($edit_category['icon'] ?? '') : ''; ?>">
                    <small>Enter a Font Awesome class or leave blank for default icon.</small>
                </div>
                
                <div class="form-group">
                    <label for="parent_id">Parent Category (optional)</label>
                    <select id="parent_id" name="parent_id">
                        <option value="">No Parent (Top-level Category)</option>
                            <?php foreach ($parent_categories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>" <?php echo ($edit_mode && $edit_category['parent_id'] == $parent['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($parent['name']); ?></option>
                            <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($edit_mode): ?>
                    <input type="hidden" name="id" value="<?php echo (int)$edit_category['id']; ?>">
                    <button type="submit" name="update_category" class="btn">Update Category</button>
                    <a href="categories.php" class="btn" style="background:#777; margin-left:8px;">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="add_category" class="btn">Add Category</button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Categories List -->
        <h2 class="section-title">All Categories</h2>
        
        <table style="width: 100%; border-collapse: collapse; background-color: var(--card-bg); border-radius: 8px; overflow: hidden; margin-bottom: 30px;">
            <thead>
                <tr style="background-color: var(--primary); color: white;">
                    <th style="padding: 12px; text-align: left;">ID</th>
                    <th style="padding: 12px; text-align: left;">Name</th>
                    <th style="padding: 12px; text-align: left;">Description</th>
                    <th style="padding: 12px; text-align: left;">Parent Category</th>
                    <th style="padding: 12px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($categories) > 0): ?>
                    <?php foreach ($categories as $category): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">#<?php echo $category['id']; ?></td>
                                <td style="padding: 12px;">
                                    <?php if (!empty($category['icon'])): ?><i class="<?php echo htmlspecialchars($category['icon']); ?>" style="margin-right:8px;"></i><?php endif; ?><?php echo htmlspecialchars($category['name']); ?>
                                </td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($category['description']); ?></td>
                        <td style="padding: 12px;"><?php echo $category['parent_name'] ? htmlspecialchars($category['parent_name']) : '—'; ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" style="color: var(--info); margin-right: 10px;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="categories.php?action=delete&id=<?php echo $category['id']; ?>" onclick="return confirm('Are you sure you want to delete this category?')" style="color: var(--danger);">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding: 20px; text-align: center;">No categories found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <script src="../assets/js/script.js"></script>
</body>
</html>