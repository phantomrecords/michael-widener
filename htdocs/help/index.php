<?php

require_once __DIR__ . '/../auth.php';
require_role('Owner','Investigator','Attorney','Police Officer','Sheriff','Security Guard');

require_login('/help/');

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Help — Michael Widener GP</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light only">
  <style>
    :root { --maxw: 820px; }
    html { overflow-y: scroll; scrollbar-gutter: stable; }
    *, *::before, *::after { box-sizing: border-box; }
    html, body { margin:0; padding:0; background:#fff; color:#000; }
    body { font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"Noto Sans",sans-serif; line-height:1.55; }
    .page { max-width:var(--maxw); margin:2rem auto 3rem; padding:0 1rem; }
    header h1 { font-size:1.85rem; margin:0 0 .35rem 0; font-weight:700; letter-spacing:.2px; }
    .subtitle { margin:0 0 1rem; font-size:1.05rem; }
    .rule { border:0; border-top:1px solid #000; margin:1rem 0; }
    h2 { font-size:1.12rem; margin:1.5rem 0 .6rem; padding-bottom:.25rem; border-bottom:1px solid #000; }
    p { margin:.35rem 0 .75rem; }
    a { color: inherit; text-decoration: underline; }
    .note { border:1px solid #000; padding:.65rem .75rem; margin:1rem 0 1.25rem; font-size:.96rem; }
    ul { margin:.5rem 0 1rem 1.25rem; }
    code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
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
      <h2>Help</h2>

      <div class="note">
        This is a private help page for authenticated users.
      </div>

      <h2>Login</h2>
      <ul>
        <li>If you are prompted to login, use the “Account ID” and password configured on the server.</li>
        <li>If you are developing locally, set env vars <code>HC_LOGIN_USERNAME</code> and <code>HC_LOGIN_PASSWORD_HASH</code>.</li>
      </ul>

      <h2>Password reset</h2>
      <p>
        The <a href="<?php echo htmlspecialchars(site_url('/forgot/'), ENT_QUOTES); ?>">Forgot Password</a>
        screen generates a new password hash to paste into server config.
      </p>

      <h2>Dev vs Prod paths</h2>
      <p>
        This site supports both running at <code>/</code> (production) and <code>/htdocs/</code> (development mirror).
        Internal links are generated with <code>site_url()</code> so they stay correct in both.
      </p>
    </section>
  </div>
</body>
</html>
