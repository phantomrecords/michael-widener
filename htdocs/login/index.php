<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }

// If already logged in, go to next (or home)
if (is_logged_in()) {
    redirect(get_next_path('/'));
}

$error = '';
$next_raw = get_next_path('/');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $account_id = isset($_POST['account_id']) ? trim((string)$_POST['account_id']) : '';
    $password   = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($account_id === '' || $password === '') {
        $error = 'Account ID and password are required.';
    } elseif (auth_check_credentials($account_id, $password)) {
        login_user($account_id);
        redirect(get_next_path('/'));
    } else {
        $error = 'Invalid login credentials.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Login — Michael Widener GP</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light only">
  <style>
    :root { --maxw: 820px; }
    html { overflow-y: scroll; scrollbar-gutter: stable; }
    *, *::before, *::after { box-sizing: border-box; }
    html, body { margin:0; padding:0; background:#fff; color:#000; }
    body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"Noto Sans",sans-serif; line-height:1.55; }
    .page { max-width: var(--maxw); margin: 2rem auto 3rem; padding: 0 1rem; }
    header h1 { font-size: 1.85rem; margin: 0 0 .35rem 0; font-weight: 700; letter-spacing: .2px; }
    .subtitle { margin: 0 0 1rem; font-size: 1.05rem; }
    .rule { border:0; border-top:1px solid #000; margin: 1rem 0; }
    a { color: inherit; text-decoration: underline; }
    form { border: 1px solid #000; padding: 1rem; max-width: 520px; }
    label { display:block; margin-top: .75rem; font-size: .95rem; }
    input[type="text"], input[type="password"] { width:100%; padding:.45rem; font-size:1rem; border:1px solid #000; margin-top:.25rem; }
    .pwrow { display:flex; gap:.5rem; align-items:flex-end; }
    .pwrow .pwcol { flex: 1; }
    .pwrow .pwbtn { flex: 0 0 auto; }
    .btnrow { display:flex; gap:.5rem; flex-wrap:wrap; margin-top: 1rem; }
    button, a.btn { display:inline-block; padding:.45rem .75rem; font-size:1rem; border:1px solid #000; background:#fff; cursor:pointer; color:inherit; text-decoration:none; white-space:nowrap; }
    .error { margin: 1rem 0; padding: .65rem .75rem; border: 1px solid #000; max-width: 520px; font-size: .96rem; }
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
      <h2>Login</h2>

      <?php if ($error !== ''): ?>
        <div class="error"><?php echo h($error); ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <input type="hidden" name="next" value="<?php echo h($next_raw); ?>" />

        <label>
          Account ID (username or email)
          <input type="text" name="account_id" autocomplete="username" />
        </label>

        <label>Password</label>
        <div class="pwrow">
          <div class="pwcol">
            <input id="password" type="password" name="password" autocomplete="current-password" />
          </div>
          <div class="pwbtn">
            <button type="button" id="togglePw" aria-controls="password" aria-pressed="false">Show</button>
          </div>
        </div>

        <div class="btnrow">
          <button type="submit">Login</button>
          <a class="btn" href="<?php echo h(site_url('/forgot/')); ?>">I forgot my password</a>
          <a class="btn" href="<?php echo h(site_url('/account-create/')); ?>">Create account</a>
        </div>
      </form>
    </section>
  </div>

  <script>
    (function () {
      var pw = document.getElementById('password');
      var btn = document.getElementById('togglePw');
      if (!pw || !btn) return;

      function setVisible(visible) {
        pw.type = visible ? 'text' : 'password';
        btn.textContent = visible ? 'Hide' : 'Show';
        btn.setAttribute('aria-pressed', visible ? 'true' : 'false');
      }

      setTimeout(function () { setVisible(true); }, 0);
      btn.addEventListener('click', function () {
        setVisible(pw.type === 'password');
      });
    })();
  </script>
</body>
</html>

