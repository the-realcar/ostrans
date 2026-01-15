<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Miasta - PPUT Ostrans</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Quicksand&family=Oswald&family=Doto&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #ffffff;
      --text: #333;
      --muted: #666;
      --card-bg: #f5f5f5;
      --border: #ddd;
      --brand: #003366;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: Quicksand, sans-serif;
      background-color: var(--bg);
      color: var(--text);
      transition: background-color 0.3s, color 0.3s;
    }

    header {
      background-color: var(--brand);
      color: white;
      padding: 20px;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
    }

    .logo-title {
      display: flex;
      align-items: center;
      gap: 15px;
      font-size: 1.5rem;
      font-weight: 700;
    }

    .logo-title img {
      height: 50px;
    }

    nav {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    nav a {
      text-decoration: none;
      color: white;
      font-weight: bold;
      padding: 10px 16px;
      border-radius: 6px;
      background: rgba(255,255,255,.15);
      border: 1px solid rgba(255,255,255,.25);
      transition: all 0.2s;
    }

    nav a:hover {
      background: rgba(255,255,255,.25);
    }

    #themeToggle {
      background: rgba(255,255,255,.15);
      color: white;
      border: 1px solid rgba(255,255,255,.25);
      border-radius: 6px;
      padding: 10px 16px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
    }

    #themeToggle:hover {
      background: rgba(255,255,255,.25);
    }

    main {
      padding: 40px 20px;
      max-width: 1200px;
      margin: auto;
    }

    h1 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 2rem;
    }

    .gallery-wrapper {
      position: relative;
      width: 100%;
      overflow: hidden;
      border-radius: 10px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      margin-bottom: 30px;
    }

    .gallery-track {
      display: flex;
      transition: transform 0.5s ease-in-out;
    }

    .gallery-image {
      min-width: 100%;
      height: 500px;
      object-fit: cover;
    }

    .arrow-button {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background-color: rgba(0, 0, 0, 0.5);
      color: white;
      border: none;
      font-size: 2rem;
      cursor: pointer;
      padding: 10px 15px;
      z-index: 2;
      border-radius: 4px;
      transition: background-color 0.2s;
    }

    .arrow-button:hover {
      background-color: rgba(0, 0, 0, 0.7);
    }

    .arrow-button.left {
      left: 10px;
    }

    .arrow-button.right {
      right: 10px;
    }

    .dots {
      display: flex;
      justify-content: center;
      margin-top: 15px;
      gap: 8px;
    }

    .dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background-color: #aaa;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .dot.active {
      background-color: var(--brand);
    }

    .legend {
      background: var(--card-bg);
      border-radius: 8px;
      padding: 1.5rem;
      max-width: 600px;
      margin: auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border: 1px solid var(--border);
    }

    .legend div {
      display: flex;
      align-items: center;
      margin-bottom: 12px;
      padding: 8px;
      border-radius: 4px;
      transition: background-color 0.2s;
    }

    .legend div:hover {
      background-color: rgba(0,0,0,0.05);
    }

    .legend-color {
      width: 24px;
      height: 24px;
      margin-right: 10px;
      border-radius: 4px;
      border: 1px solid #ccc;
      flex-shrink: 0;
    }

    section {
      background-color: var(--card-bg);
      padding: 40px 20px;
      margin-top: 60px;
      border-radius: 8px;
      border: 1px solid var(--border);
    }

    section h2 {
      text-align: center;
      margin-bottom: 20px;
      font-size: 1.5rem;
    }

    section p {
      max-width: 800px;
      margin: auto;
      font-size: 1rem;
      line-height: 1.6;
      color: var(--muted);
    }

    footer {
      background-color: var(--brand);
      color: white;
      padding: 40px 20px;
      text-align: center;
      margin-top: 40px;
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

    /* Dark theme */
    body[data-theme="dark"] {
      --bg: #0b1220;
      --text: #e5e7eb;
      --muted: #9ca3af;
      --card-bg: #0f172a;
      --border: #1f2937;
    }

    body[data-theme="dark"] .arrow-button {
      background-color: rgba(255, 255, 255, 0.2);
    }

    body[data-theme="dark"] .arrow-button:hover {
      background-color: rgba(255, 255, 255, 0.3);
    }

    body[data-theme="dark"] .legend div:hover {
      background-color: rgba(255,255,255,0.1);
    }

    /* Responsywność */
    @media (max-width: 768px) {
      header {
        flex-direction: column;
        align-items: flex-start;
      }

      nav {
        width: 100%;
        flex-wrap: wrap;
      }

      .gallery-image {
        height: 250px;
      }

      h1 {
        font-size: 1.5rem;
      }

      section {
        padding: 20px 15px;
      }
    }

    @media (max-width: 480px) {
      .gallery-image {
        height: 180px;
      }

      .arrow-button {
        font-size: 1.5rem;
        padding: 8px 12px;
      }

      .legend {
        padding: 1rem;
      }

      h1 {
        font-size: 1.3rem;
      }
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
    <h1>Legenda miast</h1>

    <div class="legend">
      <div><div class="legend-color" style="background:#e6194b;"></div>Ostrołęka</div>
      <div><div class="legend-color" style="background:#3cb44b;"></div>Dębica</div>
      <div><div class="legend-color" style="background:#ffe119;"></div>Tyniec</div>
      <div><div class="legend-color" style="background:#4363d8;"></div>Skawce</div>
      <div><div class="legend-color" style="background:#f58231;"></div>Kamionka</div>
      <div><div class="legend-color" style="background:#911eb4;"></div>Sławków</div>
      <div><div class="legend-color" style="background:#46f0f0;"></div>Dąbrowa Górnicza</div>
      <div><div class="legend-color" style="background:#f032e6;"></div>Zagórzany</div>
      <div><div class="legend-color" style="background:#bcf60c;"></div>Harbutowice</div>
      <div><div class="legend-color" style="background:#fabebe;"></div>Mucharz</div>
      <div><div class="legend-color" style="background:#008080;"></div>Bolęcin</div>
      <div><div class="legend-color" style="background:#e6beff;"></div>Kęty</div>
      <div><div class="legend-color" style="background:#9a6324;"></div>Pogórze</div>
      <div><div class="legend-color" style="background:#fffac8;"></div>Hażlach</div>
      <div><div class="legend-color" style="background:#800000;"></div>Boguszowice</div>
      <div><div class="legend-color" style="background:#aaffc3;"></div>Piwoda</div>
      <div><div class="legend-color" style="background:#808000;"></div>Zagórze</div>
      <div><div class="legend-color" style="background:#ffd8b1;"></div>Regulice</div>
      <div><div class="legend-color" style="background:#000075;"></div>Radłów</div>
      <div><div class="legend-color" style="background:#808080;"></div>Lwówek Śląski</div>
      <div><div class="legend-color" style="background:#a9a9a9;"></div>Młynka</div>
      <div><div class="legend-color" style="background:#f4a460;"></div>Lanckorona</div>
      <div><div class="legend-color" style="background:#4682b4;"></div>Okleśna</div>
      <div><div class="legend-color" style="background:#00ff7f;"></div>Alwernia</div>
      <div><div class="legend-color" style="background:#dc143c;"></div>Kobierzyce</div>
      <div><div class="legend-color" style="background:#b8860b;"></div>Biskupice Podgórne</div>
      <div><div class="legend-color" style="background:#2e8b57;"></div>Wadowice</div>
      <div><div class="legend-color" style="background:#8b4513;"></div>Jabłonków</div>
      <div><div class="legend-color" style="background:#ff1493;"></div>Jaworzynka</div>
      <div><div class="legend-color" style="background:#1e90ff;"></div>Jablunkov</div>
      <div><div class="legend-color" style="background:#20b2aa;"></div>Sterkowiec</div>
      <div><div class="legend-color" style="background:#cd5c5c;"></div>Pilce</div>
      <div><div class="legend-color" style="background:#dda0dd;"></div>Szczepanowice</div>
      <div><div class="legend-color" style="background:#778899;"></div>Piekary</div>
      <div><div class="legend-color" style="background:#7fffd4;"></div>Olkusz</div>
    </div>
  </main>

  <footer>
    <div class="footer-logo">
      <a href="/"><img src="https://ostrans.famisska.pl/logo.png" alt="Logo PPUT Ostrans"></a>
      <span>PPUT Ostrans</span>
    </div>
    <p>Copyright © <?= date('Y') ?> Ostrans. Wszelkie prawa zastrzeżone</p>
  </footer>

  <script>
    // Theme toggle
    (function(){
      const btn = document.getElementById('themeToggle');
      const apply = (mode) => {
        document.body.setAttribute('data-theme', mode);
        btn.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
        btn.textContent = mode === 'dark' ? '☀️ Motyw' : '🌙 Motyw';
        try { localStorage.setItem('theme', mode); } catch(e){}
      };
      let initial = 'light';
      try { initial = localStorage.getItem('theme') || initial; } catch(e){}
      apply(initial);
      btn.addEventListener('click', () => apply(document.body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'));
    })();
  </script>
</body>
</html>
