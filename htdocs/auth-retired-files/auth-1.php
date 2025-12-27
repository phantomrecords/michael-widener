<?php
declare(strict_types=1);

/**
 * /auth.php (site root)
 * Central session + auth helpers for michaelwidener.com.
 *
 * IMPORTANT:
 * - This file must NOT echo/print anything.
 * - This file should never be directly served publicly.
 */

/* ============================
   1) CONFIGURE YOUR LOGIN HERE
   ============================ */

// Set your username (case-sensitive match)
define('HC_LOGIN_USERNAME', 'michael'); // change if you want

// Set your password hash (generate with password_hash('yourpass', PASSWORD_DEFAULT))
define('HC_LOGIN_PASSWORD_HASH', '$2y$12$xNJSoBvIuKUIyk37PxRQBe/kzx0.1PTQZCb3V1Avj9bpRRhwd.qn2');   // <-- paste your real hash here

/* ============================
   2) SESSION BOOTSTRAP
   ============================ */

if (session_status() !== PHP_SESSION_ACTIVE) {

    // Ensure session cookie applies to whole site (NOT /legal only)
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        // Legacy fallback
        session_set_cookie_params(0, '/; samesite=Lax', '', $secure, true);
    }

    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.cookie_secure', $secure ? '1' : '0');

    session_start();
}

/* ============================
   3) CORE HELPERS
   ============================ */

function is_logged_in(): bool
{
    return (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true)
        || (!empty($_SESSION['hc_logged_in']) && $_SESSION['hc_logged_in'] === true);
}

function login_user(): void
{
    // Prevent session fixation
    session_regenerate_id(true);

    // Canonical + legacy compatibility
    $_SESSION['logged_in'] = true;
    $_SESSION['hc_logged_in'] = true;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool)($params['secure'] ?? false),
            (bool)($params['httponly'] ?? true)
        );
    }

    session_destroy();
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/**
 * Allow only local site paths for "next" to prevent open-redirects.
 */
function get_next_path(string $default = '/'): string
{
    $next = '';

    if (isset($_GET['next']) && is_string($_GET['next'])) {
        $next = $_GET['next'];
    } elseif (isset($_POST['next']) && is_string($_POST['next'])) {
        $next = $_POST['next'];
    }

    $next = trim($next);
    if ($next === '') return $default;

    // Must be absolute-path on this site, like "/legal/" (not "http://...")
    if ($next[0] !== '/') return $default;
    if (str_starts_with($next, '//')) return $default;

    return $next;
}

function redirect_to_login(string $next = '/'): void
{
    redirect('/login/?next=' . rawurlencode($next));
}

function require_login(?string $next_override = null): void
{
    if (is_logged_in()) return;

    $next = $next_override;
    if ($next === null || $next === '') {
        $next = $_SERVER['REQUEST_URI'] ?? '/';
        if (!is_string($next) || $next === '') $next = '/';
    }

    redirect_to_login($next);
}

/**
 * Credential verification used by login pages.
 */
function auth_check_credentials(string $username, string $password): bool
{
    if (!defined('HC_LOGIN_USERNAME') || !defined('HC_LOGIN_PASSWORD_HASH')) {
        return false;
    }

    $u = (string)HC_LOGIN_USERNAME;
    $h = (string)HC_LOGIN_PASSWORD_HASH;

    if ($u === '' || $h === '') {
        return false;
    }

    return hash_equals($u, $username) && password_verify($password, $h);
}

/* =========================================================
   Pending account requests: encrypt-at-rest + email confirm
   (Option B supported via /.hc_secret_key)
   ========================================================= */

function hc_secret_key_bytes(): string
{
    // Preferred: environment variable (optional)
    $b64 = getenv('HC_SECRET_KEY_B64');
    if (is_string($b64) && $b64 !== '') {
        $raw = base64_decode($b64, true);
        if (is_string($raw) && strlen($raw) === 32) return $raw;
    }

    // Option B: locked file in webroot (deny via .htaccess and file permissions)
    $path = __DIR__ . '/.hc_secret_key';
    if (is_file($path)) {
        $raw = trim((string)file_get_contents($path));
        $bin = base64_decode($raw, true);
        if (is_string($bin) && strlen($bin) === 32) return $bin;
    }

    throw new RuntimeException('Secret key not configured.');
}

function hc_encrypt_payload(array $data): array
{
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) throw new RuntimeException('Failed to encode payload.');

    $key = hc_secret_key_bytes();

    // libsodium secretbox
    if (function_exists('sodium_crypto_secretbox')) {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ct = sodium_crypto_secretbox($json, $nonce, $key);
        return [
            'alg' => 'secretbox',
            'nonce_b64' => base64_encode($nonce),
            'ct_b64' => base64_encode($ct),
        ];
    }

    // OpenSSL AES-256-GCM fallback
    if (function_exists('openssl_encrypt')) {
        $nonce = random_bytes(12);
        $tag = '';
        $ct = openssl_encrypt($json, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
        if (!is_string($ct)) throw new RuntimeException('OpenSSL encrypt failed.');
        return [
            'alg' => 'aes-256-gcm',
            'nonce_b64' => base64_encode($nonce),
            'tag_b64' => base64_encode($tag),
            'ct_b64' => base64_encode($ct),
        ];
    }

    throw new RuntimeException('No encryption backend available.');
}

function hc_pending_store_dir(): string
{
    $dir = __DIR__ . '/_pending_account_requests';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

function hc_store_pending_account_request(array $payload): string
{
    $token = bin2hex(random_bytes(16));
    $enc = hc_encrypt_payload($payload);

    $record = [
        'token' => $token,
        'created_at' => gmdate('c'),
        'ip_hash' => hash('sha256', (string)($payload['ip'] ?? '')),
        'ua_hash' => hash('sha256', (string)($payload['ua'] ?? '')),
        'encrypted' => $enc,
    ];

    $dir = hc_pending_store_dir();
    $path = $dir . '/' . $token . '.json';

    $json = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false) throw new RuntimeException('Failed to write record.');
    file_put_contents($path, $json, LOCK_EX);

    @chmod($path, 0600);
    return $token;
}

function hc_send_account_request_email(string $to_email, string $to_name, string $token): void
{
    $subject = 'Account Request Received';
    $safeName = $to_name !== '' ? $to_name : 'there';

    $body =
        "Hi {$safeName},\n\n"
        . "We received an account request.\n\n"
        . "If an account exists for the information provided—or if the request is eligible—an email will be sent with next steps.\n\n"
        . "Reference: {$token}\n\n"
        . "If you did not initiate this request, you can ignore this message.\n\n"
        . "— MichaelWidener.com\n";

    $host = $_SERVER['HTTP_HOST'] ?? 'michaelwidener.com';
    $from = 'no-reply@' . $host;

    $headers =
        "From: {$from}\r\n"
        . "Reply-To: {$from}\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail($to_email, $subject, $body, $headers);
}

/* ============================
   2B) NAV HISTORY (JS-LESS BACK)
   ============================ */

function nav_track_visit(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) return;

    $current = $_SERVER['REQUEST_URI'] ?? '/';
    if (!is_string($current) || $current === '') $current = '/';

    // Only track your own site paths
    if ($current[0] !== '/') $current = '/';

    // First hit
    if (!isset($_SESSION['nav_current'])) {
        $_SESSION['nav_prev'] = null;
        $_SESSION['nav_current'] = $current;
        $_SESSION['nav_next'] = null; // optional placeholder
        return;
    }

    // Same page refresh: don't change history
    if ($_SESSION['nav_current'] === $current) {
        return;
    }

    // Move current to prev, set new current
    $_SESSION['nav_prev'] = $_SESSION['nav_current'];
    $_SESSION['nav_current'] = $current;

    // Optional: forward history is not tracked (kept null)
    $_SESSION['nav_next'] = null;
}

// Track every request that includes auth.php
nav_track_visit();

function nav_back_href(string $fallback = '/'): string
{
    $prev = $_SESSION['nav_prev'] ?? null;
    if (!is_string($prev) || $prev === '') return $fallback;
    if ($prev[0] !== '/') return $fallback;
    return $prev;
}

function nav_forward_href(string $fallback = '/'): string
{
    // forward is intentionally conservative (no browser history available server-side)
    $next = $_SESSION['nav_next'] ?? null;
    if (!is_string($next) || $next === '') return $fallback;
    if ($next[0] !== '/') return $fallback;
    return $next;
}

