<?php
/* FILE: /forgot/index.php */
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';

// If already logged in, just send them home (or account)
if (is_logged_in()) {
    redirect(get_next_path('/account/'));
}

/*
  CONFIG NOTE (required):
  Add this to /auth.php (near your login config):

  define('HC_RESET_KEY', 'PUT-A-LONG-RANDOM-SECRET-HERE');

  Example (make your own):
  define('HC_RESET_KEY', 'mWII-Reset-2025-12-13--b3e9f0b2f6f44c1a8f2d0a7b');
*/

$error = '';
$success = '';
$new_hash = '';

$next = htmlspecialchars(get_next_path('/login/'), ENT_QUOTES);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $reset_key = isset($_POST['reset_key']) ? trim((string)$_POST['reset_key']) : '';
    $pass1     = isset($_POST['password1']) ? (string)$_POST['password1'] : '';
    $pass2     = isset($_POST['password2']) ? (string)$_POST['password2'] : '';

    // Validate basic inputs
    if ($reset_key === '' || $pass1 === '' || $pass2 === '') {
        $error = 'All fields are required.';
    } elseif (!defined('HC_RESET_KEY') || (string)HC_RESET_KEY === '') {
        $error = 'Reset is not configured yet. Missing HC_RESET_KEY in /auth.php.';
    } elseif (!hash_equals((string)HC_RESET_KEY, $reset_key)) {
        // Don’t reveal which part failed
        $error = 'Invalid reset key.';
    } elseif ($pass1 !== $pass2) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass1) < 12) {
        $error = 'Password must be at least 12 characters.';
    } else {
        // Generate a new hash to paste into /auth.php
        $new_hash = password_hash($pass1, PASSWORD_DEFAULT);

        $success = 'Success. Copy the hash below into /auth.php as HC_LOGIN_PASSWORD_HASH, then return to Login.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Forgot Password — Michael Widener II</title>
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

    html, body { margin: 0; padding: 0; background: #fff; color: #000; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                   Helvetica, Arial, "Noto Sans", sans-serif;
      line-height: 1.55;
    }
    .page { max-width: var(--maxw); margin: 2rem auto 3rem; padding: 0 1rem; }
    header h1 { font-size: 1.85rem; margin: 0 0 .35rem 0; font-weight: 700; letter-spacing: .2px; }
    .rule { border: 0; border-top: 1px solid #000; margin: 1rem 0; }

    form { border: 1px solid #000; padding: 1rem; max-width: 520px; }
    label { display: block; margin-top: .75rem; font-size: .95rem; }
    input[type="text"], input[type="password"] {
      width: 100%;
      padding: .45rem;
      font-size: 1rem;
      border: 1px solid #000;
      margin-top: .25rem;
      box-sizing: border-box;
    }
    button {
      margin-top: 1rem;
      padding: .45rem .75rem;
      font-size: 1rem;
      border: 1px solid #000;
      background: #fff;
      cursor: pointer;
    }
    .box {
      margin: 1rem 0;
      padding: .65rem .75rem;
      border: 1px solid #000;
      max-width: 720px;
      font-size: .96rem;
      white-space: pre-wrap;
      word-break: break-word;
    }
    .error { }
    .success { }
    code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
    a { color: inherit; text-decoration: underline; }
  </style>
</head>

<body>
  <div class="page">
    <header>
      <h1>Forgot Password</h1>

      <!-- NAV-START -->
      <?php include __DIR__ . '/../nav/nav.php'; ?>
      <!-- NAV-END -->

      <hr class="rule" />
    </header>

    <p>
      This is a private reset screen. It does not email anything.
      If you have the Reset Key, you can generate a new password hash to paste into <code>/auth.php</code>.
    </p>

    <?php if ($error !== ''): ?>
      <div class="box error"><strong>Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
      <div class="box success"><strong><?php echo htmlspecialchars($success, ENT_QUOTES); ?></strong></div>
    <?php endif; ?>

    <?php if ($new_hash !== ''): ?>
      <div class="box">
Copy this into /auth.php:

define('HC_LOGIN_PASSWORD_HASH', '<?php echo htmlspecialchars($new_hash, ENT_QUOTES); ?>');
      </div>

      <p>
        After updating <code>/auth.php</code>, go back to
        <a href="/login/">Login</a>.
      </p>
      <hr class="rule" />
    <?php endif; ?>

    <form method="post" action="">
      <input type="hidden" name="next" value="<?php echo $next; ?>" />

      <label>
        Reset Key
        <input type="text" name="reset_key" autocomplete="off" required />
      </label>

      <label>
        New Password (12+ characters)
        <input type="password" name="password1" autocomplete="new-password" required />
      </label>

      <label>
        Confirm New Password
        <input type="password" name="password2" autocomplete="new-password" required />
      </label>

      <button type="submit">Generate New Hash</button>
    </form>

    <p style="margin-top: 1rem;">
      <a href="/login/">← Back to Login</a>
    </p>
  </div>
</body>
</html>
