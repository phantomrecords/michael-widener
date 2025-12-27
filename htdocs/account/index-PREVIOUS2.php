<?php
/* FILE: /account/index.php */
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';

$create_mode = isset($_GET['create']) && $_GET['create'] === '1';

$sent = false;
$error = '';
$ref = '';

// If NOT in create mode, this is a private account area.
if (!$create_mode) {
    require_login('/account/');
}

// Handle Create Account (Request Access) submission
if ($create_mode && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // Basic fields (no database yet)
    $full_name = isset($_POST['full_name']) ? trim((string)$_POST['full_name']) : '';
    $email     = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $reason    = isset($_POST['reason']) ? trim((string)$_POST['reason']) : '';

    // Light validation (keeps it “official”)
    if ($full_name === '' || $email === '' || $reason === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (mb_strlen($reason) < 20) {
        $error = 'Please provide at least 20 characters describing your request.';
    } else {
        // No account creation. No enumeration. No storage (yet).
        // Generate a reference code for the user to cite later.
        $ref = 'REQ-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('Ymd');
        $sent = true;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title><?php echo $create_mode ? 'Create Account' : 'Account'; ?> — Michael Widener II</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light only">

  <style>
    :root { --maxw: 820px; }

    html, body { margin: 0; padding: 0; background: #fff; color: #000; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                   Helvetica, Arial, "Noto Sans", sans-serif;
      line-height: 1.55;
    }
    .page { max-width: var(--maxw); margin: 2rem auto 3rem; padding: 0 1rem; }

    header h1 {
      font-size: 1.85rem;
      margin: 0 0 .35rem 0;
      font-weight: 700;
      letter-spacing: .2px;
    }
    .rule { border: 0; border-top: 1px solid #000; margin: 1rem 0; }

    h2 {
      font-size: 1.12rem;
      margin: 1.5rem 0 .6rem;
      padding-bottom: .25rem;
      border-bottom: 1px solid #000;
    }

    p { margin: .35rem 0 .75rem; }
    a { color: inherit; text-decoration: underline; }

    .box {
      border: 1px solid #000;
      padding: .65rem .75rem;
      margin: 1rem 0 1.25rem;
      font-size: .96rem;
    }

    form {
      border: 1px solid #000;
      padding: 1rem;
      max-width: 560px;
    }

    label {
      display: block;
      margin-top: .75rem;
      font-size: .95rem;
    }

    input[type="text"],
    input[type="email"],
    textarea {
      width: 100%;
      padding: .45rem;
      font-size: 1rem;
      border: 1px solid #000;
      margin-top: .25rem;
      box-sizing: border-box;
      background: #fff;
      color: #000;
    }

    textarea { min-height: 120px; resize: vertical; }

    button {
      margin-top: 1rem;
      padding: .45rem .75rem;
      font-size: 1rem;
      border: 1px solid #000;
      background: #fff;
      cursor: pointer;
    }

    .btnrow {
      margin-top: 1rem;
      display: flex;
      gap: .5rem;
      flex-wrap: wrap;
    }

    .small {
      font-size: .92rem;
      opacity: .95;
    }

    code {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
      font-size: .95em;
    }

    @media print {
      @page { margin: .6in; }
      a[href]:after { content: " (" attr(href) ")"; }
    }
  </style>
</head>

<body>
  <div class="page">

    <header>
      <h1><a href="/" style="text-decoration:none;color:inherit;">Michael Widener II</a></h1>

      <!-- NAV-START -->
      <?php include __DIR__ . '/../nav/nav.php'; ?>
      <!-- NAV-END -->

      <hr class="rule" />
    </header>

    <?php if ($create_mode): ?>

      <h2>Create Account</h2>

      <div class="box">
        <strong>Access is controlled.</strong><br>
        This site contains private materials. To request access, submit the form below.
        If access is granted, you will receive instructions.
      </div>

      <?php if ($error !== ''): ?>
        <div class="box"><strong>Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
      <?php endif; ?>

      <?php if ($sent): ?>
        <div class="box">
          <strong>Request received.</strong><br>
          If your request is approved, you will receive instructions at the address provided.<br><br>
          Reference: <code><?php echo htmlspecialchars($ref, ENT_QUOTES); ?></code>
        </div>

        <p class="small">
          For security, this page does not confirm whether any account exists.
        </p>

        <div class="btnrow">
          <a href="/login/"><button type="button">Return to Login</button></a>
          <a href="/"><button type="button">Home</button></a>
        </div>

      <?php else: ?>

        <form method="post" action="/account/?create=1">
          <label>
            Full Name
            <input type="text" name="full_name" autocomplete="name" required />
          </label>

          <label>
            Email Address
            <input type="email" name="email" autocomplete="email" required />
          </label>

          <label>
            Reason for Access
            <textarea name="reason" required placeholder="Briefly describe why you are requesting access and your relationship to the matter."></textarea>
          </label>

          <div class="btnrow">
            <button type="submit">Submit Request</button>
            <a href="/login/"><button type="button">Back to Login</button></a>
          </div>
        </form>

        <p class="small" style="margin-top: .9rem;">
          Note: This form does not create an account automatically. It submits an access request for review.
        </p>

      <?php endif; ?>

    <?php else: ?>

      <h2>My Account</h2>

      <div class="box">
        <strong>Status:</strong> Logged in<br>
        <span class="small">This is the private account area. You can extend this page later with profile settings.</span>
      </div>

      <ul>
        <li><a href="/legal/">Legal Complaints & Evidence</a></li>
        <li><a href="/help/">Help (Private)</a></li>
        <li><a href="/logout/?next=/">Logout</a></li>
      </ul>

    <?php endif; ?>

  </div>
</body>
</html>
