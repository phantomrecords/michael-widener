<?php
declare(strict_types=1);

require_once __DIR__ . '/../../auth.php';
require_login('/account/approve/');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }

$diag = (isset($_GET['diag']) && $_GET['diag'] === '1');

$ROOT = dirname(__DIR__, 2); // site root (htdocs)
$PENDING_DIR  = $ROOT . '/_pending_account_requests';
$ACCOUNTS_DIR = $ROOT . '/_accounts';

function hc_secret_key_bytes(string $root): string
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

function hc_decrypt(array $enc, string $root): array
{
    $key = hc_secret_key_bytes($root);
    $alg = (string)($enc['alg'] ?? '');

    if ($alg === 'secretbox') {
        $nonce = base64_decode((string)$enc['nonce_b64'], true);
        $ct    = base64_decode((string)$enc['ct_b64'], true);
        $pt = sodium_crypto_secretbox_open($ct, $nonce, $key);
        if (!is_string($pt)) throw new RuntimeException('Decrypt failed.');
        $data = json_decode($pt, true);
        if (!is_array($data)) throw new RuntimeException('Bad JSON.');
        return $data;
    }

    if ($alg === 'aes-256-gcm') {
        $nonce = base64_decode((string)$enc['nonce_b64'], true);
        $tag   = base64_decode((string)$enc['tag_b64'], true);
        $ct    = base64_decode((string)$enc['ct_b64'], true);
        $pt = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
        if (!is_string($pt)) throw new RuntimeException('Decrypt failed.');
        $data = json_decode($pt, true);
        if (!is_array($data)) throw new RuntimeException('Bad JSON.');
        return $data;
    }

    throw new RuntimeException('Unknown encryption.');
}

$err = '';
$ok  = '';
$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$token = preg_replace('/[^a-f0-9]/', '', strtolower($token ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['token']) ? trim((string)$_POST['token']) : '';
    $token = preg_replace('/[^a-f0-9]/', '', strtolower($token ?? ''));
    if ($token === '' || strlen($token) !== 32) {
        $err = 'Missing or invalid reference token.';
    } else {
        try {
            if (!is_dir($ACCOUNTS_DIR)) @mkdir($ACCOUNTS_DIR, 0700, true);

            $pendingPath = $PENDING_DIR . '/' . $token . '.json';
            if (!is_file($pendingPath)) throw new RuntimeException('Pending request not found: ' . $token);

            $raw = (string)file_get_contents($pendingPath);
            $rec = json_decode($raw, true);
            if (!is_array($rec) || !isset($rec['encrypted'])) throw new RuntimeException('Pending record invalid.');

            $payload = hc_decrypt($rec['encrypted'], $ROOT);

            // Build “account record”
            $account = [
                'full_name'   => (string)($payload['full_name'] ?? ''),
                'account_id'  => (string)($payload['account_id'] ?? ''),
                'role'        => (string)($payload['role'] ?? ''),
                'status'      => 'approved',
                'approved_at' => gmdate('c'),
            ];

            // Encrypt the account using the same encrypt format by reusing secretbox/aes
            // (We re-encrypt by calling the same crypto backend)
            $json = json_encode($account, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false) throw new RuntimeException('Account encode failed.');

            $key = hc_secret_key_bytes($ROOT);

            if (function_exists('sodium_crypto_secretbox')) {
                $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
                $ct = sodium_crypto_secretbox($json, $nonce, $key);
                $enc = ['alg'=>'secretbox','nonce_b64'=>base64_encode($nonce),'ct_b64'=>base64_encode($ct)];
            } elseif (function_exists('openssl_encrypt')) {
                $nonce = random_bytes(12);
                $tag = '';
                $ct = openssl_encrypt($json, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
                if (!is_string($ct)) throw new RuntimeException('OpenSSL encrypt failed.');
                $enc = ['alg'=>'aes-256-gcm','nonce_b64'=>base64_encode($nonce),'tag_b64'=>base64_encode($tag),'ct_b64'=>base64_encode($ct)];
            } else {
                throw new RuntimeException('No encryption backend available.');
            }

            $acctRecord = [
                'created_at' => gmdate('c'),
                'encrypted'  => $enc,
            ];

            // Single-account file for now (your owner account). You can later key by account_id.
            $acctPath = $ACCOUNTS_DIR . '/acct_owner.json';
            $out = json_encode($acctRecord, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if ($out === false) throw new RuntimeException('Account record write failed.');

            file_put_contents($acctPath, $out, LOCK_EX);
            @chmod($acctPath, 0600);

            // Optional: delete the pending request after approval
            @unlink($pendingPath);

            $ok = 'Approved. Account record created at /_accounts/acct_owner.json';
        } catch (Throwable $e) {
            $err = $diag ? (get_class($e) . ': ' . $e->getMessage()) : 'Approval failed.';
        }
    }
}

// List pending requests
$pending = [];
if (is_dir($PENDING_DIR)) {
    $files = glob($PENDING_DIR . '/*.json') ?: [];
    foreach ($files as $f) {
        $pending[] = basename($f, '.json');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Approve Requests — Michael Widener II</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light only">
  <style>
    :root{--maxw:820px;}

/* Prevent scrollbar width shifts between pages */
html {
  overflow-y: scroll;          /* always reserve scrollbar space */
  scrollbar-gutter: stable;    /* modern browsers: keep layout stable */
}


/* Prevent padding/border width drift */
*, *::before, *::after { box-sizing: border-box; }

    html,body{margin:0;padding:0;background:#fff;color:#000;}
    body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"Noto Sans",sans-serif;line-height:1.55;}
    .page{max-width:var(--maxw);margin:2rem auto 3rem;padding:0 1rem;}
    header h1{font-size:1.85rem;margin:0 0 .35rem 0;font-weight:700;letter-spacing:.2px;}
    .subtitle{margin:0 0 1rem;font-size:1.05rem;}
    .rule{border:0;border-top:1px solid #000;margin:1rem 0;}
    h2{font-size:1.12rem;margin:1.5rem 0 .6rem;padding-bottom:.25rem;border-bottom:1px solid #000;}
    .note{border:1px solid #000;padding:.65rem .75rem;margin:1rem 0;font-size:.96rem;}
    .ok{border:1px solid #000;padding:.65rem .75rem;margin:1rem 0;font-size:.96rem;}
    input{width:100%;padding:.45rem;border:1px solid #000;box-sizing:border-box;}
    button,a.btn{display:inline-block;padding:.45rem .75rem;font-size:1rem;border:1px solid #000;background:#fff;cursor:pointer;color:inherit;text-decoration:none;}
    .row{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;}
    ul{margin:.25rem 0 .75rem 1.2rem;padding:0;}
    a{color:inherit;text-decoration:underline;}
  </style>
</head>
<body>
  <div class="page">
    <header>
      <h1>Michael Widener II</h1>
      <!-- NAV-START -->
      <?php include __DIR__ . '/../../nav/nav.php'; ?>
      <!-- NAV-END -->
      <hr class="rule" />
    </header>

    <h2>Owner Approval Queue</h2>

    <?php if ($err !== ''): ?><div class="note"><?php echo h($err); ?></div><?php endif; ?>
    <?php if ($ok !== ''): ?><div class="ok"><?php echo h($ok); ?></div><?php endif; ?>

    <div class="note">
      Paste the “Reference” token from the email (32 hex chars), then Approve.
    </div>

    <form method="post" action="">
      <label>Reference token
        <input name="token" value="<?php echo h($token); ?>" placeholder="e.g. 5b3a508306dab6619b52bcf308fc8c17" />
      </label>
      <div class="row" style="margin-top:1rem;">
        <button type="submit">Approve request</button>
        <a class="btn" href="<?php echo h(site_url('/account/')); ?>">View Account</a>
        <a class="btn" href="<?php echo h(site_url('/account/approve/')) . ($diag ? '?diag=1' : ''); ?>">Refresh</a>
      </div>
    </form>

    <h2>Pending requests found</h2>
    <?php if (count($pending) === 0): ?>
      <p>None.</p>
    <?php else: ?>
      <ul>
        <?php foreach ($pending as $t): ?>
          <li>
            <code><?php echo h($t); ?></code>
            — <a href="<?php echo h(site_url('/account/approve/?token=' . rawurlencode($t) . ($diag ? '&diag=1' : ''))); ?>">load</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

  </div>
</body>
</html>
