<?php
/**
 * User registration page
 *
 * Handles user registration via POST. Uses helper registerUser() and then
 * attempts to auto-login the new user. Error strings are set in $error for
 * display; on success the page redirects to the homepage.
 */
require_once __DIR__ . '/../includes/header.php';

$page_title = "Register";

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Register user
        $result = registerUser($name, $email, $password);
        
        if ($result === true) {
            // Auto-login after registration
            if (loginUser($email, $password)) {
                redirect('index.php');
            } else {
                $error = "Registration successful but login failed. Please try logging in.";
            }
        } else {
            $error = $result;
        }
    }
}
?>

<div class="form-container">
    <h2>Create Account</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="btn" style="width: 100%;">Register</button>
    </form>
    
    <p style="text-align: center; margin-top: 20px;">
        Already have an account? <a href="<?php echo SITE_URL; ?>/login.php">Login here</a>
    </p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>