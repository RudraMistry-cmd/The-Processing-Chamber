<?php
// 500.php - Server Error
$page_title = "Server Error";
include 'includes/header.php';
?>

<div style="text-align: center; padding: 100px 20px;">
/**
 * 500 - Server Error
 *
 * Generic server error page. Keeps output minimal and uses shared header/footer.
 */
require_once __DIR__ . '/includes/header.php';
        <i class="fas fa-exclamation-circle"></i>
    </div>
    <h1>500 - Server Error</h1>
    <p>Something went wrong on our end. Please try again later.</p>
    <a href="index.php" class="btn">Go Home</a>
</div>

<?php include 'includes/footer.php'; ?>