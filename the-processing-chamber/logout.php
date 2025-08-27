<?php
/**
 * Logout endpoint
 *
 * Clears the user's session and optional remember-me cookie, then redirects
 * to the public homepage.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Perform logout and send user back to the site root
logoutUser();
redirect('index.php');
?>