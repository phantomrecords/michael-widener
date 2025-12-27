<?php
/* FILE: /account/create/index.php */
declare(strict_types=1);

require_once __DIR__ . '/../../auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/**
 * UX goal:
 * - feel “official”
 * - user believes: request sent elsewhere for finalization (no account created yet)
 * - data trapped + encrypted at rest (pending only)
 */

$errors = [];
$success = false;

$account_id = trim((string)($_POST['account_id'] ?? ''));
$display_name = trim((string)($_POST['display_name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$notes = trim((string)($_POST['notes'] ?? ''));

function is_valid_account_id(string $s): bool {
  // allow username OR email; do not label as username/email
  if ($s === '') return false;
  if (filter_var($s, FILTER_VALIDATE_EMAIL)) return true;
  return (bool)preg_match('/^[A-Za-z0-9._-]{3,32}$/', $s);
}
function is_valid_email(string $s): bool {
  return (bool)filter_var($s, FILTER_VALIDATE_EMAIL);
}
function is_valid_display_name(string $s): bool {
  return $s !== '' && mb_strlen($s) <= 80;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Save draft-in-progress into session (so it persists while they work)
  $_SESSION['create_account_draft'] = [
    'account_id' => $account_id,
    'display_name' => $display_name,
    'email' => $email,
    'notes' => $notes,
  ];

  // Validate
  if (!is_valid_account_id($account_id)) {
    $errors[] = 'Please enter a valid Account ID (username-style or email-style).';
  }
  if (!is_valid_display_name($display_name)) {
    $errors[] = 'Please enter your display name.';
  }
  if (!is_valid_email($email)) {
    $errors[] = 'Please enter a valid email address.';
  }
  if (mb_strlen($notes) > 1500) {
    $errors[] = 'Notes are too long (max 1500 characters).';
  }

  if (!$errors) {
    // Package what the user entered
    $payload = [
      'account_id' => $account_id,
      'display_name' => $display_name,
      'email' => $email,
      'notes' => $notes,
      'submitted_at' => gmdate('c'),
      'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
      'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ];

    // Store encrypted pending request (NOT a real account)
    // and email the user a confirmation.
    $token = hc_store_pending_account_request($payload);

    // Email user (confirmation / verification link feel)
    hc_send_account_request_email($email, $display_name, $token);

    // Clear draft (so they feel it’s not “saved as an account”)
    unset($_SESSION['create_account_draft']);

    $success = true;
  }
}

// On GET, prefill from draft if available
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['create_account_draft']) && is_array($_SESSION['create_account_draft'])) {
  $d = $_SESSION['create_account_draft'];
  $account_id   = (string)($d['account_id'] ?? '');
  $display_name = (string)($d['display_name'] ?? '');
  $email        = (string)($d['email'] ?? '');
  $notes        = (string)($d['notes'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Create Account — Michael Widener II</title>
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
    body {
      font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"Noto Sans",sans-serif;
      line-height:1.55;
    }
    .page { max-width:var(--maxw); margin:2rem auto 3rem; padding:0 1rem; }
    header h1 { font-size:1.85rem; margin:0 0 .35rem; font-weight:700; letter-spacing:.2px; }
    .rule { border:0; border-top:1px solid #000; margin:1rem 0; }
    .small { font-size:.92rem; opacity:.95; }
    form { border:1px solid #000; padding:1rem; max-width:640px; }
    label { display:block; margin-top:.75rem; font-size:.95rem; }
    input[type="text"], textarea {
      width:100%; padding:.45rem; font-size:1rem; border:1px solid #000; margin-top:.25rem; box-sizing:border-box;
    }
    textarea { min-height: 120px; resize: vertical; }
    button { margin-top:1rem; padding:.45rem .75rem; font-size:1rem; border:1px solid #000; background:#fff; cursor:pointer; }
    .box { border:1px solid #000; padding:.75rem; margin:1rem 0; }
    a { color:inherit; text-decoration:underline; }
    .ok { color:#0a7a0a; font-weight:700; }
    .bad { color:#b00000; }
    ul { margin:.25rem 0 .75rem 1.2rem; }
  </style>
</head>
<body>
<div class="page">
  <header>
    <h1>Create Account</h1>
    <?php include __DIR__ . '/../../nav/nav.php'; ?>
    <hr class="rule" />
  </header>

  <?php if ($success): ?>
    <div class="box">
      <div class="ok">Request received.</div>
      <p class="small" style="margin:.5rem 0 0 0;">
        If an account exists for the information provided—or if the request is eligible—an email will be sent with next steps.
      </p>
      <p class="small" style="margin:.5rem 0 0 0;">
        <strong>No account has been created yet.</strong> This request is routed for finalization before any account becomes active.
      </p>
      <p style="margin:.75rem 0 0 0;">
        <a href="/login/">← Back to Sign In</a>
      </p>
    </div>
  <?php else: ?>

    <div class="small">
      Enter your details below. Your request will be sent for finalization.
      <strong>No account is created at this step.</strong>
    </div>

    <?php if ($errors): ?>
      <div class="box bad">
        <strong>Please fix the following:</strong>
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?php echo htmlspecialchars($e, ENT_QUOTES); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="/account/create/">
      <label>
        Account ID (Can be username or email address)
        <input
          type="text"
          name="account_id"
          value="<?php echo htmlspecialchars($account_id, ENT_QUOTES); ?>"
          autocomplete="username"
          pattern="([A-Za-z0-9._-]{3,32})|([^@\s]+@[^@\s]+\.[^@\s]+)"
          required
        />
      </label>

      <label>
        Display Name
        <input
          type="text"
          name="display_name"
          value="<?php echo htmlspecialchars($display_name, ENT_QUOTES); ?>"
          autocomplete="name"
          required
        />
      </label>

      <label>
        Email Address
        <input
          type="text"
          name="email"
          value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>"
          autocomplete="email"
          required
        />
      </label>

      <label>
        Notes (optional)
        <textarea name="notes"><?php echo htmlspecialchars($notes, ENT_QUOTES); ?></textarea>
      </label>

      <button type="submit">Send for Finalization</button>
    </form>

    <p style="margin-top:1rem;">
      <a href="/login/">← Back to Sign In</a>
    </p>

  <?php endif; ?>
</div>
</body>
</html>
