<?php
declare(strict_types=1);

$diag = (isset($_GET['diag']) && (string)$_GET['diag'] === '1');

require_once __DIR__ . '/auth.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }

// Paths
$nav_path = __DIR__ . '/nav/nav.php';

// Auth state
$logged_in = function_exists('is_logged_in') ? is_logged_in() : false;

// For Login form (same behavior as /login/)
$error = '';
$next_raw = get_next_path('/');

// If already logged in, no need to POST-login here.
if (!$logged_in && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $account_id = isset($_POST['account_id']) ? trim((string)$_POST['account_id']) : '';
    $password   = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($account_id === '' || $password === '') {
        $error = 'Account ID and password are required.';
    } else {
        if (auth_check_credentials($account_id, $password)) {

login_user($account_id);
redirect(get_next_path('/'));


/* BEGIN login_user change */
            login_user($account_id);                 // <-- Updated this login_user function
            redirect(get_next_path('/')); // This redirect was already correct. I made no change except this comment text
/* END login_user change */

/* BEGIN-PREVIOUS login_user change */
/*            login_user();                 // <-- do NOT redirect inside login_user() */
/*            redirect(get_next_path('/')); // return to requested page (or /) */
/* END-PREVIOUS login_user change */

        } else {
            $error = 'Invalid login credentials.';
        }
    }
}

// Simple “Retail” cards (replace these with your real product URLs/titles later)
$products = [
    [
        'title' => 'Most Popular',
        'price' => 'Visit store',
        'href'  => 'https://djx.vegas/retail-store/ols/products?page=1&sortOption=descend_by_popularity',
    ],
    [
        'title' => 'Digital Downloads',
        'price' => 'Visit store',
        'href'  => 'https://djx.vegas/retail-store/ols/categories/downloadable-graphics',
    ],
    [
        'title' => 'Game Mat-shaped Downloadable Graphic',
        'price' => 'Visit store',
        'href'  => 'https://djx.vegas/retail-store/ols/products/downloadable-stormy-titans-graphic-second-version',
    ],
    [
        'title' => 'MousePad-shaped Downloadable Graphic',
        'price' => 'Visit store',
        'href'  => 'https://djx.vegas/retail-store/ols/products/downloadable-stormy-titans-mouse-pad-graphic',
    ],
    [
        'title' => 'Original, Solid-wood Drink Coasters',
        'price' => 'Visit store',
        'href'  => 'https://djx.vegas/retail-store/ols/products/stormy-titans-drink-coaster-set',
    ],
    [
        'title' => 'Original, Solid-wood Mousepad',
        'price' => 'Visit store',
        'href'  => 'https://djx.vegas/retail-store/ols/products/wooden-stormy-titans-mouse-pad',
    ],
];

$sites_designed = [
  [
    'title' => 'Michael Widener GP',
    'price' => 'Live site',
    'href'  => 'https://michaelwidener.com/',
    'thumb' => 'Retail Website Designed in 2025',
  ],
  [
    'title' => 'DJX.Vegas',
    'price' => 'Live site',
    'href'  => 'https://djx.vegas/',
    'thumb' => 'Retail Website Designed in 2020. Launched in 2024.',
  ],
  [
    'title' => 'Game Scape Interactive',
    'price' => 'Live site',
    'href'  => 'https://game-scape.us/',
    'thumb' => 'Retail Website Designed in 1997. Re-launched in 2025',
  ],
  [
    'title' => 'Dream-Singles.us (in progress)',
    'price' => 'Private & Now In R&D Phase (login-only)',
    'href'  => 'https://dream-singles.us/',
    'thumb' => 'Dating Website Being Designed in 2025',
  ],
  [
    'title' => 'Unnamed Site',
    'price' => 'Private & Now In R&D Phase (login-only)',
    'href'  => 'javascript:history.go(0);',
    'thumb' => '2015 Website Design by Contract',
  ],
  [
    'title' => 'Phantom Records DBA',
    'price' => 'Private & Now In Repair Following Cyber Hacking (login-only)',
    'href'  => 'https://phantomrecords.com/',
    'thumb' => 'Corporate Website Designed in 2004. Has Been In Remediation Since 2020 Pandemic',
  ],

];

$wp_modules = [
  [
    'title' => 'Plugin / Module: (placeholder name)',
    'price' => 'Private notes + install info',
    'href'  => '/account/',   // or your internal page when you create it
    'thumb' => 'WP',
  ],
  [
    'title' => 'Module: (placeholder name)',
    'price' => 'Private notes + versioning',
    'href'  => '/account/',
    'thumb' => 'WP',
  ],
];




?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Michael Widener GP</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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



    html, body {
      margin: 0;
      padding: 0;
      background: #fff;
      color: #000;
    }

    .subtitle a.nav-home{
      font-size: 1.15em;
      line-height: 1;
      position: relative;
      top: .02em; /* tiny optical nudge */
    }

    /* Prevent scrollbar width shifts between pages */
    html { scrollbar-gutter: stable; }

    html, body { margin:0; padding:0; background:#fff; color:#000; }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                   Helvetica, Arial, "Noto Sans", sans-serif;
      line-height: 1.55;
    }

    .page { max-width: var(--maxw); margin: 2rem auto 3rem; padding: 0 1rem; }

    .retail-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.25rem;
    }

    @media (max-width: 640px) {
    .retail-grid {
      grid-template-columns: 1fr;
      }
    }

/* Match other pages (no up-scaling on the home icon) */
.subtitle a.nav-home,
.nav-home .fa{
  font-size: 1em !important;
  top: 0 !important;
}


    .subtitle a.nav-home{
      font-size: 1.15em;
      line-height: 1;
      position: relative;
      top: .02em; /* tiny optical nudge */
    }

    .nav-home{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      line-height:1;
      text-decoration: underline;            /* keep link look */
      text-underline-offset: .12em;          /* match your nav spacing */
    }

    .nav-home .fa{
      font-size: 1.15em;                     /* tweak taller */
      line-height:1;
      position: relative;
      top: .02em;                            /* tiny baseline nudge */
    }



    header h1 {
      font-size: 1.85rem;
      margin: 0 0 .35rem 0;
      font-weight: 700;
      letter-spacing: .2px;
    }

    .subtitle { margin: 0 0 1rem; font-size: 1.05rem; }

    .rule { border:0; border-top:1px solid #000; margin: 1rem 0; }

    h2 {
      font-size: 1.12rem;
      margin: 1.5rem 0 .6rem;
      padding-bottom: .25rem;
      border-bottom: 1px solid #000;
    }

    p { margin: .35rem 0 .75rem; }

    .note {
      border: 1px solid #000;
      padding: .65rem .75rem;
      margin: 1rem 0 1.25rem;
      font-size: .96rem;
    }

    footer {
      margin-top: 2rem;
      border-top: 1px solid #000;
      padding-top: 1rem;
      font-size: .92rem;
      text-align: center;
    }

    a { color: inherit; text-decoration: underline; }

    .diag {
      border: 1px dashed #000;
      padding: .65rem .75rem;
      margin: 1rem 0 1.25rem;
      font-size: .94rem;
      white-space: pre-wrap;
    }

    /* ===== Login form (Home page) ===== */

    form {
      border: 1px solid #000;
      padding: 1rem;
      max-width: 520px;
    }

    label {
      display: block;
      margin-top: .75rem;
      font-size: .95rem;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: .45rem;
      font-size: 1rem;
      border: 1px solid #000;
      margin-top: .25rem;
      box-sizing: border-box;
    }

    .pwrow {
      display: flex;
      gap: .5rem;
      align-items: flex-end;
    }

    .pwrow .pwcol { flex: 1; }
    .pwrow .pwbtn { flex: 0 0 auto; }

    .btnrow {
      display: flex;
      gap: .5rem;
      flex-wrap: wrap;
      margin-top: 1rem;
    }

    button,
    a.btn {
      display: inline-block;
      padding: .45rem .75rem;
      font-size: 1rem;
      border: 1px solid #000;
      background: #fff;
      cursor: pointer;
      color: inherit;
      text-decoration: none;
      white-space: nowrap;
    }

    .error {
      margin: 1rem 0;
      padding: .65rem .75rem;
      border: 1px solid #000;
      max-width: 520px;
      font-size: .96rem;
    }

    /* ===== Retail grid (DJX.Vegas “panel shape” cue) ===== */

    .products {
      margin-top: .25rem;
    }

    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1rem;
      margin: .75rem 0 0;
    }

    .card {
      border: 1px solid #000;
      padding: .75rem;
      text-decoration: none;
      color: inherit;
      display: block;
    }

    .thumb {
      border: 1px solid #000;
      aspect-ratio: 1 / 1;     /* square image area like the store panels */
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: .65rem;
      font-size: .95rem;
    }

.thumb img {
  max-width: 100%;
  max-height: 100%;
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

    .card h3 {
      margin: 0 0 .15rem 0;
      font-size: 1.02rem;
      font-weight: 400;        /* keep it non-dramatic */
    }

    .price {
      margin: 0;
      font-size: .95rem;
    }

    .muted {
      font-size: .92rem;
      margin-top: .35rem;
    }
  </style>
</head>

<body>
  <div class="page">

    <header>
      <h1>Michael Widener GP</h1>

      <!-- NAV -->
      <?php
      if (is_file($nav_path) && is_readable($nav_path)) {
          include $nav_path;
      } else {
          $next = $_SERVER['REQUEST_URI'] ?? '/';
          $auth_href = $logged_in
            ? '/logout/?next=' . rawurlencode($next)
            : '/login/?next=' . rawurlencode($next);

          echo '<p class="subtitle">'
             . '<a href="/">&uarr;</a> | '
             . '<a href="/">Home</a> | '
             . '<a href="/help/">?</a> | '
             . '<a href="/account/">╭ ACCOUNT ╮</a> | '
             . '<a href="' . h($auth_href) . '">' . ($logged_in ? 'Logout' : 'Login') . '</a>'
             . '</p>';
      }
      ?>

      <hr class="rule" />
    </header>

    <section>
      <h2>Home</h2>

      <?php if ($logged_in): ?>
        <p>Welcome. Your account is authenticated.</p>
        <p class="muted">These sections include Private Sections only available after login.</p>
      <?php else: ?>

        <?php if ($error !== ''): ?>
          <div class="error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="post" action="">
          <input type="hidden" name="next" value="<?php echo h($next_raw); ?>" />

          <label>
            Account ID (can be username or email address)
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
            <a class="btn" href="/iforgot/">I forgot my password</a>
            <a class="btn" href="/account-create/">Create account</a>
          </div>
        </form>
      <?php endif; ?>
    </section>

    <section class="products">
      <h2>Retail</h2>

      <p class="muted">
        Retail items hosted on <a target="_parent" href="https://djx.vegas/">DJX.Vegas</a>, displayed here using a consistent visual structure.
      </p>

      <div class="product-grid">
        <?php foreach ($products as $p): ?>
          <a class="card" href="<?php echo h((string)$p['href']); ?>" target="_self" rel="noopener">

<div class="thumb">
  <?php if ($p['title'] === 'Most Popular'): ?>
    <img
      src="/images/products/Story-Titans-Store-Category-Sketch-Most-Popular-Items-1024x1024.png"
      alt="Most popular items – lifestyle sketch of shopper selecting sports and outdoor gear"
      loading="lazy"
    />

  <?php elseif ($p['title'] === 'Digital Downloads'): ?>
    <img
      src="/images/products/Story-Titans-Store-Category-Sketch-Digital-Downloads-Category-1024x1024.png"
      alt="Digital downloads category."
      loading="lazy"
    />

  <?php elseif ($p['title'] === 'Game Mat-shaped Downloadable Graphic'): ?>
    <img
      src="/images/products/stormy-titans-gamemat-lifestyle-sketch-square.png"
      alt="Stormy Titans card game mat in use."
      loading="lazy"
    />

  <?php elseif ($p['title'] === 'MousePad-shaped Downloadable Graphic'): ?>
    <img
      src="/images/products/sketch-of-stormy-titans-mousepad-lifestyle-image-square-1024x1024.png"
      alt="Stormy Titans see-through wrist rest with clear padding."
      loading="lazy"
    />

  <?php elseif ($p['title'] === 'MousePad-shaped Downloadable Graphic'): ?>
    <img
      src="/images/products/sketch-of-stormy-titans-mousepad-lifestyle-image-square-1024x1024.png"
      alt="Stormy Titans see-through wrist rest with clear padding."
      loading="lazy"
    />

  <?php elseif ($p['title'] === 'Original, Solid-wood Mousepad'): ?>
    <img
      src="/images/products/Mouse-Pad-Solid-Wood-Lifestyle-Image-Sketch-1024x1024.png"
      alt="Stormy Titans Soid-wood Rectangular Mouse Pad."
      loading="lazy"
    />

  <?php elseif ($p['title'] === 'Original, Solid-wood Drink Coasters'): ?>
    <img
      src="/images/products/Drink-Coast-Set-of-Four-Solid-Wood-1024x1024.png"
      alt="Stormy Titans Soid-wood Rectangular Mouse Pad."
      loading="lazy"
    />



  <?php else: ?>
    DJX.Vegas
  <?php endif; ?>
</div>


            <h3><?php echo h((string)$p['title']); ?></h3>
            <p class="price"><?php echo h((string)$p['price']); ?></p>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

<?php if ($logged_in): ?>

  <section class="products">
    <h2>Websites I Designed (or am Designing Now)</h2>

    <p class="muted">
      Private index of production sites and works-in-progress (visible only after login).
    </p>

    <div class="product-grid">
      <?php foreach ($sites_designed as $s): ?>
        <a class="card" href="<?php echo h((string)$s['href']); ?>" target="_self" rel="noopener">
          <div class="thumb"><?php echo h((string)($s['thumb'] ?? 'Website')); ?></div>
          <h3><?php echo h((string)$s['title']); ?></h3>
          <p class="price"><?php echo h((string)$s['price']); ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="products">
    <h2>WordPress Plugins &amp; Modules</h2>

    <p class="muted">
      Private build notes, release history, and documentation for plugins/modules I maintain (visible only after login).
    </p>

    <div class="product-grid">
      <?php foreach ($wp_modules as $m): ?>
        <a class="card" href="<?php echo h((string)$m['href']); ?>" target="_self" rel="noopener">
          <div class="thumb"><?php echo h((string)($m['thumb'] ?? 'WP')); ?></div>
          <h3><?php echo h((string)$m['title']); ?></h3>
          <p class="price"><?php echo h((string)$m['price']); ?></p>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

<?php endif; ?>

<?php if ($logged_in): ?>

  <section class="products">
    <h2>Complaints</h2>
    <p class="muted">
      Private chronicles of formal complaints, timelines, evidence links, and outcomes — written for accuracy and future reference, epecially to suypport verbal recall and to address verbal accusations of fact not recalled accurately.
    </p>

    <div class="product-grid">
      <a class="card" href="/complaints/" rel="noopener">
        <div class="thumb">PRIVATE</div>
        <h3>Complaints Dashboard</h3>
        <p class="price">Banks • Agencies • Case packets</p>
      </a>
    </div>
  </section>

  <section class="products">
    <h2>Bullies</h2>
    <p class="muted">
      Private accountability notes about betrayals and intimidation — documented for use in Court.
    </p>

    <div class="product-grid">
      <a class="card" href="/bullies/" rel="noopener">
        <div class="thumb">PRIVATE</div>
        <h3>Bullies Index</h3>
        <p class="price">Names • incidents • boundaries</p>
      </a>
    </div>
  </section>

<?php endif; ?>


    <?php if ($diag): ?>
      <div class="diag"><?php
        echo "DIAG MODE: enabled\n";
        echo "RUNNING: " . __FILE__ . "\n";
        echo "PHP: " . PHP_VERSION . "\n\n";
        echo "NAV FILE:\n";
        echo " - path: {$nav_path}\n";
        echo " - exists: " . (file_exists($nav_path) ? "YES" : "NO") . "\n";
        echo " - readable: " . (is_readable($nav_path) ? "YES" : "NO") . "\n\n";
        echo "AUTH:\n";
        echo " - auth.php: " . (__DIR__ . "/auth.php") . "\n";
        echo " - is_logged_in(): " . ($logged_in ? "YES" : "NO") . "\n";
      ?></div>
    <?php endif; ?>

    <footer>
      <p>&copy; <?php echo date('Y'); ?> Michael Widener II</p>
    </footer>

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

      // Keep password field type=password for autofill, then reveal after paint.
      setTimeout(function () { setVisible(true); }, 0);

      btn.addEventListener('click', function () {
        setVisible(pw.type === 'password');
      });
    })();
  </script>
</body>
</html>
