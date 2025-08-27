<?php require_once __DIR__ . '/../includes/config.php'; ?>
<?php
// includes/auth.php
// Authentication helpers: login, register, logout and remember-me handling.
// Keep this file lightweight and procedural to match project conventions.
require_once __DIR__ . '/config.php';
//
// Functions below should not output anything. They only manipulate session/cookies and return status.
//
// Authentication functions
function loginUser($email, $password, $remember = false) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        // Set remember token if requested
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60); // 30 days
            
            setcookie('remember_token', $token, $expiry, '/');
            
            // Store token in database
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, remember_expiry = ? WHERE id = ?");
            $stmt->execute([$token, date('Y-m-d H:i:s', $expiry), $user['id']]);
        }
        
        return true;
    }
    
    return false;
}

function registerUser($name, $email, $password, $role = 'customer') {
    global $pdo;
    
    // hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }

    // insert new user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $success = $stmt->execute([$name, $email, $hashedPassword, $role]);

    return $success 
        ? ['success' => true, 'message' => 'Registration successful.']
        : ['success' => false, 'message' => 'Something went wrong.'];
}

function logoutUser() {
    // Clear session
    session_unset();
    session_destroy();
    
    // Clear remember cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

function checkRememberToken() {
    global $pdo;
    
    if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
        $token = $_COOKIE['remember_token'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND remember_expiry > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }
    }
    
    return false;
}

// Check remember token on each page load
checkRememberToken();
?>