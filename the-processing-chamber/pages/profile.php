<?php
/**
 * User profile
 *
 * Shows user's personal information and order history. Handles profile updates
 * (including optional password change) via POST.
 */
require_once __DIR__ . '/../includes/header.php';

$page_title = "User Profile";

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('pages/login.php');
}

// Get user details
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($name) || empty($email)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        
        if ($stmt->fetch()) {
            $error = "Email address is already taken.";
        } else {
            // Update user details
            $update_data = [$name, $email, $user_id];
            $update_query = "UPDATE users SET name = ?, email = ?";
            
            // Update password if provided
            if (!empty($current_password) && !empty($new_password)) {
                if (!password_verify($current_password, $user['password'])) {
                    $error = "Current password is incorrect.";
                } elseif ($new_password !== $confirm_password) {
                    $error = "New passwords do not match.";
                } elseif (strlen($new_password) < 6) {
                    $error = "New password must be at least 6 characters long.";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_query .= ", password = ?";
                    $update_data[] = $hashed_password;
                }
            }
            
            $update_query .= " WHERE id = ?";
            
            if (!isset($error)) {
                $stmt = $pdo->prepare($update_query);
                if ($stmt->execute($update_data)) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $success = "Profile updated successfully!";
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Failed to update profile. Please try again.";
                }
            }
        }
    }
}
?>

<div class="profile-container">
    <h2 class="section-title">User Profile</h2>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="profile-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- Profile Form -->
        <div class="profile-form">
            <div class="form-container">
                <h3>Personal Information</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <h4 style="margin-top: 20px; margin-bottom: 15px;">Change Password</h4>
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                </form>
            </div>
        </div>

        <!-- Order History -->
        <div class="order-history">
            <div style="background-color: var(--card-bg); padding: 20px; border-radius: 8px;">
                <h3>Order History</h3>
                
                <?php if (count($orders) > 0): ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <div style="border-bottom: 1px solid var(--border-color); padding: 15px 0;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <div>
                                        <strong>Order #<?php echo $order['id']; ?></strong>
                                        <br>
                                        <small>Date: <?php echo date('M j, Y', strtotime($order['created_at'])); ?></small>
                                    </div>
                                    <div style="text-align: right;">
                                        <div><?php echo formatPrice($order['total']); ?></div>
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
                                </div>
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.9rem;">View Details</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>You haven't placed any orders yet.</p>
                    <a href="products.php" class="btn">Start Shopping</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>