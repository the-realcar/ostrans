<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Strona Główna - PPUT Ostrans</title>
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
    .hero { background: var(--card); border:1px solid var(--border); border-radius:16px; padding:40px; box-shadow: 0 8px 24px rgba(0,0,0,.05); display:flex; align-items:center; gap:32px; }
    .hero img { width:100px; height:100px; object-fit:contain; }
    .hero h1 { margin:0; font-size:2rem; line-height:1.3; }
    .hero p { margin:10px 0 0; color: var(--muted); font-size:1.05rem; }
    .cta { display:flex; gap:14px; margin-top:20px; flex-wrap:wrap; }
    .btn { display:inline-block; padding:14px 24px; border-radius:8px; border:none; background: var(--brand-2); color:#fff; font-weight:700; text-decoration:none; font-size:1rem; cursor:pointer; transition:all 0.2s; }
    .btn:hover { background:#0652c3; transform:translateY(-2px); box-shadow: 0 4px 12px rgba(10,74,159,.3); }
    .btn.alt { background:#fff; color:var(--brand-2); border:2px solid var(--brand-2); }
    .btn.alt:hover { background:var(--brand-2); color:#fff; }

    .grid { margin-top:24px; display:grid; grid-template-columns: repeat(auto-fit, minmax(280px,1fr)); gap:20px; }
    .card { background: var(--card); border:1px solid var(--border); border-radius:12px; padding:24px; box-shadow: 0 6px 18px rgba(0,0,0,.04); }
    .card h2 { margin:0 0 12px; font-size:1.4rem; }
    .card h3 { margin:0 0 12px; font-size:1.2rem; }
    .card p { margin:0; color: var(--muted); font-size:1.05rem; line-height:1.6; }

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
    }
  
    /* Dark mode via body[data-theme="dark"] for compatibility with panel's toggle */
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
      <a href="/linia/index.php">Linie</a>
      <a href="/panel/index.php">Panel</a>
    </nav>
  </header>

  <main>
    <section class="hero">
      <img src="https://ostrans.famisska.pl/logo.png" alt="PPUT Ostrans">
      <div>
        <h1>PPUT Ostrans — komunikacja i obsługa</h1>
        <p>Aktualności firmy, informacje o liniach oraz panel pracowników w jednym miejscu.</p>
        <div class="cta">
          <a class="btn" href="/panel/index.php">Panel pracowników</a>
          <a class="btn alt" href="/linia/index.php">Linie</a>
        </div>
      </div>
    </section>

    <!-- Galeria (z witryny głównej) -->
    <section class="card" style="margin-top:16px" aria-label="Galeria">
      <div style="display:flex; gap:8px; overflow:auto; padding:6px 2px">
        <img src="https://ostrans.famisska.pl/wp-content/uploads/2025/05/image-156.png" alt="Galeria Ostrans" style="height:140px; border-radius:8px"/>
        <img src="https://ostrans.famisska.pl/wp-content/uploads/2025/05/image-73.png" alt="Galeria Ostrans" style="height:140px; border-radius:8px"/>
        <img src="https://ostrans.famisska.pl/wp-content/uploads/2025/05/image-97.png" alt="Galeria Ostrans" style="height:140px; border-radius:8px"/>
        <img src="https://ostrans.famisska.pl/wp-content/uploads/2025/05/image-98.png" alt="Galeria Ostrans" style="height:140px; border-radius:8px"/>
        <img src="https://ostrans.famisska.pl/wp-content/uploads/2025/05/image-132.png" alt="Galeria Ostrans" style="height:140px; border-radius:8px"/>
        <img src="https://ostrans.famisska.pl/wp-content/uploads/2025/05/image-139.png" alt="Galeria Ostrans" style="height:140px; border-radius:8px"/>
      </div>
    </section>

    <!-- O nas (z witryny głównej) -->
    <section class="card" style="margin-top:16px">
      <h2 style="margin-top:0">O Nas</h2>
      <p>Ostrans to prywatna firma oferująca usługi transportowe. Operujemy w Ostrołęce i okolicach dla MZK Ostrołęka. Firma została założona 16 października 2022 roku i jest prowadzona do dnia dzisiejszego. Posiadamy w naszej ofercie duży tabor przystosowany do najnowszych standardów. Ponad 90% naszej floty jest niskopodłogowa i gotowa na transport osób niepełnosprawnych. Jesteśmy również jedyną firmą z całodniowymi rozkładami jazdy. Więc na co czekasz? Wypełnij formularz o pracę i się skontaktujemy z Tobą!</p>
      <div class="cta">
        <a class="btn" href="/pracuj.php">Pracuj z nami</a>
        <a class="btn alt" href="/linie.php">Linie</a>
      </div>
    </section>

    <section class="grid" aria-label="Szybkie skróty">
      <div class="card">
        <h3>Pracuj z nami</h3>
        <p>Dołącz do zespołu kierowców i dyspozytorów. Sprawdź wymagania i rekrutację.</p>
      </div>
      <div class="card">
        <h3>Linie i rozkłady</h3>
        <p>Informacje o obsługiwanych liniach i brygadach. Aktualne zmiany i komunikaty.</p>
      </div>
      <div class="card">
        <h3>Kontakt</h3>
        <p>Skontaktuj się z nami w sprawach współpracy i obsługi pasażerów.</p>
      </div>
    </section>
  </main>
  <footer>
    <div class="footer-logo">
      <a href="/"><img src="https://ostrans.famisska.pl/logo.png" alt="Logo PPUT Ostrans"></a>
      <span>PPUT Ostrans</span>
    </div>
    <p>Copyright © <?= date('Y') ?> Ostrans. Wszelkie prawa zastrzeżone</p>
  </footer>

  <script>
    // Prosty toggle motywu na potrzeby strony głównej (współgra z przyciskiem w panelu)
    (function(){
      const btn = document.getElementById('themeToggle');
      const apply = (mode) => {
        document.body.setAttribute('data-theme', mode);
        btn.textContent = mode === 'dark' ? '☀️ Motyw' : '🌙 Motyw';
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
