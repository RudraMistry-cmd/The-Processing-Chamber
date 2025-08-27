<?php
/**
 * User login
 *
 * Handles authentication and optional remember-me token. Redirects after
 * successful login using the stored `redirect_url` (e.g., checkout flow).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = "Login";

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                
                setcookie('remember_token', $token, $expiry, '/');
                
                // Store token in database
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$token, $user['id']]);
            }
            
            // Redirect based on role or stored intention
            if ($user['role'] === 'admin') {
                redirect('admin/index.php');
            } else {
                // If there was a requested page before login (like checkout), go there
                if (!empty($_SESSION['redirect_url'])) {
                    $redirect_to = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                    redirect($redirect_to);
                }

                // Default to pages index
                redirect('index.php');
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="form-container">
    <h2>User Login</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-options">
            <label>
                <input type="checkbox" name="remember"> Remember me
            </label>
            <a href="<?php echo SITE_URL; ?>/pages/forgot-password.php">Forgot Password?</a>
        </div>
        
        <button type="submit" class="btn" style="width: 100%;">Login</button>
    </form>
    
    <div class="admin-login" style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
        <p>Are you an admin? <a href="<?php echo SITE_URL; ?>/admin/login.php">Login here</a></p>
    </div>
    
    <p style="text-align: center; margin-top: 20px;">
        Don't have an account? <a href="<?php echo SITE_URL; ?>/pages/register.php">Register here</a>
    </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>