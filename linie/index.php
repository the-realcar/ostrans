<?php
// Dynamic line detail page - handles any /linie/XXX-YY.php route
// Extract line number and variant from URL
$path = $_SERVER['REQUEST_URI'];
preg_match('#/linie/([^/]+)\.php#', $path, $matches);
$slug = $matches[1] ?? '';

// Parse slug (format: lineNum-variant, e.g., 107-01 or A-01)
$parts = explode('-', $slug, 2);
$lineNum = $parts[0] ?? '';
$variantId = $parts[1] ?? '01';

if (!$lineNum) {
    http_response_code(404);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>404</title></head><body><h1>404 - Linia nie znaleziona</h1><p><a href="/linie.php">Powrót do listy linii</a></p></body></html>';
    exit;
}

// Fetch line data from SIL API
$apiUrl = "https://sil.kanbeq.me/ostrans/api/lines";
$linesData = @file_get_contents($apiUrl);
$allLines = $linesData ? json_decode($linesData, true) : [];

// Find all variants for this line number
$lineVariants = array_filter($allLines, function($l) use ($lineNum) {
    return ($l['line'] ?? '') === $lineNum;
});

if (empty($lineVariants)) {
    http_response_code(404);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>404</title></head><body><h1>404 - Linia nie znaleziona</h1><p><a href="/linie.php">Powrót do listy linii</a></p></body></html>';
    exit;
}

// Find the requested variant
$currentVariant = null;
foreach ($lineVariants as $v) {
    if (($v['variant'] ?? '') === $variantId) {
        $currentVariant = $v;
        break;
    }
}

// Fallback to first variant if requested not found
if (!$currentVariant) {
    $currentVariant = reset($lineVariants);
    $variantId = $currentVariant['variant'] ?? '01';
}

$lineType = $currentVariant['type'] ?? 'bus';
$from = $currentVariant['from'] ?? '';
$to = $currentVariant['to'] ?? '';

// Fetch stops for this variant
$stopsApiUrl = "https://sil.kanbeq.me/ostrans/api/lines/" . urlencode($lineNum) . "/" . urlencode($variantId) . "/stops";
$stopsData = @file_get_contents($stopsApiUrl);
$stops = $stopsData ? json_decode($stopsData, true) : [];

$typeLabels = [
    'tram' => 'Tramwaj',
    'trol' => 'Trolejbus',
    'bus' => 'Autobus'
];
$typeLabel = $typeLabels[$lineType] ?? 'Linia';

$pageTitle = "$typeLabel $lineNum — PPUT Ostrans";
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand&family=Oswald&family=Doto&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f7f9fc;
      --text: #111827;
      --muted: #6b7280;
      --brand: #003366;
      --brand-2: #0a4a9f;
      --card: #ffffff;
      --border: #e5e7eb;
      --focus: #ffbf47;
      --tram: #d32f2f;
      --trol: #1976d2;
      --bus: #388e3c;
    }
    * { box-sizing: border-box; }
    html, body { margin:0; padding:0; font-family: Quicksand, Arial, sans-serif; color: var(--text); background: var(--bg); }
    a { color: var(--brand-2); text-decoration: none; }
    a:hover { text-decoration: underline; }
    header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 18px; background: var(--brand); color:#fff; }
    .logo-title { display:flex; align-items:center; gap:12px; font-weight:700; }
    header img { height:42px; display:block; }
    nav a { color:#dbeafe; margin-left:14px; font-weight:600; }
    nav a:hover { color:#fff; }
    #themeToggle { background:#fff; color:#111; border:1px solid rgba(0,0,0,.08); border-radius:8px; padding:8px 12px; cursor:pointer; }

    main { max-width:900px; margin:26px auto; padding:0 16px; }
    .breadcrumb { font-size:0.9rem; color: var(--muted); margin-bottom:12px; }
    .breadcrumb a { color: var(--brand-2); }
    
    .line-header { background: var(--card); border:1px solid var(--border); border-radius:12px; padding:18px; margin-bottom:18px; box-shadow: 0 4px 12px rgba(0,0,0,.05); }
    .line-title { display:flex; align-items:center; gap:14px; margin-bottom:8px; }
    .line-badge-large { display:inline-flex; align-items:center; justify-content:center; min-width:60px; padding:12px 16px; border-radius:10px; font-weight:700; font-size:1.4rem; color:#fff; }
    .line-badge-large.tram { background: var(--tram); }
    .line-badge-large.trol { background: var(--trol); }
    .line-badge-large.bus { background: var(--bus); }
    .line-title h1 { margin:0; font-size:1.6rem; }
    .line-route { font-size:1.1rem; color: var(--muted); margin:6px 0; }
    .line-route strong { color: var(--text); }

    .variants { margin:12px 0; display:flex; gap:8px; flex-wrap:wrap; }
    .variant-btn { padding:8px 12px; border-radius:8px; border:1px solid var(--border); background:#fff; color: var(--text); cursor:pointer; text-decoration:none; font-size:0.95rem; }
    .variant-btn.active { background: var(--brand); color:#fff; font-weight:700; }
    .variant-btn:hover { background: var(--brand-2); color:#fff; text-decoration:none; }

    .stops-section { background: var(--card); border:1px solid var(--border); border-radius:12px; padding:18px; box-shadow: 0 4px 12px rgba(0,0,0,.05); }
    .stops-section h2 { margin:0 0 16px; font-size:1.3rem; }
    .stops-list { list-style:none; margin:0; padding:0; }
    .stop-item { padding:12px 14px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:10px; }
    .stop-item:last-child { border-bottom:none; }
    .stop-num { min-width:32px; font-weight:700; color: var(--muted); font-size:0.9rem; }
    .stop-name { flex:1; font-size:1rem; }

    footer { margin-top:26px; background:#fff; border-top:1px solid var(--border); }
    .footer-inner { max-width:900px; margin:0 auto; padding:16px; color: var(--muted); display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; }

    @media (max-width:600px) {
      header { flex-direction:column; align-items:flex-start; gap:8px; }
      nav { display:flex; flex-wrap:wrap; gap:8px; }
      nav a { margin-left:0; }
      .line-title { flex-direction:column; align-items:flex-start; }
    }
  
    body[data-theme="dark"] {
      --bg:#0b1220; --text:#e5e7eb; --muted:#9ca3af; --card:#0f172a; --border:#1f2937;
    }
  </style>
</head>
<body>

  <header>
    <div class="logo-title">
      <a href="/"><img src="https://ostrans.famisska.pl/logo.png" alt="Logo PPUT Ostrans"></a>
      <span>PPUT Ostrans</span>
    </div>
    <button id="themeToggle" type="button" aria-label="Przełącz motyw" aria-pressed="false">Motyw</button>
    <nav>
      <a href="/">Strona główna</a>
      <a href="/linie.php">Linie</a>
      <a href="/panel/index.php">Panel pracowników</a>
    </nav>
  </header>

  <main>
    <div class="breadcrumb">
      <a href="/">Strona główna</a> / <a href="/linie.php">Linie</a> / <?= htmlspecialchars("$typeLabel $lineNum") ?>
    </div>

    <div class="line-header">
      <div class="line-title">
        <span class="line-badge-large <?= htmlspecialchars($lineType) ?>">
          <?= htmlspecialchars($lineNum) ?>
        </span>
        <div>
          <h1><?= htmlspecialchars($typeLabel) ?></h1>
          <div class="line-route">
            <strong><?= htmlspecialchars($from) ?></strong> → <strong><?= htmlspecialchars($to) ?></strong>
          </div>
        </div>
      </div>

      <?php if (count($lineVariants) > 1): ?>
      <div class="variants">
        <span style="color:var(--muted);font-size:0.9rem">Warianty:</span>
        <?php foreach ($lineVariants as $v): 
          $vId = $v['variant'] ?? '01';
          $vFrom = $v['from'] ?? '';
          $vTo = $v['to'] ?? '';
          $vUrl = "/linie/" . urlencode($lineNum) . "-" . urlencode($vId) . ".php";
          $isActive = ($vId === $variantId);
        ?>
        <a href="<?= htmlspecialchars($vUrl) ?>" class="variant-btn <?= $isActive ? 'active' : '' ?>" title="<?= htmlspecialchars("$vFrom → $vTo") ?>">
          <?= htmlspecialchars($vId) ?>: <?= htmlspecialchars($vFrom) ?> → <?= htmlspecialchars($vTo) ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="stops-section">
      <h2>Przystanki (wariant <?= htmlspecialchars($variantId) ?>)</h2>
      <?php if (!empty($stops)): ?>
      <ul class="stops-list">
        <?php foreach ($stops as $idx => $stop): 
          $stopName = is_array($stop) ? ($stop['name'] ?? $stop['stop'] ?? '') : $stop;
        ?>
        <li class="stop-item">
          <span class="stop-num"><?= $idx + 1 ?>.</span>
          <span class="stop-name"><?= htmlspecialchars($stopName) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?>
      <p style="color:var(--muted)">Brak danych o przystankach dla tego wariantu.</p>
      <?php endif; ?>
    </div>
  </main>

  <footer>
    <div class="footer-inner">
      <span>© <?=date('Y')?> PPUT Ostrans</span>
      <span><a href="https://ostrans.famisska.pl/polityka-prywatnosci">Polityka prywatności</a></span>
    </div>
  </footer>

  <script>
    (function(){
      const btn = document.getElementById('themeToggle');
      const apply = (mode) => {
        document.body.setAttribute('data-theme', mode);
        try { localStorage.setItem('theme', mode); } catch(e){}
        btn && btn.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
      };
      let initial = 'light';
      try { initial = localStorage.getItem('theme') || initial; } catch(e){}
      apply(initial);
      btn && btn.addEventListener('click', () => apply(document.body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'));
    })();
  </script>
</body>
</html>
