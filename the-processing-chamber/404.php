<?php
/**
 * 404 - Page Not Found
 *
 * Generic not-found page displayed when a route or file is missing.
 */
$page_title = "Page Not Found";
require_once __DIR__ . '/includes/header.php';
?>

<div style="text-align: center; padding: 100px 20px;">
    <div style="font-size: 6rem; color: var(--primary); margin-bottom: 20px;">
        <i class="fas fa-exclamation-triangle"></i>
    </div>
    <h1>404 - Page Not Found</h1>
    <p>The page you're looking for doesn't exist.</p>
    <a href="index.php" class="btn">Go Home</a>
</div>

<?php include 'includes/footer.php'; ?>