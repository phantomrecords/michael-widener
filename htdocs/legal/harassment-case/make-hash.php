<?php
// make_hash.php — one-time helper to generate a bcrypt hash

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($password === '' || $confirm === '') {
        $msg = "Both fields are required.";
    } elseif ($password !== $confirm) {
        $msg = "Passwords do not match.";
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $msg  = "Hash generated successfully. Copy this into HC_PASSWORD_HASH:<br><code>" .
                htmlspecialchars($hash, ENT_QUOTES, 'UTF-8') . "</code>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Generate Password Hash</title>
</head>
<body>
  <h1>Generate Password Hash</h1>
  <?php if (!empty($msg)): ?>
    <p><?php echo $msg; ?></p>
  <?php endif; ?>
  <form method="post">
    <p>
      <label>Password:<br>
        <input type="password" name="password" required>
      </label>
    </p>
    <p>
      <label>Confirm Password:<br>
        <input type="password" name="confirm" required>
      </label>
    </p>
    <p><button type="submit">Generate Hash</button></p>
  </form>
</body>
</html>
