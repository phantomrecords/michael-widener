<?php
declare(strict_types=1);

/**
 * /make-user.php
 * Generates a username + password + bcrypt hashes, then forces a .txt download.
 * Does NOT write anything to disk.
 *
 * Delete this file immediately after use.
 */

if (PHP_SAPI === 'cli') {
  fwrite(STDERR, "Run this via the browser over HTTPS.\n");
  exit(1);
}

function rand_base32(int $len): string {
  $alphabet = 'abcdefghijklmnopqrstuvwxyz234567';
  $out = '';
  for ($i = 0; $i < $len; $i++) {
    $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
  }
  return $out;
}

function strong_password(int $bytes = 18): string {
  // 18 bytes -> ~24 chars base64url-ish
  $raw = random_bytes($bytes);
  return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}

$mode = strtolower((string)($_GET['mode'] ?? 'random')); // random|custom
$custom_u = (string)($_GET['u'] ?? '');

if ($mode === 'custom') {
  $username = trim($custom_u);
  // allow username-style or email-style
  $is_email = filter_var($username, FILTER_VALIDATE_EMAIL) !== false;
  $is_user  = (bool)preg_match('/^[A-Za-z0-9._-]{3,32}$/', $username);
  if ($username === '' || (!$is_email && !$is_user)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "ERROR: invalid username.\n\n";
    echo "Use either:\n";
    echo " - username-style: 3-32 chars [A-Za-z0-9._-]\n";
    echo " - email-style: name@example.com\n";
    exit;
  }
} else {
  // random username: mw-xxxxxxxx (base32)
  $username = 'mw-' . rand_base32(10);
}

$password = strong_password(18);

// Hash both so neither is stored as plaintext in auth.php
$user_hash = password_hash($username, PASSWORD_DEFAULT);
$pass_hash = password_hash($password, PASSWORD_DEFAULT);

// Build the downloadable content
$host = $_SERVER['HTTP_HOST'] ?? 'michaelwidener.com';
$ts = gmdate('Y-m-d\THis\Z');

$txt =
"MichaelWidener.com — Make User Output\n"
."Generated (UTC): {$ts}\n"
."Host: {$host}\n\n"
."PLAINTEXT (save offline):\n"
."USERNAME: {$username}\n"
."PASSWORD: {$password}\n\n"
."PASTE INTO /auth.php:\n\n"
."define('HC_LOGIN_USERNAME_HASH', '{$user_hash}');\n"
."define('HC_LOGIN_PASSWORD_HASH', '{$pass_hash}');\n\n"
."Then delete /make-user.php from the server.\n";

// Force download, discourage caching anywhere
$fname = 'MW-Credentials-' . gmdate('Ymd-His') . '.txt';

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fname . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo $txt;
