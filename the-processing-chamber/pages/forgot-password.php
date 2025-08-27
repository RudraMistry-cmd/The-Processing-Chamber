<?php
/**
 * Forgot password
 *
 * Generates a one-time reset token and (for demo) displays the reset link.
 * In production this should send an email instead.
 */
require_once __DIR__ . '/../includes/header.php';

$page_title = "Forgot Password";

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Process password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
            $stmt->execute([$token, $expiry, $user['id']]);
            
            // In a real application, you would send an email here
            // For this example, we'll show the reset link on the page
            
            $reset_link = SITE_URL . "/reset-password.php?token=$token&email=" . urlencode($email);
            $success = "Password reset link has been generated. <a href='$reset_link'>Click here to reset your password</a>";
        } else {
            $error = "No account found with that email address.";
        }
    }
}
?>

<div class="form-container">
    <h2>Forgot Password</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <p>Enter your email address and we'll send you a link to reset your password.</p>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <button type="submit" class="btn" style="width: 100%;">Send Reset Link</button>
    </form>
    
    <p style="text-align: center; margin-top: 20px;">
        Remember your password? <a href="<?php echo SITE_URL; ?>/login.php">Login here</a>
    </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>