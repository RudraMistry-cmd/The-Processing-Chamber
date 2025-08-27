<?php
/**
 * Reset password
 *
 * Validates the reset token and allows the user to set a new password.
 */
require_once __DIR__ . '/../includes/header.php';

$page_title = "Reset Password";

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Check if token and email are provided
if (!isset($_GET['token']) || !isset($_GET['email'])) {
    redirect('forgot-password.php');
}

$token = $_GET['token'];
$email = urldecode($_GET['email']);

// Validate token
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND reset_token = ? AND reset_expiry > NOW()");
$stmt->execute([$email, $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $error = "Invalid or expired reset token. Please request a new password reset.";
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Update password and clear reset token
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);
        
        $success = "Password reset successfully! You can now <a href='login.php'>login</a> with your new password.";
    }
}
?>

<div class="form-container">
    <h2>Reset Password</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (!isset($success) && (!isset($error) || $error !== "Invalid or expired reset token. Please request a new password reset.")): ?>
        <p>Enter your new password below.</p>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn" style="width: 100%;">Reset Password</button>
        </form>
    <?php endif; ?>
    
    <p style="text-align: center; margin-top: 20px;">
        <a href="<?php echo SITE_URL; ?>/forgot-password.php">Request a new reset link</a>
    </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>