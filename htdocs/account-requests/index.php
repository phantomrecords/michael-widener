<?php
declare(strict_types=1);

/**
 * /account-requests/index.php
 * Owner review queue for pending account requests.
 */

require_once __DIR__ . '/../auth.php';
require_login('/account-requests/');

$diag = (isset($_GET['diag']) && $_GET['diag'] === '1');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }

/* ============================
   Paths
   ============================ */

$ROOT = dirname(__DIR__); // /htdocs
$PENDING_DIR = $ROOT . '/_pending_account_requests';
$PROCESSED_DIR = $PENDING_DIR . '/_processed';
$APPROVED_DIR = $PROCESSED_DIR . '/approved';
$DENIED_DIR   = $PROCESSED_DIR . '/denied';

$ACCOUNTS_DIR = $ROOT . '/_accounts';

foreach ([$PENDING_DIR, $PROCESSED_DIR, $APPROVED_DIR, $DENIED_DIR, $ACCOUNTS_DIR] as $d) {
  if (!is_dir($d)) { @mkdir($d, 0700, true); }
}

/* ============================
   Crypto (must match account-create)
   ============================ */

function hc_secret_key_bytes_for_review(string $root): string
{
  $b64 = getenv('HC_SECRET_KEY_B64');
  if (is_string($b64) && $b64 !== '') {
    $raw = base64_decode($b64, true);
    if (is_string($raw) && strlen($raw) === 32) return $raw;
  }

  $path = $root . '/.hc_secret_key';
  if (is_file($path) && is_readable($path)) {
    $raw = trim((string)file_get_contents($path));
    $bin = base64_decode($raw, true);
    if (is_string($bin) && strlen($bin) === 32) return $bin;
  }

  throw new RuntimeException('Secret key not configured. Missing /.hc_secret_key (base64, 32 bytes).');
}

function hc_decrypt_payload(array $enc, string $root): array
{
  $key = hc_secret_key_bytes_for_review($root);
  $alg = (string)($enc['alg'] ?? '');

  if ($alg === 'secretbox') {
    if (!function_exists('sodium_crypto_secretbox_open')) {
      throw new RuntimeException('Cannot decrypt: libsodium not available.');
    }
    $nonce = base64_decode((string)($enc['nonce_b64'] ?? ''), true);
    $ct    = base64_decode((string)($enc['ct_b64'] ?? ''), true);
    if (!is_string($nonce) || !is_string($ct)) throw new RuntimeException('Bad secretbox fields.');
    $pt = sodium_crypto_secretbox_open($ct, $nonce, $key);
    if (!is_string($pt)) throw new RuntimeException('Secretbox decrypt failed.');
    $data = json_decode($pt, true);
    if (!is_array($data)) throw new RuntimeException('Decrypted payload not JSON.');
    return $data;
  }

  if ($alg === 'aes-256-gcm') {
    if (!function_exists('openssl_decrypt')) {
      throw new RuntimeException('Cannot decrypt: OpenSSL not available.');
    }
    $nonce = base64_decode((string)($enc['nonce_b64'] ?? ''), true);
    $tag   = base64_decode((string)($enc['tag_b64'] ?? ''), true);
    $ct    = base64_decode((string)($enc['ct_b64'] ?? ''), true);
    if (!is_string($nonce) || !is_string($tag) || !is_string($ct)) throw new RuntimeException('Bad GCM fields.');
    $pt = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
    if (!is_string($pt)) throw new RuntimeException('AES-GCM decrypt failed.');
    $data = json_decode($pt, true);
    if (!is_array($data)) throw new RuntimeException('Decrypted payload not JSON.');
    return $data;
  }

  throw new RuntimeException('Unknown encryption algorithm: ' . $alg);
}

function hc_encrypt_payload_for_accounts(array $data, string $root): array
{
  $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  if ($json === false) throw new RuntimeException('Failed to encode payload.');
  $key = hc_secret_key_bytes_for_review($root);

  if (function_exists('sodium_crypto_secretbox')) {
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ct = sodium_crypto_secretbox($json, $nonce, $key);
    return [
      'alg' => 'secretbox',
      'nonce_b64' => base64_encode($nonce),
      'ct_b64' => base64_encode($ct),
    ];
  }

  if (function_exists('openssl_encrypt')) {
    $nonce = random_bytes(12);
    $tag = '';
    $ct = openssl_encrypt($json, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
    if (!is_string($ct)) throw new RuntimeException('OpenSSL encrypt failed.');
    return [
      'alg' => 'aes-256-gcm',
      'nonce_b64' => base64_encode($nonce),
      'tag_b64'   => base64_encode($tag),
      'ct_b64'    => base64_encode($ct),
    ];
  }

  throw new RuntimeException('No encryption backend available.');
}

/* ============================
   CSRF
   ============================ */

if (empty($_SESSION['hc_csrf']) || !is_string($_SESSION['hc_csrf']) || strlen($_SESSION['hc_csrf']) < 20) {
  $_SESSION['hc_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['hc_csrf'];

function require_csrf(string $csrf): void {
  $posted = (string)($_POST['csrf'] ?? '');
  if ($posted === '' || !hash_equals($csrf, $posted)) {
    http_response_code(400);
    echo "Bad request.";
    exit;
  }
}

/* ============================
   Helpers
   ============================ */

function token_to_path(string $dir, string $token): string {
  if (!preg_match('/^[a-f0-9]{32}$/', $token)) return '';
  return $dir . '/' . $token . '.json';
}

function account_filename_for_id(string $account_id): string {
  // hashed filename so you don't leak IDs in filenames
  return 'acct_' . hash('sha256', strtolower(trim($account_id))) . '.json';
}

/* ============================
   Actions (approve/deny)
   ============================ */

$flash = '';
$flash_err = '';
$view_token = isset($_GET['t']) ? (string)$_GET['t'] : '';
$view_data = null;
$view_record = null;

try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf($csrf);

    $action = (string)($_POST['action'] ?? '');
    $token  = (string)($_POST['token'] ?? '');

    $path = token_to_path($PENDING_DIR, $token);
    if ($path === '' || !is_file($path)) {
      throw new RuntimeException('Request file not found.');
    }

    $raw = (string)file_get_contents($path);
    $rec = json_decode($raw, true);
    if (!is_array($rec)) throw new RuntimeException('Bad request file JSON.');

    $enc = $rec['encrypted'] ?? null;
    if (!is_array($enc)) throw new RuntimeException('Missing encrypted payload.');

    $payload = hc_decrypt_payload($enc, $ROOT);

    // Normalize fields
    $full_name  = trim((string)($payload['full_name'] ?? ''));
    $account_id = trim((string)($payload['account_id'] ?? ''));
    $role       = trim((string)($payload['role'] ?? ''));

    if ($account_id === '') throw new RuntimeException('Payload missing account_id.');

    if ($action === 'approve') {

      // Create/overwrite an encrypted account record
      $acct = [
        'account_id' => $account_id,
        'full_name'  => $full_name,
        'role'       => $role,
        'status'     => 'active',
        'approved_at'=> gmdate('c'),
        'source_request_token' => $token,
      ];

      $enc_acct = hc_encrypt_payload_for_accounts($acct, $ROOT);

      $acct_record = [
        'created_at' => gmdate('c'),
        'encrypted'  => $enc_acct,
      ];

      $acct_json = json_encode($acct_record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
      if ($acct_json === false) throw new RuntimeException('Failed to encode account record.');

      $acct_path = $ACCOUNTS_DIR . '/' . account_filename_for_id($account_id);
      file_put_contents($acct_path, $acct_json, LOCK_EX);
      @chmod($acct_path, 0600);

      // Move request into processed/approved
      $dest = token_to_path($APPROVED_DIR, $token);
      @rename($path, $dest);

      $flash = 'Approved and stored account record.';
    }
    elseif ($action === 'deny') {
      $dest = token_to_path($DENIED_DIR, $token);
      @rename($path, $dest);
      $flash = 'Denied and archived request.';
    }
    else {
      throw new RuntimeException('Unknown action.');
    }
  }

  // Load view
  if ($view_token !== '') {
    $path = token_to_path($PENDING_DIR, $view_token);
    if ($path !== '' && is_file($path)) {
      $raw = (string)file_get_contents($path);
      $rec = json_decode($raw, true);
      if (is_array($rec) && isset($rec['encrypted']) && is_array($rec['encrypted'])) {
        $view_record = $rec;
        $view_data = hc_decrypt_payload($rec['encrypted'], $ROOT);
      }
    }
  }

} catch (Throwable $e) {
  $flash_err = $diag ? (get_class($e) . ': ' . $e->getMessage()) : 'Action failed.';
}

/* ============================
   List queue
   ============================ */

$items = [];
foreach (glob($PENDING_DIR . '/*.json') ?: [] as $file) {
  $base = basename($file);
  $token = preg_replace('/\.json$/', '', $base);
  if (!preg_match('/^[a-f0-9]{32}$/', $token)) continue;

  $created = '';
  $role = '';
  $account_id = '';

  $raw = @file_get_contents($file);
  $rec = is_string($raw) ? json_decode($raw, true) : null;

  if (is_array($rec)) {
    $created = (string)($rec['created_at'] ?? '');
    if (isset($rec['encrypted']) && is_array($rec['encrypted'])) {
      try {
        $p = hc_decrypt_payload($rec['encrypted'], $ROOT);
        $role = (string)($p['role'] ?? '');
        $account_id = (string)($p['account_id'] ?? '');
      } catch (Throwable $e) {
        $role = '(decrypt failed)';
      }
    }
  }

  $items[] = [
    'token' => $token,
    'created_at' => $created,
    'role' => $role,
    'account_id' => $account_id,
  ];
}

usort($items, function($a, $b) {
  return strcmp((string)$b['created_at'], (string)$a['created_at']);
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Account Requests — Michael Widener II</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light only">
  <style>
    :root { --maxw: 820px; }

/* Prevent scrollbar width shifts between pages */
html {
  overflow-y: scroll;          /* always reserve scrollbar space */
  scrollbar-gutter: stable;    /* modern browsers: keep layout stable */
}


/* Prevent padding/border width drift */
*, *::before, *::after { box-sizing: border-box; }

    html, body { margin:0; padding:0; background:#fff; color:#000; }
    body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"Noto Sans",sans-serif; line-height:1.55; }
    .page { max-width:var(--maxw); margin:2rem auto 3rem; padding:0 1rem; }
    header h1 { font-size:1.85rem; margin:0 0 .35rem 0; font-weight:700; letter-spacing:.2px; }

    .nav-home{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      line-height:1;
      text-decoration: underline;            /* keep link look */
      text-underline-offset: .12em;          /* match your nav spacing */
    }

    .nav-home .fa{
      font-size: 1.15em;                     /* tweak taller */
      line-height:1;
      position: relative;
      top: .02em;                            /* tiny baseline nudge */
    }

    .subtitle { margin:0 0 1rem; font-size:1.05rem; }
    .rule { border:0; border-top:1px solid #000; margin:1rem 0; }
    h2 { font-size:1.12rem; margin:1.5rem 0 .6rem; padding-bottom:.25rem; border-bottom:1px solid #000; }
    a { color:inherit; text-decoration:underline; }
    .note { border:1px solid #000; padding:.65rem .75rem; margin:1rem 0 1.25rem; font-size:.96rem; }
    .ok, .err, .diag { border:1px solid #000; padding:.65rem .75rem; margin:1rem 0; max-width:820px; font-size:.96rem; }
    .diag { border-style:dashed; white-space:pre-wrap; }
    table { width:100%; border-collapse:collapse; margin: .75rem 0 1rem; }
    th, td { border:1px solid #000; padding:.5rem; vertical-align:top; font-size:.95rem; }
    th { text-align:left; }
    .btnrow { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:.75rem; }
    button, a.btn { display:inline-block; padding:.45rem .75rem; font-size:1rem; border:1px solid #000; background:#fff; cursor:pointer; color:inherit; text-decoration:none; }
    pre { white-space:pre-wrap; }
    footer { margin-top:2rem; border-top:1px solid #000; padding-top:1rem; font-size:.92rem; text-align:center; }
  </style>
</head>
<body>
  <div class="page">
    <header>
      <h1>Michael Widener II</h1>
      <!-- NAV-START -->
      <?php include __DIR__ . '/../nav/nav.php'; ?>
      <!-- NAV-END -->
      <hr class="rule" />
    </header>

    <h2>Account Requests</h2>

    <?php if ($flash !== ''): ?>
      <div class="ok"><?php echo h($flash); ?></div>
    <?php endif; ?>

    <?php if ($flash_err !== ''): ?>
      <div class="err"><?php echo h($flash_err); ?></div>
    <?php endif; ?>

    <?php if ($diag): ?>
      <div class="diag">DIAG MODE: enabled
PHP: <?php echo h(PHP_VERSION); ?>

Pending: <?php echo h($PENDING_DIR); ?>

Processed: <?php echo h($PROCESSED_DIR); ?>

Accounts: <?php echo h($ACCOUNTS_DIR); ?>

Key: <?php echo h($ROOT . '/.hc_secret_key'); ?></div>
    <?php endif; ?>

    <div class="note">
      Pending requests are stored in <code>/_pending_account_requests/</code>. Approve/Deny moves them into <code>/_processed/</code>.
    </div>

    <?php if ($view_data !== null && is_array($view_data)): ?>
      <h2>Request Detail</h2>

      <table>
        <tr><th>Reference</th><td><?php echo h($view_token); ?></td></tr>
        <tr><th>Full name</th><td><?php echo h((string)($view_data['full_name'] ?? '')); ?></td></tr>
        <tr><th>Account ID</th><td><?php echo h((string)($view_data['account_id'] ?? '')); ?></td></tr>
        <tr><th>Requested role</th><td><?php echo h((string)($view_data['role'] ?? '')); ?></td></tr>
        <tr><th>Notes</th><td><?php echo h((string)($view_data['notes'] ?? '')); ?></td></tr>
        <tr><th>Submitted</th><td><?php echo h((string)($view_record['created_at'] ?? '')); ?></td></tr>
      </table>

      <form method="post" action="<?php echo h(site_url('/account-requests/')); ?>">
        <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
        <input type="hidden" name="token" value="<?php echo h($view_token); ?>">

        <div class="btnrow">
          <button type="submit" name="action" value="approve">Approve</button>
          <button type="submit" name="action" value="deny">Deny</button>
          <a class="btn" href="<?php echo h(site_url('/account-requests/')); ?>">Back to list</a>
        </div>
      </form>

    <?php else: ?>

      <h2>Pending Queue</h2>

      <?php if (count($items) === 0): ?>
        <p>No pending requests found.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Submitted</th>
              <th>Account ID</th>
              <th>Role</th>
              <th>Reference</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?php echo h((string)$it['created_at']); ?></td>
                <td><?php echo h((string)$it['account_id']); ?></td>
                <td><?php echo h((string)$it['role']); ?></td>
                <td><a href="<?php echo h(site_url('/account-requests/?t=' . rawurlencode((string)$it['token']))); ?>"><?php echo h((string)$it['token']); ?></a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

    <?php endif; ?>

    <footer>
      <p>&copy; <?php echo date('Y'); ?> Michael Widener II</p>
    </footer>
  </div>
</body>
</html>
