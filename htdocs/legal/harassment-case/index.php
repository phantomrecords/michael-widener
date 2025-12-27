<?php
// index.php – login for /legal/harassment-case/
declare(strict_types=1);

session_start();
require __DIR__ . '/config.php';

// Initialize failed attempts counter
if (!isset($_SESSION['hc_failed_attempts'])) {
    $_SESSION['hc_failed_attempts'] = 0;
}

$error = '';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Very basic rate limiting
    if ($_SESSION['hc_failed_attempts'] >= HC_MAX_FAILED_ATTEMPTS) {
        // Small delay to slow brute-force attempts
        sleep(2);
    }

    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    if ($u === HC_USERNAME && password_verify($p, HC_PASSWORD_HASH)) {
        // Successful login
        $_SESSION['hc_failed_attempts'] = 0;
        session_regenerate_id(true);
        $_SESSION['hc_logged_in'] = true;

        // Redirect to the /home/ folder (clean URL)
        header('Location: ./home/');
        exit;
    } else {
        // Failed login
        $_SESSION['hc_failed_attempts']++;
        $error = 'Invalid username or password.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Harassment Case – Secure Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root {
      color-scheme: light dark;
    }
    * {
      box-sizing: border-box;
    }

/* Prevent scrollbar width shifts between pages */
html {
  overflow-y: scroll;          /* always reserve scrollbar space */
  scrollbar-gutter: stable;    /* modern browsers: keep layout stable */
}


/* Prevent padding/border width drift */
*, *::before, *::after { box-sizing: border-box; }

    body {
      margin: 0;
      padding: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      line-height: 1.5;
      background: #f5f5f5;
      color: #222;
    }
    @media (prefers-color-scheme: dark) {
      body {
        background: #111;
        color: #eee;
      }
    }
    .page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem 1rem;
    }
    .card {
      width: 100%;
      max-width: 420px;
      background: #fff;
      border-radius: 12px;
      padding: 1.5rem 1.75rem 1.75rem;
      border: 1px solid #ddd;
      box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }
    @media (prefers-color-scheme: dark) {
      .card {
        background: #181818;
        border-color: #333;
        box-shadow: 0 8px 24px rgba(0,0,0,0.5);
      }
    }
    h1 {
      font-size: 1.4rem;
      margin: 0 0 0.25rem;
      font-weight: 600;
    }
    p.lead {
      font-size: 0.9rem;
      margin: 0 0 1.25rem;
      color: #555;
    }
    @media (prefers-color-scheme: dark) {
      p.lead {
        color: #ccc;
      }
    }
    form {
      margin-top: 0.5rem;
    }
    label {
      display: block;
      font-size: 0.85rem;
      margin-bottom: 0.25rem;
    }
    .field {
      width: 100%;
      display: block;
      margin: 0 0 0.85rem 0; /* even left & right margins via width:100% + box-sizing */
      padding: 0.55rem 0.75rem;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 0.9rem;
      background: #fff;
      color: inherit;
    }
    @media (prefers-color-scheme: dark) {
      .field {
        background: #111;
        border-color: #444;
        color: #eee;
      }
    }
    .field:focus {
      outline: none;
      border-color: #0070f3;
      box-shadow: 0 0 0 1px rgba(0,112,243,0.5);
    }
    .button-row {
      margin-top: 0.5rem;
    }
    button[type="submit"] {
      display: inline-block;
      padding: 0.55rem 1.4rem;
      border-radius: 999px;
      border: none;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      background: #0070f3;
      color: #fff;
    }
    button[type="submit"]:hover {
      background: #005ad1;
    }
    @media (prefers-color-scheme: dark) {
      button[type="submit"] {
        background: #1e6bff;
      }
      button[type="submit"]:hover {
        background: #1753c2;
      }
    }
    .error {
      margin-bottom: 0.8rem;
      padding: 0.5rem 0.75rem;
      border-radius: 8px;
      font-size: 0.85rem;
      background: #ffe6e6;
      color: #b00020;
      border: 1px solid #f5b5b5;
    }
    @media (prefers-color-scheme: dark) {
      .error {
        background: #3a1116;
        border-color: #662029;
        color: #ffb3c1;
      }
    }
    .meta {
      margin-top: 1.1rem;
      font-size: 0.78rem;
      color: #777;
    }
    @media (prefers-color-scheme: dark) {
      .meta {
        color: #999;
      }
    }
  </style>
</head>
<body>
  <div class="page">
    <div class="card">
      <h1>Harassment Case – Login</h1>
      <p class="lead">
        This area is restricted to the owner of this domain and authorized users.
        A valid username and password are required to continue.
      </p>

      <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <label for="username">Username</label>
        <input
          type="text"
          name="username"
          id="username"
          class="field"
          autocomplete="username"
          required
        >

        <label for="password">Password</label>
        <input
          type="password"
          name="password"
          id="password"
          class="field"
          autocomplete="current-password"
          required
        >

        <div class="button-row">
          <button type="submit">Sign in</button>
        </div>
      </form>

      <div class="meta">
        Sessions are private to this browser. If you are not the authorized user,
        close this page immediately.
      </div>
    </div>
  </div>
</body>
</html>
