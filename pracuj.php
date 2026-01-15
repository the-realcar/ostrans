<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Praca - PPUT Ostrans</title>
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
      --card-bg: #f9f9f9;
      --border: #ccc;
      --brand: #003366;
      --brand-hover: #0055aa;
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
      max-width: 900px;
      margin: auto;
    }

    h1 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 2rem;
    }

    form {
      background: var(--card-bg);
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      border: 1px solid var(--border);
    }

    form h2 {
      margin-bottom: 20px;
      text-align: center;
      font-size: 1.5rem;
    }

    form label {
      display: block;
      margin-bottom: 15px;
      font-weight: 600;
    }

    form input[type="text"],
    form textarea {
      width: 100%;
      padding: 12px;
      margin-top: 6px;
      border: 1px solid var(--border);
      border-radius: 5px;
      font-family: Quicksand, sans-serif;
      background: var(--bg);
      color: var(--text);
      transition: border-color 0.3s;
    }

    form input[type="text"]:focus,
    form textarea:focus {
      outline: none;
      border-color: var(--brand);
    }

    form input[type="range"] {
      width: 100%;
      margin-top: 8px;
    }

    form input[type="checkbox"] {
      margin-right: 8px;
    }

    form button {
      padding: 14px 24px;
      background-color: var(--brand);
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s;
      width: 100%;
      margin-top: 10px;
    }

    form button:hover {
      background-color: var(--brand-hover);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,51,102,.3);
    }

    form button:disabled {
      background-color: #999;
      cursor: not-allowed;
      transform: none;
    }

    #statusMsg {
      margin-top: 15px;
      text-align: center;
      font-weight: 600;
    }

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

    /* Dark theme */
    body[data-theme="dark"] {
      --bg: #0b1220;
      --text: #e5e7eb;
      --muted: #9ca3af;
      --card-bg: #0f172a;
      --border: #1f2937;
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

      h1 {
        font-size: 1.5rem;
      }

      form {
        padding: 20px;
      }
    }

    @media (max-width: 480px) {
      form {
        padding: 15px;
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
    <h1>Pracuj z Nami - Ostrans</h1>

    <form id="driverForm">
      <h2>Formularz na Stanowisko Kierowcy</h2>

      <label>1. Imię:<br>
        <input type="text" name="name" required>
      </label>

      <label>2. Discord + ID:<br>
        <input type="text" name="discord" required placeholder="@nick 123456789012345678">
      </label>

      <label>3. Numer kierowcy w SILu:<br>
        <input type="text" name="sil_number" required>
      </label>

      <label>4. Jaki jest limit prędkości w terenie zabudowanym?<br>
        <input type="text" name="speed_limit" required>
      </label>

      <label>5. Wybierz wszystkie dodatki, które posiadasz:</label>
      <label><input type="checkbox" name="addons" value="Tramwaje"> Tramwaje</label>
      <label><input type="checkbox" name="addons" value="Autobusy Przegubowe"> Autobusy Przegubowe</label>
      <label><input type="checkbox" name="addons" value="Dłuższe Autobusy Przegubowe"> Dłuższe Autobusy Przegubowe</label>
      <label><input type="checkbox" name="addons" value="Malowania Pojazdów"> Malowania Pojazdów</label>
      <label><input type="checkbox" name="addons" value="Niestandardowe Trasy"> Niestandardowe Trasy</label>

      <label>6. Jak bardzo będziesz aktywny/-a w firmie? (1–10)<br>
        <input type="range" name="activity" min="1" max="10" value="5" required>
        <output id="activityValue">5</output>
      </label>

      <label>7. Określ swoje umiejętności prowadzenia pojazdów (1–10)<br>
        <input type="range" name="skills" min="1" max="10" value="5" required>
        <output id="skillsValue">5</output>
      </label>

      <label>8. Czy chcesz coś jeszcze dodać? (opcjonalnie)<br>
        <textarea name="additional" rows="4"></textarea>
      </label>

      <button type="submit" id="submitBtn">Wyślij</button>
      <p id="statusMsg"></p>
    </form>
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
        btn.textContent = mode === 'dark' ? '☀️ Motyw' : '🌙 Motyw';
        try { localStorage.setItem('theme', mode); } catch(e){}
        btn && btn.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
      };
      let initial = 'light';
      try { initial = localStorage.getItem('theme') || initial; } catch(e){}
      apply(initial);
      btn && btn.addEventListener('click', () => apply(document.body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'));
    })();

    // Range value display
    document.querySelector('[name="activity"]').addEventListener('input', function() {
      document.getElementById('activityValue').textContent = this.value;
    });
    document.querySelector('[name="skills"]').addEventListener('input', function() {
      document.getElementById('skillsValue').textContent = this.value;
    });
  </script>
  <script src="https://ostrans.famisska.pl/sendform.js"></script>
</body>
</html>
