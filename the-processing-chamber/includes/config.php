<?php
/**
 * Application configuration
 *
 * This file centralizes environment and bootstrap logic for the site.
 * It is intentionally simple and procedural so developers can quickly
 * find and adjust database credentials, site constants and startup behavior.
 *
 * Notes for maintainers:
 * - This runs early on every page via `require_once` from headers.
 * - Keep debugging flags minimal in production.
 */

// -----------------------------
// PHP environment / debugging
// -----------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure session is available to all pages.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------
// Database configuration
// -----------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'processing_chamber');
define('DB_USER', 'root');
define('DB_PASS', '');

// -----------------------------
// Site configuration
// -----------------------------
define('SITE_NAME', 'The Processing Chamber');
define('SITE_URL', 'http://localhost/the-processing-chamber');

// Default timezone for date functions
date_default_timezone_set('Asia/Kolkata');

// -----------------------------
// PDO (MySQL) connection
// -----------------------------
// Fail fast if the PDO MySQL extension is not enabled.
if (!extension_loaded('pdo_mysql')) {
    die("PDO MySQL extension is not enabled. Please enable it in your php.ini file.");
}

try {
    // Create a shared PDO instance for the app. Keep attributes explicit.
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    if (defined('PDO::ATTR_ERRMODE')) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } else {
        // Backwards-compatible numeric constants if needed
        $pdo->setAttribute(3, 2); // ATTR_ERRMODE = 3, ERRMODE_EXCEPTION = 2
    }

} catch (PDOException $e) {
    // Don't reveal sensitive details in production; for development show the message.
    die("Database connection failed: " . $e->getMessage());
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>