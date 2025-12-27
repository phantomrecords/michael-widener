<?php
// ---------------------------------------------------------------
// /resume/index.php
// Landing + access control for all resume/portfolio pages
// Reuses the same login from /legal/harassment-case/config.php
// Includes a safe config loader with explicit error output
// ---------------------------------------------------------------
declare(strict_types=1);

session_start();

// Resolve config.php path safely
$configPath = __DIR__ . '/../legal/harassment-case/config.php';

// If config is missing, show a clear error instead of a 500
if (!is_file($configPath)) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "ERROR: Config file not found at:\n";
    echo "  {$configPath}\n\n";
    echo "Please verify that:\n";
    echo "  - This file exists (config.php)\n";
    echo "  - It is located in /legal/harassment-case/\n";
    echo "  - The path from /resume/index.php to it is ../legal/harassment-case/config.php\n";
    exit;
}

// Load shared login constants (HC_USERNAME, HC_PASSWORD_HASH, etc.)
require $configPath;

$error = '';

// Are we already logged in?
$loggedIn = !empty($_SESSION['hc_logged_in']) && $_SESSION['hc_logged_in'] === true;

// Handle login POST from this page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    if ($u === HC_USERNAME && password_verify($p, HC_PASSWORD_HASH)) {
        $_SESSION['hc_failed_attempts'] = 0;
        session_regenerate_id(true);
        $_SESSION['hc_logged_in'] = true;

        // Reload this page so the private section appears
        header('Location: /resume/');
        exit;
    } else {
        $_SESSION['hc_failed_attempts'] = ($_SESSION['hc_failed_attempts'] ?? 0) + 1;
        $error = 'Invalid username or password.';
        $loggedIn = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Resumes &amp; Professional Portfolio – Michael Widener II</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    :root { color-scheme: light dark; }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 1.5rem 1rem 3rem;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      line-height: 1.6;
      background: #f5f5f5;
      color: #222;
    }
    @media (prefers-color-scheme: dark) {
      body { background: #111; color: #eee; }
    }
    .page { max-width: 880px; margin: 0 auto; }
    .card {
      background: #fff;
      border-radius: 12px;
      border: 1px solid #ccc;
      padding: 1.4rem 1.6rem;
      box-shadow: 0 8px 24px rgba(0,0,0,0.06);
      margin-bottom: 1.4rem;
    }
    @media (prefers-color-scheme: dark) {
      .card {
        background: #181818;
        border-color: #333;
        box-shadow: 0 8px 24px rgba(0,0,0,0.5);
      }
    }
    h1 {
      margin: 0 0 0.5rem;
      font-size: 1.7rem;
      font-weight: 650;
    }
    h2 {
      margin: 0 0 0.5rem;
      font-size: 1.2rem;
    }
    p { margin: 0 0 0.7rem; font-size: 0.95rem; }
    ul { margin: 0.25rem 0 0.7rem 1.2rem; padding: 0; font-size: 0.95rem; }
    a { color: #0070f3; text-decoration: none; }
    a:hover { text-decoration: underline; }
    label {
      display: block;
      font-size: 0.85rem;
      margin-bottom: 0.25rem;
    }
    .field {
      width: 100%;
      margin: 0 0 0.85rem 0;
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
        border-color: #555;
        color: #eee;
      }
    }
    .field:focus {
      outline: none;
      border-color: #0070f3;
      box-shadow: 0 0 0 1px rgba(0,112,243,0.5);
    }
    button[type="submit"] {
      padding: 0.55rem 1.4rem;
      border-radius: 999px;
      border: none;
      background: #0070f3;
      color: #fff;
      cursor: pointer;
      font-size: 0.9rem;
      font-weight: 600;
    }
    button[type="submit"]:hover { background: #005ad1; }
    @media (prefers-color-scheme: dark) {
      button[type="submit"] { background: #1e6bff; }
      button[type="submit"]:hover { background: #1753c2; }
    }
    .error {
      background: #ffe6e6;
      color: #b00020;
      padding: 0.55rem 0.75rem;
      border-radius: 8px;
      margin-bottom: 0.9rem;
      border: 1px solid #f5b5b5;
      font-size: 0.85rem;
    }
    @media (prefers-color-scheme: dark) {
      .error {
        background: #3a1116;
        border-color: #662029;
        color: #ffb3c1;
      }
    }
    .meta {
      font-size: 0.8rem;
      color: #777;
      margin-top: 0.5rem;
    }
    @media (prefers-color-scheme: dark) {
      .meta { color: #aaa; }
    }
  </style>
</head>
<body>
  <div class="page">

    <!-- INTRO CARD -->
    <div class="card">
      <h1>Resumes &amp; Professional Portfolio</h1>
      <p>
        This area contains private resumes and supporting documentation for
        recruiters, collaborators, and legal professionals invited to review
        my work. A valid login is required for full access.
      </p>

      <p>
        You may still view a static public snapshot here:<br>
        <a href="/resume/public-index.html">Open public resume overview</a>
      </p>
    </div>

    <!-- ACCESS AREA -->
    <div class="card">
      <?php if ($loggedIn): ?>
        <h2>Private Resume Links</h2>
        <p>You are logged in. The following sections are available:</p>

        <ul>
          <li><a href="/resume/digital-analytics/">Digital Analytics Resume</a></li>
          <li><a href="/resume/web-developer/">Web Developer Resume</a></li>
          <li><a href="/resume/evidence-tech/">Evidence Technician Resume</a></li>
          <li><a href="/resume/shelter-lab/">Shelter Lab Tech Resume</a></li>
          <li><a href="/portfolio/">Full Portfolio</a></li>
          <li><a href="/legal/">Legal Workspace</a></li>
        </ul>

        <p class="meta">
          To end this session, use the logout link in the legal workspace
          (harassment-case dashboard) or close your browser.
        </p>

      <?php else: ?>

        <h2>Sign In for Full Access</h2>

        <?php if (!empty($error)): ?>
          <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" action="">
          <label for="username">Username</label>
          <input
            type="text"
            id="username"
            name="username"
            class="field"
            autocomplete="username"
            required
          >

          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            class="field"
            autocomplete="current-password"
            required
          >

          <button type="submit">Sign In</button>
        </form>

        <p class="meta">
          Credentials are issued directly by the site owner. If you were
          invited to review my materials, please use the username and password
          I provided.
        </p>

      <?php endif; ?>
    </div>

  </div>
</body>
</html>
