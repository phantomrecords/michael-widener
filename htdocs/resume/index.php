<?php
// ---------------------------------------------------------------
// /resume/index.php
// Landing + access control for resumes & portfolio
// Uses shared site auth (auth.php)
// ---------------------------------------------------------------
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';

$error = '';

// Check login status
$loggedIn = is_logged_in();

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim((string)($_POST['username'] ?? ''));
    $p = (string)($_POST['password'] ?? '');

    if ($u !== '' && $p !== '' && auth_check_credentials($u, $p)) {
        login_user($u);
        redirect('/resume/');
    } else {
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
    <div class="card">
      <h1>Resumes &amp; Professional Portfolio</h1>
      <p>
        This area contains private resumes and supporting documentation for
        recruiters, collaborators, and legal professionals invited to review
        my work. A valid login is required for full access.
      </p>
      <p>
        You may still view a static public snapshot here:
        <br>
        <a href="<?php echo htmlspecialchars(site_url('/resume/public-index.html'), ENT_QUOTES); ?>">Open public resume overview</a>
      </p>
    </div>

    <div class="card">
      <?php if ($loggedIn): ?>
        <h2>Private Resume Links</h2>
        <p>You are logged in. The following sections are available:</p>

        <ul>
          <li><a href="<?php echo htmlspecialchars(site_url('/resume/digital-analytics/'), ENT_QUOTES); ?>">Digital Analytics Resume</a></li>
          <li><a href="<?php echo htmlspecialchars(site_url('/resume/web-developer/'), ENT_QUOTES); ?>">Web Developer Resume</a></li>
          <li><a href="<?php echo htmlspecialchars(site_url('/resume/Michael_Widener_Resume_Evidence_Technician.pdf'), ENT_QUOTES); ?>">Evidence Technician Resume (PDF)</a></li>
          <li><a href="<?php echo htmlspecialchars(site_url('/resume/Shelter-Computer-Lab-Tech.html'), ENT_QUOTES); ?>">Shelter Lab Tech Resume (HTML)</a></li>
          <li><a href="<?php echo htmlspecialchars(site_url('/portfolio/'), ENT_QUOTES); ?>">Full Portfolio</a></li>
          <li><a href="<?php echo htmlspecialchars(site_url('/legal/'), ENT_QUOTES); ?>">Legal Workspace</a></li>
        </ul>

        <p class="meta">
          To end this session for the whole site, use:
          <br>
          <a href="<?php echo htmlspecialchars(site_url('/logout/?next=/resume/'), ENT_QUOTES); ?>">Logout</a>
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
