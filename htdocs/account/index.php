<?php
declare(strict_types=1);

/**
 * /account/index.php
 * Read-only account view.
 * Edit is intentionally gated for future security workflow.
 */

require_once __DIR__ . '/../auth.php';
require_login('/account/');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }

$diag = (isset($_GET['diag']) && $_GET['diag'] === '1');

/* ============================
   Paths
   ============================ */

$ROOT = dirname(__DIR__); // /htdocs
$ACCOUNTS_DIR = $ROOT . '/_accounts';

/* ============================
   Crypto (same as approval flow)
   ============================ */

function hc_secret_key_bytes_account(string $root): string
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

    throw new RuntimeException('Secret key not configured.');
}

function hc_decrypt_account(array $enc, string $root): array
{
    $key = hc_secret_key_bytes_account($root);
    $alg = (string)($enc['alg'] ?? '');

    if ($alg === 'secretbox') {
        $nonce = base64_decode((string)($enc['nonce_b64'] ?? ''), true);
        $ct    = base64_decode((string)($enc['ct_b64'] ?? ''), true);
        if (!is_string($nonce) || !is_string($ct)) throw new RuntimeException('Bad secretbox fields.');
        $pt = sodium_crypto_secretbox_open($ct, $nonce, $key);
        if (!is_string($pt)) throw new RuntimeException('Decrypt failed.');
        $data = json_decode($pt, true);
        if (!is_array($data)) throw new RuntimeException('Bad JSON.');
        return $data;
    }

    if ($alg === 'aes-256-gcm') {
        $nonce = base64_decode((string)($enc['nonce_b64'] ?? ''), true);
        $tag   = base64_decode((string)($enc['tag_b64'] ?? ''), true);
        $ct    = base64_decode((string)($enc['ct_b64'] ?? ''), true);
        if (!is_string($nonce) || !is_string($tag) || !is_string($ct)) throw new RuntimeException('Bad gcm fields.');
        $pt = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
        if (!is_string($pt)) throw new RuntimeException('Decrypt failed.');
        $data = json_decode($pt, true);
        if (!is_array($data)) throw new RuntimeException('Bad JSON.');
        return $data;
    }

    throw new RuntimeException('Unknown encryption.');
}

/* ============================
   Load account record
   ============================ */

$account = null;
$err = '';

try {
    // You currently have a single-owner model.
    // Load the first account file found.
    $files = glob($ACCOUNTS_DIR . '/acct_*.json') ?: [];
    if (count($files) === 0) {
        throw new RuntimeException('No account record found.');
    }

    $raw = (string)file_get_contents($files[0]);
    $rec = json_decode($raw, true);
    if (!is_array($rec) || !isset($rec['encrypted']) || !is_array($rec['encrypted'])) {
        throw new RuntimeException('Invalid account record.');
    }

    $account = hc_decrypt_account($rec['encrypted'], $ROOT);

} catch (Throwable $e) {
    $err = $diag ? (get_class($e) . ': ' . $e->getMessage()) : 'Unable to load account.';
}

/* ============================
   SUPER CHECK (for Review link)
   ============================ */

$super =
    (function_exists('is_super') && is_super())
    || (trim((string)($_SESSION['account_role'] ?? '')) === 'Super')
    || (trim((string)($_SESSION['role'] ?? '')) === 'Super');

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Account — Michael Widener GP</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light only">
  <style>
    :root { --maxw: 820px; }

/* Prevent scrollbar width shifts between pages */
html { scrollbar-gutter: stable; }

/* Prevent padding/border width drift */
*, *::before, *::after { box-sizing: border-box; }

    html, body { margin:0; padding:0; background:#fff; color:#000; }
    body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"Noto Sans",sans-serif; line-height:1.55; }
    .page { max-width:var(--maxw); margin:2rem auto 3rem; padding:0 1rem; }
    header h1 { font-size:1.85rem; margin:0 0 .35rem 0; font-weight:700; letter-spacing:.2px; }
    .subtitle { margin:0 0 1rem; font-size:1.05rem; }
    .rule { border:0; border-top:1px solid #000; margin:1rem 0; }

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

h2 {
  font-size: 1.12rem;
  font-weight: 400; /* remove bold */
  margin: 1.5rem 0 .6rem;
  padding-bottom: .25rem;
  border-bottom: 1px solid #000;
}
    table { width:100%; border-collapse:collapse; margin:.75rem 0; }
    th, td { border:1px solid #000; padding:.5rem; font-size:.95rem; text-align:left; }
    .note { border:1px solid #000; padding:.65rem .75rem; margin:1rem 0; font-size:.96rem; }
    .btnrow { display:flex; gap:.5rem; margin-top:1rem; }
    a.btn, button { border:1px solid #000; background:#fff; padding:.45rem .75rem; cursor:pointer; text-decoration:none; color:inherit; }
    .diag { border:1px dashed #000; padding:.65rem .75rem; margin:1rem 0; white-space:pre-wrap; font-size:.94rem; }
    footer { margin-top:2rem; border-top:1px solid #000; padding-top:1rem; font-size:.92rem; text-align:center; }

     */ Added Monday, December 15, 2025 */
.accttabs {
  font-size: .95rem;
  font-weight: 400; /* explicit normal */
}

.accttabs .tab {
  font-weight: 400;                 /* kill bold everywhere */
  color: #551A8B;                   /* match main nav link color */
  text-decoration: underline;
  text-underline-offset: 2px;
  text-decoration-thickness: 1px;
}

.accttabs .tab.active {
  text-decoration-style: dashed;    /* active indicator */
}

  </style>
</head>
<body>
  <div class="page">
    <header>
      <h1>Michael Widener GP</h1>
      <!-- NAV-START -->
      <?php include __DIR__ . '/../nav/nav.php'; ?>
      <!-- NAV-END -->
      <hr class="rule" />
    </header>

<?php
echo '<pre style="border:1px solid #000;padding:.5rem;margin:1rem 0;">';
echo 'SESSION role keys:' . "\n";
echo '$_SESSION["role"] = ' . ($_SESSION['role'] ?? '(unset)') . "\n";
echo '$_SESSION["account_role"] = ' . ($_SESSION['account_role'] ?? '(unset)') . "\n";
echo '$_SESSION["approved"] = ' . ($_SESSION['approved'] ?? '(unset)') . "\n";
echo '</pre>';
?>

<?php
echo '<pre style="border:1px dashed #000;padding:.5rem;margin:1rem 0;">';
echo 'AUTH FILE: ' . (function_exists('auth_running_path') ? auth_running_path() : '(missing)') . "\n";
echo 'function_exists(is_super): ' . (function_exists('is_super') ? 'YES' : 'NO') . "\n";
echo 'is_super(): ' . ((function_exists('is_super') && is_super()) ? 'YES' : 'NO') . "\n";
echo '$super (local): ' . ($super ? 'YES' : 'NO') . "\n";
echo '</pre>';
?>

<h2 style="display:flex; align-items:baseline; justify-content:space-between; gap:1rem;">
  <span>Account:</span>

  <span class="accttabs">
    <a class="tab active" href="<?php echo h(site_url('/account/')); ?>">Information</a> <span class="tabsep">|</span> 
    <a class="tab" href="<?php echo h(site_url('/account/details/')); ?>">Details</a> <span class="tabsep">|</span> 
    <a class="tab" href="<?php echo h(site_url('/account/edit/')); ?>">Edit</a> <span class="tabsep">|</span> 
    <a class="tab" href="<?php echo h(site_url('/account/manage/')); ?>">Manage</a>

    <?php if ($super): ?>
      <span class="tabsep">|</span>
      <a class="tab" href="<?php echo h(site_url('/accounts/review/')); ?>">Review</a>
    <?php endif; ?>
  </span>
</h2>


    <?php if ($err !== ''): ?>
      <div class="note"><?php echo h($err); ?></div>
    <?php elseif (is_array($account)): ?>

      <table>
        <tr><th>Full name</th><td><?php echo h((string)($account['full_name'] ?? '')); ?></td></tr>
        <tr><th>Account ID</th><td><?php echo h((string)($account['account_id'] ?? '')); ?></td></tr>
        <tr><th>Role</th><td><?php echo h((string)($account['role'] ?? '')); ?></td></tr>
        <tr><th>Status</th><td><?php echo h((string)($account['status'] ?? '')); ?></td></tr>
        <tr><th>Approved</th><td><?php echo h((string)($account['approved_at'] ?? '')); ?></td></tr>
      </table>

      <div class="note">
        This account is displayed in read-only mode.
        Changes require additional verification.
      </div>

      <div class="btnrow">
        <a class="btn" href="<?php echo h(site_url('/account/edit/')); ?>">Edit (verification required)</a>
        <a class="btn" href="<?php echo h(site_url('/')); ?>">Home</a>
      </div>

    <?php endif; ?>

    <?php if ($diag): ?>
      <div class="diag">DIAG MODE
Account dir: <?php echo h($ACCOUNTS_DIR); ?>

Auth: is_logged_in() = <?php echo is_logged_in() ? 'YES' : 'NO'; ?></div>
    <?php endif; ?>

    <footer>
      <p>&copy; <?php echo date('Y'); ?> Michael Widener GP</p>
    </footer>
  </div>
</body>
</html>
