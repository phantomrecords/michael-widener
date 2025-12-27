<?php
/* FILE: /legal/index.php */
declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_role('Owner','Investigator','Attorney','Accountant','Police Officer','Sheriff','Security Guard');

// Require site-wide login for all legal materials
require_login('/legal/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Legal Complaints & Evidence — Michael Widener II</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light only">

  <!-- Matches resume typography & black/white layout -->
  <style>
    :root { --maxw: 820px; }

/* Prevent scrollbar width shifts between pages */
html {
  overflow-y: scroll;          /* always reserve scrollbar space */
  scrollbar-gutter: stable;    /* modern browsers: keep layout stable */
}


/* Prevent padding/border width drift */
*, *::before, *::after { box-sizing: border-box; }

    html, body { margin: 0; padding: 0; background: #fff; color: #000; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto,
                   Helvetica, Arial, "Noto Sans", sans-serif;
      line-height: 1.55;
    }
    .page {
      max-width: var(--maxw);
      margin: 2rem auto 3rem;
      padding: 0 1rem;
    }
    header h1 {
      font-size: 1.85rem;
      margin: 0 0 .35rem 0;
      font-weight: 700;
      letter-spacing: .2px;
    }
    .subtitle {
      margin: 0 0 1rem;
      font-size: 1.05rem;
    }
    .rule {
      border: 0;
      border-top: 1px solid #000;
      margin: 1rem 0;
    }
    h2 {
      font-size: 1.12rem;
      margin: 1.5rem 0 .6rem;
      padding-bottom: .25rem;
      border-bottom: 1px solid #000;
    }
    h3 {
      margin: .5rem 0;
      font-size: 1.02rem;
    }
    p { margin: .35rem 0 .75rem; }
    a { color: inherit; text-decoration: underline; }

    .note {
      border: 1px solid #000;
      padding: .65rem .75rem;
      margin: 1rem 0 1.25rem;
      font-size: .96rem;
    }

    /* Complaint tile layout */
    .tiles {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-top: 1rem;
    }
    .tile {
      border: 1px solid #000;
      flex: 1 1 calc(50% - 1rem);
      padding: 1rem;
      transition: background .2s, color .2s;
      text-decoration: none;
    }
    .tile:hover {
      background: #000;
      color: #fff;
    }
    .tile p {
      margin: .3rem 0;
      font-size: .95rem;
    }
    .badge-row {
      margin-top: .3rem;
      font-size: .82rem;
      opacity: .9;
    }
    .badge {
      border: 1px solid currentColor;
      border-radius: 2px;
      padding: 0 .25rem;
      margin-right: .4rem;
      display: inline-block;
    }

    @media print {
      @page { margin: .6in; }
      a[href]:after { content: " (" attr(href) ")"; }
      .tile { display: block; margin-bottom: 1rem; }
    }
  </style>
</head>

<body>
  <div class="page">

    <header class="block">
      <h1><a href="<?php echo htmlspecialchars(site_url('/'), ENT_QUOTES); ?>" style="text-decoration:none;color:inherit;">Michael Widener II</a></h1>

      <!-- NAV-START -->
      <?php include __DIR__ . '/../nav/nav.php'; ?>
      <!-- NAV-END -->

      <hr class="rule" />
    </header>

    <div class="note">
      <strong>Purpose:</strong> This private directory organizes formal complaints and
      their supporting evidence. Each complaint is structured as a folder (not a file),
      allowing investigators and attorneys to download materials directly.
    </div>

    <section>
      <h2>Complaints</h2>
      <p>Select a complaint below to open its dedicated evidence folder.</p>

      <div class="tiles">

        <!-- Example complaint -->
        <a class="tile" href="./us-bank-ssdi-freeze/">
          <h3>US Bank — SSDI Account Freeze</h3>
          <p>
            U.S. Bank froze and began closing my SSDI-funded account after I self-reported
            an online employment scam and provided complete evidence.
          </p>
          <div class="badge-row">
            <span class="badge">Federal</span>
            <span class="badge">OCC</span>
            <span class="badge">Origin: IC3</span>
            <span class="badge">Status: Draft</span>
          </div>
        </a>

        <!-- Duplicate this block for additional complaints -->

      </div>
    </section>

    <section>
      <h2>Evidence Structure</h2>
      <p>Each complaint folder may include:</p>
      <ul>
        <li><code>./ic3/</code> — FBI Internet Crime Complaint Center submissions</li>
        <li><code>./occ/</code> — Office of the Comptroller of the Currency filings</li>
        <li><code>./banks/</code> — Bank notices, statements, correspondence</li>
        <li><code>./local/</code> — Police reports, CAD logs, surveillance requests</li>
      </ul>
      <p>
        Evidence remains adjacent to the complaint narrative to preserve context
        and chain-of-custody clarity.
      </p>
    </section>

  </div>
</body>
</html>
