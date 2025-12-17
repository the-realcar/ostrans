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
    header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 18px; background: var(--brand); color:#fff; }
    .logo-title { display:flex; align-items:center; gap:12px; font-weight:700; }
    header img { height:42px; display:block; }
    nav a { color:#dbeafe; margin-left:14px; font-weight:600; }
    nav a:hover { color:#fff; }
    #themeToggle { background:#fff; color:#111; border:1px solid rgba(0,0,0,.08); border-radius:8px; padding:8px 12px; cursor:pointer; }

    main { max-width:1100px; margin:26px auto; padding:0 16px; }
    .hero { background: var(--card); border:1px solid var(--border); border-radius:12px; padding:22px; box-shadow: 0 8px 24px rgba(0,0,0,.05); display:flex; align-items:center; gap:18px; }
    .hero img { width:72px; height:72px; object-fit:contain; }
    .hero h1 { margin:0; font-size:1.6rem; line-height:1.2; }
    .hero p { margin:6px 0 0; color: var(--muted); }
    .cta { display:flex; gap:10px; margin-top:14px; }
    .btn { display:inline-block; padding:10px 14px; border-radius:8px; border:1px solid var(--border); background: var(--brand-2); color:#fff; font-weight:700; text-decoration:none; }
    .btn.alt { background:#fff; color:var(--brand-2); }

    .grid { margin-top:18px; display:grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap:14px; }
    .card { background: var(--card); border:1px solid var(--border); border-radius:10px; padding:14px; box-shadow: 0 6px 18px rgba(0,0,0,.04); }
    .card h3 { margin:0 0 8px; font-size:1.05rem; }
    .card p { margin:0; color: var(--muted); }

    footer { margin-top:26px; background:#fff; border-top:1px solid var(--border); }
    .footer-inner { max-width:1100px; margin:0 auto; padding:16px; color: var(--muted); display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; }

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
      <a href="https://ostrans.famisska.pl"><img src="https://ostrans.famisska.pl/logo.png" alt="Logo PPUT Ostrans"></a>
      <span>PPUT Ostrans</span></a>
    </div>
    <button id="themeToggle" type="button" aria-label="Przełącz motyw" aria-pressed="false" style="margin-left:12px">Motyw</button>
    <nav>
      <a href="https://ostrans.famisska.pl/pracuj">Pracuj z nami</a>
      <a href="https://ostrans.famisska.pl/linie">Linie</a>
      <a href="https://ostrans.famisska.pl/aktualnosci">Aktualności</a>
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
          <a class="btn alt" href="https://ostrans.famisska.pl/aktualnosci">Aktualności</a>
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
        <a class="btn" href="https://ostrans.famisska.pl/pracuj">Pracuj z nami</a>
        <a class="btn alt" href="https://ostrans.famisska.pl/linie">Linie</a>
        <a class="btn alt" href="https://ostrans.famisska.pl/aktualnosci">Aktualności</a>
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
    <div class="footer-inner">
      <span>© <?=date('Y')?> PPUT Ostrans</span>
      <span><a href="https://ostrans.famisska.pl/polityka-prywatnosci">Polityka prywatności</a></span>
    </div>
  </footer>

  <script>
    // Prosty toggle motywu na potrzeby strony głównej (współgra z przyciskiem w panelu)
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
  <script src="/panel/panel.php"></script>
</body>
</html>
