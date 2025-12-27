<?php
// config.php — shared configuration for harassment-case portal

// Disable error display in browser (prevents information leaks)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// -----------------------------
// SECURITY CONSTANTS
// -----------------------------

// Session cookie name — unique so it never collides
define('HC_SESSION_NAME', 'hc_secure_session_2025a');

// Session timeout (in seconds)
// 30 minutes = 1800 seconds
define('HC_SESSION_TIMEOUT', 1800);

// Maximum failed login attempts before slowing brute force
define('HC_MAX_FAILED_ATTEMPTS', 10);

// -----------------------------
// LOGIN CREDENTIALS
// -----------------------------



// Username you want
define('HC_USERNAME', 'michael');

// PASSWORD HASH
// Replace this with the output of: password_hash('yourpassword', PASSWORD_DEFAULT)
// MUST BE GENERATED ON YOUR MACHINE — NEVER PLAINTEXT
//
// Example placeholder (THIS WILL NOT WORK — replace it):
// define('HC_PASSWORD_HASH', '$2y$10$REPLACE_THIS_WITH_YOUR_REAL_PASSWORD_HASH');
//
// -----------------------------
// IMPORTANT:
// You *must* update this next line with YOUR real password hash.
// -----------------------------

define('HC_PASSWORD_HASH', '$2y$12$TcxbK8b.ybP6xWNDtES4qO5.PctjXp2xAG6lzkXdh0FE/IYpLFViK');

// -----------------------------
// SESSION SECURITY SETTINGS
// -----------------------------

// Ensure strict cookies and secure session behavior are always enabled
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);      // HTTPS required
ini_set('session.cookie_samesite', 'Strict');

// You do *not* call session_start() here — each page does that itself
?>
