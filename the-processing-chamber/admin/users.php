<?php
/**
 * admin/users.php — Manage users (admin)
 *
 * Allows administrators to edit, delete and list users. Actions are handled via
 * POST/GET at the top of the file so redirects can be performed before output.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
    // Optional password change by admin
    $new_password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['password_confirm']) ? trim($_POST['password_confirm']) : '';
        
        // Validate inputs
        if (empty($name) || empty($email)) {
            $error = "Please fill in all required fields.";
        } elseif ($new_password !== '' || $confirm_password !== '') {
            // If admin provided a new password, validate it
            if ($new_password !== $confirm_password) {
                $error = "Passwords do not match.";
            } elseif (strlen($new_password) < 8) {
                $error = "Password must be at least 8 characters.";
            }
        } else {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            
            if ($stmt->fetch()) {
                $error = "Email address is already taken.";
            } else {
                if ($new_password !== '') {
                    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $role, $hashed, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $role, $id]);
                }

                $success = "User updated successfully!";
            }
        }
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Prevent admin from deleting themselves
    if ($id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        $success = "User deleted successfully!";
    }
}

// Get all users
// Handle edit action (load user for editing)
$editUser = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $uid = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$uid]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$editUser) {
        $error = "User not found for editing.";
    }
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title
$page_title = "Manage Users";
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
            <li><a href="categories.php"><i class="fas fa-list"></i> Categories</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
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
            <h1>Manage Users</h1>
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
        <!-- Edit User Form (if editing) -->
        <?php if ($editUser): ?>
        <div class="form-container" style="margin-bottom: 20px;">
            <h2>Edit User</h2>
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($editUser['name']); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($editUser['email']); ?>">
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <option value="customer" <?php echo ($editUser['role'] === 'customer') ? 'selected' : ''; ?>>Customer</option>
                        <option value="admin" <?php echo ($editUser['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="password">New Password (optional)</label>
                    <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm New Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" placeholder="Repeat new password">
                </div>
                <button type="submit" name="update_user" class="btn">Update User</button>
                <a href="users.php" class="btn" style="margin-left:8px;">Cancel</a>
            </form>
        </div>
        <?php endif; ?>

        <!-- Users List -->
        <h2 class="section-title">All Users</h2>
        
        <table style="width: 100%; border-collapse: collapse; background-color: var(--card-bg); border-radius: 8px; overflow: hidden; margin-bottom: 30px;">
            <thead>
                <tr style="background-color: var(--primary); color: white;">
                    <th style="padding: 12px; text-align: left;">ID</th>
                    <th style="padding: 12px; text-align: left;">Name</th>
                    <th style="padding: 12px; text-align: left;">Email</th>
                    <th style="padding: 12px; text-align: left;">Role</th>
                    <th style="padding: 12px; text-align: left;">Joined</th>
                    <th style="padding: 12px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">#<?php echo $user['id']; ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($user['name']); ?></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 8px; border-radius: 4px; background-color: <?php echo $user['role'] === 'admin' ? 'var(--primary)' : 'var(--info)'; ?>; color: white;">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td style="padding: 12px;"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td style="padding: 12px; text-align: center;">
                            <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" style="color: var(--info); margin-right: 10px;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?')" style="color: var(--danger);">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding: 20px; text-align: center;">No users found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <script src="../assets/js/script.js"></script>
</body>
</html>