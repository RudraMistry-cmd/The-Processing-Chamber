<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php
$page_title = "Contact Us";

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // In a real application, you would send an email here
        // For this example, we'll just simulate success
        
        // Save contact form data to database (optional)
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_submissions (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);
            $success = "Thank you for your message! We'll get back to you within 24 hours.";
        } catch (Exception $e) {
            $success = "Thank you for your message! We'll get back to you soon.";
        }
    }
}

// Create contact_submissions table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // Table creation failed, but we can still proceed
}
?>

<div class="contact-container">
    <h2 class="section-title">Contact Us</h2>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="contact-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <!-- Contact Form -->
        <div class="contact-form">
            <div class="form-container">
                <h3>Send us a Message</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="5" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Send Message</button>
                </form>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="contact-info">
            <div style="background-color: var(--card-bg); padding: 20px; border-radius: 8px; height: 100%;">
                <h3>Get in Touch</h3>
                
                <div style="margin-bottom: 25px;">
                    <h4><i class="fas fa-map-marker-alt" style="color: var(--primary); margin-right: 10px;"></i> Address</h4>
                    <p>123 Tech Park, Bangalore, Karnataka 560001, India</p>
                </div>
                
                <div style="margin-bottom: 25px;">
                    <h4><i class="fas fa-phone" style="color: var(--primary); margin-right: 10px;"></i> Phone</h4>
                    <p>+91 9876543210</p>
                </div>
                
                <div style="margin-bottom: 25px;">
                    <h4><i class="fas fa-envelope" style="color: var(--primary); margin-right: 10px;"></i> Email</h4>
                    <p>support@processingchamber.com</p>
                </div>
                
                <div style="margin-bottom: 25px;">
                    <h4><i class="fas fa-clock" style="color: var(--primary); margin-right: 10px;"></i> Business Hours</h4>
                    <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                    <p>Saturday: 10:00 AM - 4:00 PM</p>
                    <p>Sunday: Closed</p>
                </div>
                
                <div>
                    <h4><i class="fas fa-share-alt" style="color: var(--primary); margin-right: 10px;"></i> Follow Us</h4>
                    <div style="display: flex; gap: 15px; margin-top: 10px;">
                        <a href="#" style="color: var(--body-text); font-size: 1.5rem;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: var(--body-text); font-size: 1.5rem;"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: var(--body-text); font-size: 1.5rem;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: var(--body-text); font-size: 1.5rem;"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Map -->
    <div style="margin-top: 40px;">
        <h3>Our Location</h3>
        <div style="width: 100%; height: 400px; background-color: #f5f5f5; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
            <p>Google Maps would be embedded here in a real application</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>