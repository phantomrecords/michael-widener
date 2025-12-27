<?php
declare(strict_types=1);

/**
 * auth.php (site root)
 * Single source of truth for login state + redirect helpers.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    // Use secure defaults where possible
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/** HTML escape helper */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }

/**
 * Only allow internal “next” paths like "/portfolio/".
 * Blocks full URLs, protocol-relative, etc.
 */
function get_next_path(string $fallback = '/'): string
{
    $next = $_POST['next'] ?? $_GET['next'] ?? $fallback;
    if (!is_string($next)) return $fallback;

    $next = trim($next);
    if ($next === '' || $next[0] !== '/') return $fallback;
    if (str_starts_with($next, '//')) return $fallback; // protocol-relative

    return $next;
}

/** Redirect and exit */
function redirect(string $path): void
{
    if ($path === '' || $path[0] !== '/') $path = '/';
    header('Location: ' . $path, true, 302);
    exit;
}

/** Logged-in flag */
function is_logged_in(): bool
{
    return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/** Mark user logged in */
function login_user(): void
{
    session_regenerate_id(true);
    $_SESSION['logged_in'] = true;
}

/** Log out completely */
function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool)($params['secure'] ?? false),
            (bool)($params['httponly'] ?? true)
        );
    }

    session_destroy();
}

/**
 * Require login or bounce to /login/?next=/requested/path
 */
function require_login(): void
{
    if (is_logged_in()) return;

    $next = $_SERVER['REQUEST_URI'] ?? '/';
    if (!is_string($next) || $next === '' || $next[0] !== '/' || str_starts_with($next, '//')) {
        $next = '/';
    }

    redirect('/login/?next=' . rawurlencode($next));
}

/**
 * OPTIONAL:
 * Replace this with your real credential check.
 * Keep here so login/index.php can call it.
 */
function auth_check_credentials(string $account_id, string $password): bool
{
    // TODO: implement your real auth (DB, hashed passwords, etc.)
    return false;
}
