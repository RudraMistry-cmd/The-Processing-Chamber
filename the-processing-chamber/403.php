<?php
/**
 * 403 - Access Forbidden
 *
 * Simple error page shown when a user is not authorized to view a resource.
 */
$page_title = "Access Forbidden";
require_once __DIR__ . '/includes/header.php';
?>

<div style="text-align: center; padding: 100px 20px;">
    <div style="font-size: 6rem; color: var(--danger); margin-bottom: 20px;">
        <i class="fas fa-ban"></i>
    </div>
    <h1>403 - Access Forbidden</h1>
    <p>You don't have permission to access this page.</p>
    <a href="index.php" class="btn">Go Home</a>
</div>

<?php include 'includes/footer.php'; ?>