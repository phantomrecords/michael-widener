<?php
// logout.php — terminates session and returns user to login screen

error_reporting(E_ALL);
ini_set('display_errors', 0);

require __DIR__ . '/config.php';

function hc_log(string $type, string $detail = ''): void {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '-';
    $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 160);
    $ts  = date('c');
    $line = $ts . "\t" . $type . "\t" . $detail . "\t" . $ip . "\t" . $ua . PHP_EOL;
    @file_put_contents(__DIR__ . '/login-log.txt', $line, FILE_APPEND | LOCK_EX);
}

// Session setup
session_name(HC_SESSION_NAME);
session_start();

$username = $_SESSION['hc_username'] ?? '';

// Log the logout event
hc_log('LOGOUT', $username);

// Clear all session variables
$_SESSION = [];

// Remove the session cookie (use PHP's own parameters)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy server-side session container
session_destroy();

// Redirect to folder (login page resolves automatically as index.php)
header("Location: ./");
exit;
