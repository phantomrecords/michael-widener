<?php
declare(strict_types=1);

/**
 * auth.php (site root)
 * Single source of truth for login state + redirect helpers.
 */

/**
 * Determine base path for this deployment.
 *
 * - Production: site served at "/" => base path ""
 * - Development: site served at "/htdocs/" => base path "/htdocs"
 *
 * Override (optional): set env var SITE_BASE_PATH to "" or "/htdocs".
 */
function site_base_path(): string
{
    $override = getenv('SITE_BASE_PATH');
    if (is_string($override)) {
        $override = trim($override);
        if ($override === '' || $override === '/') return '';
        if ($override[0] !== '/') $override = '/' . $override;
        return rtrim($override, '/');
    }

    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (is_string($script) && preg_match('#^/htdocs(?:/|$)#', $script)) {
        return '/htdocs';
    }

    return '';
}

/**
 * Build an internal site URL that works in both prod (/) and dev (/htdocs/).
 *
 * - External URLs (http/https/mailto/tel/javascript) are returned unchanged.
 * - Hash links (#...) are returned unchanged.
 * - Absolute paths ("/foo") are prefixed with base path when needed.
 */
function site_url(string $path): string
{
    $path = (string)$path;
    if ($path === '') $path = '/';

    // Leave external and special schemes untouched
    if (preg_match('#^(https?:)?//#i', $path) || preg_match('#^(mailto:|tel:|javascript:)#i', $path)) {
        return $path;
    }
    if ($path[0] === '#') return $path;

    // Normalize to site-absolute
    if ($path[0] !== '/') $path = '/' . $path;

    $base = site_base_path();
    if ($base === '') return $path;

    // Avoid double-prefixing when caller already included base
    if ($path === $base || str_starts_with($path, $base . '/')) return $path;

    return $base . $path;
}

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
    $path = site_url($path);
    header('Location: ' . $path, true, 302);
    exit;
}

/** Logged-in flag */
function is_logged_in(): bool
{
    return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/** Mark user logged in */
function login_user(?string $account_id = null): void
{
    session_regenerate_id(true);
    $_SESSION['logged_in'] = true;

    if (is_string($account_id) && $account_id !== '') {
        $_SESSION['account_id'] = $account_id;
    }

    // Minimal role defaults (so role-gated pages don't 403 by default)
    if (empty($_SESSION['role']) || !is_string($_SESSION['role'])) {
        $_SESSION['role'] = 'Owner';
    }
    if (empty($_SESSION['account_role']) || !is_string($_SESSION['account_role'])) {
        $_SESSION['account_role'] = (string)$_SESSION['role'];
    }
    if (!isset($_SESSION['approved'])) {
        $_SESSION['approved'] = true;
    }
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
function require_login(?string $next_override = null): void
{
    if (is_logged_in()) return;

    $next = $next_override;
    if (!is_string($next) || $next === '') {
        $next = $_SERVER['REQUEST_URI'] ?? '/';
        if (!is_string($next) || $next === '') $next = '/';
    }
    if ($next === '' || $next[0] !== '/' || str_starts_with($next, '//')) $next = '/';

    redirect(site_url('/login/?next=' . rawurlencode($next)));
}

/**
 * OPTIONAL:
 * Replace this with your real credential check.
 * Keep here so login/index.php can call it.
 */
function auth_check_credentials(string $account_id, string $password): bool
{
    $account_id = trim($account_id);
    $password = (string)$password;

    // Prefer environment variables so secrets are not committed to git.
    $user = getenv('HC_LOGIN_USERNAME');
    if (!is_string($user) || $user === '') {
        $user = defined('HC_LOGIN_USERNAME') ? (string)HC_LOGIN_USERNAME : '';
    }

    $pass_hash = getenv('HC_LOGIN_PASSWORD_HASH');
    if (!is_string($pass_hash) || $pass_hash === '') {
        $pass_hash = defined('HC_LOGIN_PASSWORD_HASH') ? (string)HC_LOGIN_PASSWORD_HASH : '';
    }

    if ($user === '' || $pass_hash === '') return false;
    if (!hash_equals($user, $account_id)) return false;

    return password_verify($password, $pass_hash);
}

/* ============================
   Role helpers (lightweight)
   ============================ */

function current_role(): string
{
    $role = $_SESSION['account_role'] ?? ($_SESSION['role'] ?? '');
    return is_string($role) ? trim($role) : '';
}

function is_super(): bool
{
    return current_role() === 'Super';
}

function require_role(string ...$allowed): void
{
    if (!is_logged_in()) return; // require_login() will handle redirects when used
    if (count($allowed) === 0) return;

    $role = current_role();
    if ($role === '') {
        http_response_code(403);
        echo 'Forbidden.';
        exit;
    }
    if (is_super()) return;

    foreach ($allowed as $a) {
        if ($role === $a) return;
    }

    http_response_code(403);
    echo 'Forbidden.';
    exit;
}

function require_super(?string $next_override = null): void
{
    require_login($next_override);
    if (is_super()) return;
    http_response_code(403);
    echo 'Forbidden.';
    exit;
}

/* ============================
   Nav history (JS-less back/forward)
   ============================ */

function nav_track_visit(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) return;

    $current = $_SERVER['REQUEST_URI'] ?? '/';
    if (!is_string($current) || $current === '') $current = '/';
    if ($current[0] !== '/') $current = '/';

    // First hit
    if (!isset($_SESSION['nav_current'])) {
        $_SESSION['nav_prev'] = null;
        $_SESSION['nav_current'] = $current;
        $_SESSION['nav_next'] = null;
        return;
    }

    // Refresh: don't change history
    if ($_SESSION['nav_current'] === $current) return;

    $_SESSION['nav_prev'] = $_SESSION['nav_current'];
    $_SESSION['nav_current'] = $current;
    $_SESSION['nav_next'] = null;
}

function nav_back_href(string $fallback = '/'): string
{
    $prev = $_SESSION['nav_prev'] ?? null;
    if (!is_string($prev) || $prev === '' || $prev[0] !== '/') return $fallback;
    return $prev;
}

function nav_forward_href(string $fallback = '/'): string
{
    $next = $_SESSION['nav_next'] ?? null;
    if (!is_string($next) || $next === '' || $next[0] !== '/') return $fallback;
    return $next;
}

nav_track_visit();
