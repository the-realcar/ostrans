<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Linie - PPUT Ostrans</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
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

    main { max-width:1100px; margin:26px auto; padding:0 16px; }
    h1 { margin:0 0 16px; font-size:1.8rem; }
    .legend { display:flex; gap:14px; margin-bottom:18px; font-size:0.9rem; }
    .legend-item { display:flex; align-items:center; gap:6px; }
    .legend-dot { width:16px; height:16px; border-radius:50%; }
    .legend-dot.tram { background: var(--tram); }
    .legend-dot.trol { background: var(--trol); }
    .legend-dot.bus { background: var(--bus); }

    .line-group { margin-bottom:24px; }
    .line-group h2 { font-size:1.2rem; margin:0 0 10px; color: var(--muted); }
    .line-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(54px,1fr)); gap:8px; }
    .line-badge { display:flex; align-items:center; justify-content:center; padding:10px 6px; border-radius:8px; font-weight:700; text-align:center; border:2px solid transparent; cursor:pointer; transition: all 0.15s; }
    .line-badge:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.15); text-decoration:none; }
    .line-badge.tram { background: var(--tram); color:#fff; }
    .line-badge.trol { background: var(--trol); color:#fff; }
    .line-badge.bus { background: var(--bus); color:#fff; }

    footer { margin-top:26px; background:#fff; border-top:1px solid var(--border); }
    .footer-inner { max-width:1100px; margin:0 auto; padding:16px; color: var(--muted); display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; }

    @media (max-width:600px) {
      header { flex-direction:column; align-items:flex-start; gap:8px; }
      nav { display:flex; flex-wrap:wrap; gap:8px; }
      nav a { margin-left:0; }
      .line-grid { grid-template-columns: repeat(auto-fill, minmax(48px,1fr)); }
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
      <span>PPUT Ostrans — Linie</span>
    </div>
    <button id="themeToggle" type="button" aria-label="Przełącz motyw" aria-pressed="false" style="margin-left:12px">Motyw</button>
    <nav>
      <a href="/">Strona główna</a>
      <a href="/linie.php">Linie</a>
      <a href="/panel/index.php">Panel pracowników</a>
    </nav>
  </header>

  <main>
    <h1>Rozkład jazdy — wszystkie linie</h1>
    
    <div class="legend">
      <div class="legend-item"><span class="legend-dot tram"></span> Tramwaj</div>
      <div class="legend-item"><span class="legend-dot trol"></span> Trolejbus</div>
      <div class="legend-item"><span class="legend-dot bus"></span> Autobus</div>
    </div>

    <?php
    // Fetch lines from SIL API
    $apiUrl = 'https://sil.kanbeq.me/ostrans/api/lines';
    $linesData = @file_get_contents($apiUrl);
    $lines = $linesData ? json_decode($linesData, true) : [];

    // Group by type
    $grouped = ['tram' => [], 'trol' => [], 'bus' => []];
    foreach ($lines as $line) {
      $num = $line['line'] ?? '';
      $type = $line['type'] ?? 'bus';
      if (!isset($grouped[$type][$num])) {
        $grouped[$type][$num] = [];
      }
      $grouped[$type][$num][] = $line;
    }

    // Sort numerically
    foreach ($grouped as $t => &$arr) {
      uksort($arr, function($a, $b) {
        // Extract leading numbers for proper numeric sort
        preg_match('/^(\d+)/', $a, $aNum);
        preg_match('/^(\d+)/', $b, $bNum);
        if (!empty($aNum) && !empty($bNum)) {
          return (int)$aNum[1] <=> (int)$bNum[1];
        }
        return strnatcasecmp($a, $b);
      });
    }

    $typeLabels = [
      'tram' => 'Tramwaj',
      'trol' => 'Trolejbus',
      'bus' => 'Autobus'
    ];

    foreach ($grouped as $type => $linesInType):
      if (empty($linesInType)) continue;
    ?>
    <div class="line-group">
      <h2><?= htmlspecialchars($typeLabels[$type]) ?></h2>
      <div class="line-grid">
        <?php foreach ($linesInType as $lineNum => $variants): 
          // Use first variant to get base info
          $firstVariant = $variants[0];
          $variantId = $firstVariant['variant'] ?? '01';
          $url = "/linie/" . urlencode($lineNum) . "-" . urlencode($variantId) . ".php";
        ?>
        <a href="<?= htmlspecialchars($url) ?>" class="line-badge <?= htmlspecialchars($type) ?>">
          <?= htmlspecialchars($lineNum) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
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
