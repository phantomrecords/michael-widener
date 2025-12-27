<?php
// /account-create/index.php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';

$logged_in = is_logged_in();

/* ============================
   DIAG MODE (safe)
   ============================ */
$diag = (isset($_GET['diag']) && $_GET['diag'] === '1');

/* ============================
   Security helpers (CSRF)
   ============================ */
function hc_csrf_token(): string
{
    if (empty($_SESSION['csrf_account_create']) || !is_string($_SESSION['csrf_account_create'])) {
        $_SESSION['csrf_account_create'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_account_create'];
}

function hc_csrf_check(?string $token): bool
{
    $sess = $_SESSION['csrf_account_create'] ?? '';
    if (!is_string($sess) || $sess === '') return false;
    if (!is_string($token) || $token === '') return false;
    return hash_equals($sess, $token);
}

/* ============================
   Encrypt-at-rest (pending requests)
   ============================ */
function hc_secret_key_bytes(): string
{
    // Preferred: environment variable
    $b64 = getenv('HC_SECRET_KEY_B64');
    if (is_string($b64) && $b64 !== '') {
        $raw = base64_decode($b64, true);
        if (is_string($raw) && strlen($raw) === 32) return $raw;
    }

    // Fallback: locked file in site root
    $path = dirname(__DIR__) . '/.hc_secret_key'; // /htdocs/.hc_secret_key
    if (is_file($path)) {
        $raw = trim((string)file_get_contents($path));
        $bin = base64_decode($raw, true);
        if (is_string($bin) && strlen($bin) === 32) return $bin;
    }

    throw new RuntimeException('Secret key not configured. Create /.hc_secret_key (base64, 32 bytes).');
}

function hc_encrypt_payload(array $data): array
{
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) throw new RuntimeException('Failed to encode payload.');
    $key = hc_secret_key_bytes();

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
            'tag_b64' => base64_encode($tag),
            'ct_b64' => base64_encode($ct),
        ];
    }

    throw new RuntimeException('No encryption backend available (libsodium or openssl required).');
}

function hc_pending_store_dir(): string
{
    $dir = dirname(__DIR__) . '/_pending_account_requests'; // /htdocs/_pending_account_requests
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    if (!is_dir($dir)) {
        throw new RuntimeException('Cannot create _pending_account_requests directory (permissions).');
    }
    return $dir;
}

function hc_log_error(string $msg): void
{
    try {
        $dir = hc_pending_store_dir();
        $path = $dir . '/_errors.log';
        $line = '[' . gmdate('c') . '] ' . $msg . "\n";
        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        @chmod($path, 0600);
    } catch (Throwable $e) {
        // If logging fails, do nothing—never break UX further.
    }
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
    if ($json === false) throw new RuntimeException('Failed to encode record.');

    $ok = @file_put_contents($path, $json, LOCK_EX);
    if ($ok === false) {
        throw new RuntimeException('Failed to write pending record (permissions).');
    }
    @chmod($path, 0600);

    return $token;
}

function hc_send_account_request_email(string $to_email, string $to_name, string $token): void
{
    $host = $_SERVER['HTTP_HOST'] ?? 'michaelwidener.com';
    $subject = 'Account Request Received';
    $safeName = $to_name !== '' ? $to_name : 'there';

    $body =
        "Hi {$safeName},\n\n" .
        "We received an account request.\n\n" .
        "If an account exists for the information provided—or if the request is eligible—an email will be sent with next steps.\n\n" .
        "Reference: {$token}\n\n" .
        "If you did not initiate this request, you can ignore this message.\n\n" .
        "— {$host}\n";

    $from = 'no-reply@' . $host;
    $headers =
        "From: {$from}\r\n" .
        "Reply-To: {$from}\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n";

    // mail() may be disabled; that's OK. We treat email as "best effort".
    @mail($to_email, $subject, $body, $headers);
}

/* ============================
   Form handling
   ============================ */
$roles = [
    'Owner',
    'Investigator',
    'Attorney',
    'Accountant',
    'Police Officer',
    'Security Guard',
    'Sheriff',
    'Contractor',
];

$draft = $_SESSION['account_create_draft'] ?? [];
if (!is_array($draft)) $draft = [];

$state = 'form';
$error = '';
$ref = '';
$diag_out = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $draft = [
        'account_id' => trim((string)($_POST['account_id'] ?? '')),
        'full_name'  => trim((string)($_POST['full_name'] ?? '')),
        'role'       => trim((string)($_POST['role'] ?? '')),
        'notes'      => trim((string)($_POST['notes'] ?? '')),
    ];
    $_SESSION['account_create_draft'] = $draft;

    if (!hc_csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Session expired. Please refresh and try again.';
    } else {

        $account_id = $draft['account_id'];
        $full_name  = $draft['full_name'];
        $role       = $draft['role'];
        $notes      = $draft['notes'];

        if ($full_name === '') {
            $error = 'Full name is required.';
        } elseif ($account_id === '') {
            $error = 'Account ID is required.';
        } elseif (!filter_var($account_id, FILTER_VALIDATE_EMAIL)) {
            $error = 'Account ID must be an email address for verification delivery.';
        } elseif ($role === '' || !in_array($role, $roles, true)) {
            $error = 'Please select a valid role.';
        } else {

            try {
                $payload = [
                    'account_id' => $account_id,
                    'full_name'  => $full_name,
                    'role'       => $role,
                    'notes'      => $notes,
                    'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
                    'ua'         => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'path'       => $_SERVER['REQUEST_URI'] ?? '',
                    'ts'         => time(),
                ];

                $ref = hc_store_pending_account_request($payload);
                hc_send_account_request_email($account_id, $full_name, $ref);

                unset($_SESSION['account_create_draft']);
                $state = 'submitted';

            } catch (Throwable $e) {
                $msg = get_class($e) . ': ' . $e->getMessage();
                hc_log_error('account-create failed: ' . $msg);

                $error = 'Unable to submit request at this time. Please try again later.';
                if ($diag) {
                    $diag_out = "DIAG: " . $msg;
                }
            }
        }
    }
}

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES); }

$csrf = h(hc_csrf_token());
$account_id_val = h($draft['account_id'] ?? '');
$full_name_val  = h($draft['full_name'] ?? '');
$role_val       = h($draft['role'] ?? '');
$notes_val      = h($draft['notes'] ?? '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Account Create — Michael Widener GP</title>
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

    header h1 { font-size:1.85rem; margin:0 0 .35rem 0; font-weight:700; letter-spacing:.2px; }
    .subtitle { margin:0 0 1rem; font-size:1.05rem; }
    .rule { border:0; border-top:1px solid #000; margin:1rem 0; }
    h2 { font-size:1.12rem; margin:1.5rem 0 .6rem; padding-bottom:.25rem; border-bottom:1px solid #000; }
    p { margin:.35rem 0 .75rem; }
    a { color:inherit; text-decoration:underline; }
    .box { border:1px solid #000; padding:1rem; max-width:620px; }
    label { display:block; margin-top:.75rem; font-size:.95rem; }
    input[type="text"], select, textarea { width:100%; padding:.45rem; font-size:1rem; border:1px solid #000; margin-top:.25rem; box-sizing:border-box; background:#fff; color:#000; }
    textarea { min-height:120px; resize:vertical; }
    .btnrow { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:1rem; }
    button, a.btn { display:inline-block; padding:.45rem .75rem; font-size:1rem; border:1px solid #000; background:#fff; cursor:pointer; color:inherit; text-decoration:none; white-space:nowrap; }
    .error { margin:1rem 0; padding:.65rem .75rem; border:1px solid #000; max-width:620px; font-size:.96rem; }
    .note { border:1px solid #000; padding:.65rem .75rem; margin:1rem 0 1.25rem; font-size:.96rem; max-width:620px; }
    .diag { border:1px dashed #000; padding:.65rem .75rem; margin:1rem 0; max-width:620px; font-size:.94rem; white-space:pre-wrap; }
    footer { margin-top:2rem; border-top:1px solid #000; padding-top:1rem; font-size:.92rem; text-align:center; }
  </style>
</head>

<body>
  <div class="page">
    <header>
      <h1>Michael Widener GP</h1>
      <?php include __DIR__ . '/../nav/nav.php'; ?>
      <hr class="rule" />
    </header>

    <section>
      <h2>Account Create</h2>

      <?php if ($error !== ''): ?>
        <div class="error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <?php if ($diag && $diag_out !== ''): ?>
        <div class="diag"><?php echo h($diag_out); ?></div>
      <?php elseif ($diag): ?>
        <div class="diag">
DIAG MODE: enabled
Pending dir: <?php echo h(dirname(__DIR__) . '/_pending_account_requests'); ?>

Key file: <?php echo h(dirname(__DIR__) . '/.hc_secret_key'); ?>

If submit fails, we’ll show the exact exception here.
        </div>
      <?php endif; ?>

      <?php if ($state === 'submitted'): ?>
        <div class="box">
          <p><strong>Request received.</strong></p>
          <p>
            If the information provided is eligible, you will receive next steps by email.
            No account is created automatically.
          </p>
          <p><strong>Reference:</strong> <?php echo h($ref); ?></p>
          <div class="btnrow">
            <a class="btn" href="/">Return to Home</a>
            <a class="btn" href="/iforgot/">Password Recovery</a>
          </div>
        </div>
      <?php else: ?>

        <div class="note">
          This request is delivered for finalization before an account becomes active.
        </div>

        <form class="box" method="post" action="/account-create/<?php echo $diag ? '?diag=1' : ''; ?>">
          <input type="hidden" name="csrf" value="<?php echo $csrf; ?>" />

          <label>
            Full name
            <input type="text" name="full_name" value="<?php echo $full_name_val; ?>" autocomplete="name" required />
          </label>

          <label>
            Account ID (can be username or email address)
            <input type="text" name="account_id" value="<?php echo $account_id_val; ?>" autocomplete="username" required />
          </label>

          <label>
            Requested role
            <select name="role" required>
              <option value="" <?php echo ($role_val === '' ? 'selected' : ''); ?>>Select…</option>
              <?php foreach ($roles as $r): ?>
                <option value="<?php echo h($r); ?>" <?php echo ($role_val === $r ? 'selected' : ''); ?>>
                  <?php echo h($r); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label>
            Notes (optional)
            <textarea name="notes" placeholder="Add context, case reference, or why access is needed…"><?php echo $notes_val; ?></textarea>
          </label>

          <div class="btnrow">
            <button type="submit">Send request</button>
            <a class="btn" href="/iforgot/">I forgot my password</a>
            <a class="btn" href="/">Cancel</a>
          </div>
        </form>

      <?php endif; ?>
    </section>

    <footer>
      <p>&copy; <?php echo date('Y'); ?> Michael Widener GP</p>
    </footer>
  </div>
</body>
</html>
