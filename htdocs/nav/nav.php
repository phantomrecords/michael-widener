<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';

$logged_in = is_logged_in();

$next = $_SERVER['REQUEST_URI'] ?? '/';
if (!is_string($next) || $next === '' || $next[0] !== '/') {
    $next = '/';
}

$auth_href = $logged_in
    ? '/logout/?next=' . rawurlencode($next)
    : '/login/?next=' . rawurlencode($next);

$auth_text = $logged_in ? 'Logout' : 'Login';
?>

<p class="subtitle">
  <a target="_parent" href="/">&uarr;</a> |
  <a href="<?php echo htmlspecialchars(nav_back_href('/'), ENT_QUOTES); ?>">←</a> |
  <a class="nav-home" href="/" aria-label="Home" title="Home">
  <i class="fa fa-home" aria-hidden="true"></i></a> <!-- <a href="/">Home</a> --> | |
  <a href="<?php echo htmlspecialchars(nav_forward_href('/'), ENT_QUOTES); ?>">→</a> |
  <a href="/help/">?</a> |
  <a href="/account/">╭ ACCOUNT ╮</a> |
  <a href="<?php echo htmlspecialchars($auth_href, ENT_QUOTES); ?>">
    <?php echo htmlspecialchars($auth_text, ENT_QUOTES); ?>
  </a>
</p>
