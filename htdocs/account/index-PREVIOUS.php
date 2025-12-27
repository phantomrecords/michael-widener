<?php
/* FILE: /account/index.php */
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_login('/account/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Account — Michael Widener II</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light only">
  <style>
    :root { --maxw: 820px; }
    html,body{margin:0;padding:0;background:#fff;color:#000;}
    body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"Noto Sans",sans-serif;line-height:1.55;}
    .page{max-width:var(--maxw);margin:2rem auto 3rem;padding:0 1rem;}
    header h1{font-size:1.85rem;margin:0 0 .35rem;font-weight:700;letter-spacing:.2px;}
    .subtitle{margin:0 0 1rem;font-size:1.05rem;}
    .rule{border:0;border-top:1px solid #000;margin:1rem 0;}
    .note{border:1px solid #000;padding:.65rem .75rem;margin:1rem 0 1.25rem;font-size:.96rem;}
    a{color:inherit;text-decoration:underline;}
  </style>
</head>
<body>
  <div class="page">
    <header class="block">
      <h1><a href="/" style="text-decoration:none;color:inherit;">Michael Widener II</a></h1>

      <!-- NAV-START -->
      <?php include __DIR__ . '/../nav/nav.php'; ?>
      <!-- NAV-END -->

      <hr class="rule" />
    </header>

    <div class="note">
      <strong>Account:</strong> You are logged in.
    </div>

    <p><a href="/logout/?next=/">Logout</a></p>
  </div>
</body>
</html>
