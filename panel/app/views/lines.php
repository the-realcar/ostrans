<?php
/** @var array $grouped */
/** @var array $typeLabels */
/** @var string $year */
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Linie - PPUT Ostrans</title>
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
    header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:20px 24px; background: var(--brand); color:#fff; }
    .logo-title { display:flex; align-items:center; gap:12px; font-weight:700; font-size:1.3rem; }
    header img { height:50px; display:block; }
    nav { display:flex; gap:12px; align-items:center; }
    nav a { display:inline-block; background:rgba(255,255,255,.15); color:#fff; padding:10px 16px; border-radius:6px; font-weight:600; text-decoration:none; border:1px solid rgba(255,255,255,.25); transition:all 0.2s; }
    nav a:hover { background:rgba(255,255,255,.25); color:#fff; }
    #themeToggle { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.25); border-radius:6px; padding:10px 16px; cursor:pointer; font-weight:600; transition:all 0.2s; }
    #themeToggle:hover { background:rgba(255,255,255,.25); }

    main { max-width:1200px; margin:32px auto; padding:0 20px; }
    h1 { margin:0 0 20px; font-size:2rem; }
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

    footer {
      background-color: var(--brand);
      color: white;
      padding: 40px 20px;
      text-align: center;
      margin-top: 60px;
    }
    .footer-logo {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
      font-size: 2rem;
      margin-bottom: 10px;
    }
    .footer-logo img {
      height: 80px;
    }

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
      <span>PPUT Ostrans</span>
    </div>
    <button id="themeToggle" type="button" aria-label="Przełącz motyw" aria-pressed="false">🌙 Motyw</button>
    <nav>
      <a href="/pracuj.php">Pracuj z nami</a>
      <a href="/linie.php">Linie</a>
      <a href="/panel/index.php">Panel</a>
    </nav>
  </header>

  <main>
    <h1>Rozkład jazdy — wszystkie linie</h1>
    
    <div class="legend">
      <div class="legend-item"><span class="legend-dot tram"></span> Tramwaj</div>
      <div class="legend-item"><span class="legend-dot trol"></span> Trolejbus</div>
      <div class="legend-item"><span class="legend-dot bus"></span> Autobus</div>
    </div>

    <?php if (empty($grouped['tram']) && empty($grouped['trol']) && empty($grouped['bus'])): ?>
      <p style="color:var(--muted)">Brak danych linii (API/Baza niedostępne?). Spróbuj ponownie później.</p>
    <?php else: ?>
      <div style="background:#e3f2fd; border-left:4px solid #1976d2; padding:12px 16px; margin-bottom:16px; border-radius:4px; color:#0d47a1; font-size:0.9rem;">
        <strong>ℹ️ Dane z SIL API:</strong> Linie są pobierane z głównego źródła danych. Jeśli SIL byłby niedostępny, system korzysta z lokalnej bazy danych lub próbek testowych.
      </div>
    <?php endif; ?>

    <?php foreach ($grouped as $type => $linesInType): 
      if (empty($linesInType)) continue;
      $label = $typeLabels[$type] ?? ucfirst($type);
    ?>
    <div class="line-group">
      <h2><?= htmlspecialchars($label) ?></h2>
      <div class="line-grid">
        <?php foreach ($linesInType as $lineNum => $variants): 
          // Use first variant to generate link (no variants in DB, defaults to 01)
          $variantId = $variants[0]['variant'] ?? '01';
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
    <div class="footer-logo">
      <a href="/"><img src="https://ostrans.famisska.pl/logo.png" alt="Logo PPUT Ostrans"></a>
      <span>PPUT Ostrans</span>
    </div>
    <p>Copyright © <?= htmlspecialchars($year) ?> Ostrans. Wszelkie prawa zastrzeżone</p>
  </footer>

  <script>
    (function(){
      const btn = document.getElementById('themeToggle');
      const apply = (mode) => {
        document.body.setAttribute('data-theme', mode);
        btn.textContent = mode === 'dark' ? '\u2600\ufe0f Motyw' : '\ud83c\udf19 Motyw';
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
