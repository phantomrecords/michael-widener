<?php
// /accounts/review/index.php — Super-only: pending account request queue
declare(strict_types=1);

require_once __DIR__ . '/../../auth.php';
require_super('/accounts/review/');

$diag = (isset($_GET['diag']) && (string)$_GET['diag'] === '1');

// Storage directory (filesystem)
$pending_dir = __DIR__ . '/../../_pending_account_requests';

// Collect pending records
$items = [];
$error = '';

try {
    if (!is_dir($pending_dir)) {
        // Create if missing (optional). Comment out if you prefer manual creation.
        @mkdir($pending_dir, 0700, true);
    }

    if (!is_dir($pending_dir) || !is_readable($pending_dir)) {
        throw new RuntimeException('Pending directory is missing or not readable: ' . $pending_dir);
    }

    $files = glob($pending_dir . '/*.json') ?: [];
    foreach ($files as $path) {
        if (!is_file($path) || !is_readable($path)) continue;

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') continue;

        $rec = json_decode($raw, true);
        if (!is_array($rec)) continue;

        $token = isset($rec['token']) && is_string($rec['token']) ? $rec['token'] : basename($path, '.json');
        $created_at = isset($rec['created_at']) && is_string($rec['created_at']) ? $rec['created_at'] : '';
        $alg = '';
        if (
            isset($rec['encrypted']) && is_array($rec['encrypted']) &&
            isset($rec['encrypted']['alg']) && is_string($rec['encrypted']['alg'])
        ) {
            $alg = $rec['encrypted']['alg'];
        }

        $items[] = [
            'token' => $token,
            'created_at' => $created_at,
            'alg' => $alg,
            'path' => $path,
        ];
    }

    // Sort newest first by created_at if available, else by token
    usort($items, function(array $a, array $b): int {
        $ta = (string)($a['created_at'] ?? '');
        $tb = (string)($b['created_at'] ?? '');
        if ($ta !== '' && $tb !== '') {
            return strcmp($tb, $ta); // desc
        }
        return strcmp((string)($b['token'] ?? ''), (string)($a['token'] ?? ''));
    });

} catch (Throwable $e) {
    $error = $e->getMessage();
}

// Helpers
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Accounts Review — Michael Widener GP</title>
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

    html, body { margin:0; padding:0; background:#fff; color:#000; }
    body{
      font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"Noto Sans",sans-serif;
      line-height:1.55;
    }

    .page{max-width:var(--maxw);margin:2rem auto 3rem;padding:0 1rem;}
    header h1{font-size:1.85rem;margin:0 0 .35rem 0;font-weight:700;letter-spacing:.2px;}
    .subtitle{margin:0 0 1rem;font-size:1.05rem;}
    .rule{border:0;border-top:1px solid #000;margin:1rem 0;}

    h2{font-size:1.12rem;margin:1.5rem 0 .6rem;padding-bottom:.25rem;border-bottom:1px solid #000;}
    p{margin:.35rem 0 .75rem;}
    a{color:inherit;text-decoration:underline;}

    .note{border:1px solid #000;padding:.65rem .75rem;margin:1rem 0 1.25rem;font-size:.96rem;}
    .error{border:1px solid #000;padding:.65rem .75rem;margin:1rem 0 1.25rem;font-size:.96rem;}

    table{
      width:100%;
      border-collapse:collapse;
      margin: .75rem 0 1.25rem;
      font-size: .98rem;
    }
    th, td{
      border:1px solid #000;
      padding:.5rem .6rem;
      vertical-align:top;
      text-align:left;
    }
    th{font-weight:700;}
    .mono{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;}
    .small{font-size:.92rem;}
    .btn{
      display:inline-block;
      padding:.35rem .6rem;
      border:1px solid #000;
      text-decoration:none;
      margin-right:.35rem;
    }

    footer{margin-top:2rem;border-top:1px solid #000;padding-top:1rem;font-size:.92rem;text-align:center;}
    @media print { @page{margin:.6in;} a[href]:after{content:" (" attr(href) ")";} }
  </style>
</head>

<body>
  <div class="page">

    <header>
      <h1>Michael Widener GP</h1>
      <!-- NAV-START -->
      <?php include __DIR__ . '/../../nav/nav.php'; ?>
      <!-- NAV-END -->
      <hr class="rule" />
    </header>

    <h2>Accounts Review</h2>

    <div class="note">
      Role required: <strong>Super</strong>. This page lists pending account requests (encrypted-at-rest).
    </div>

    <?php if ($error !== ''): ?>
      <div class="error">
        <strong>Queue Error:</strong> <?php echo h($error); ?>
      </div>
    <?php endif; ?>

    <?php if (count($items) === 0): ?>
      <p>No pending requests found.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th style="width:34%;">Reference</th>
            <th style="width:26%;">Created</th>
            <th style="width:18%;">Encryption</th>
            <th style="width:22%;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <?php
              $token = (string)($it['token'] ?? '');
              $created = (string)($it['created_at'] ?? '');
              $alg = (string)($it['alg'] ?? '');
              $approve_href = '/accounts/approve/?token=' . rawurlencode($token);
            ?>
            <tr>
              <td class="mono"><?php echo h($token); ?></td>
              <td class="small"><?php echo $created !== '' ? h($created) : '(unknown)'; ?></td>
              <td class="small"><?php echo $alg !== '' ? h($alg) : '(unknown)'; ?></td>
              <td>
                <a class="btn" href="<?php echo h($approve_href); ?>">Approve</a>
                <?php if ($diag): ?>
                  <div class="small mono" style="margin-top:.35rem;">
                    <?php echo h(basename((string)($it['path'] ?? ''))); ?>
                  </div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <?php if ($diag): ?>
      <div class="note">
        <div class="mono small">
Pending dir: <?php echo h($pending_dir); ?>

Count: <?php echo (string)count($items); ?>
        </div>
      </div>
    <?php endif; ?>

    <p>
      <a href="/account/">Return to Account</a> |
      <a href="/">Home</a>
    </p>

    <footer>
      <p>&copy; <?php echo date('Y'); ?> Michael Widener GP</p>
    </footer>

  </div>
</body>
</html>
