<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 - Linia nie znaleziona | PPUT Ostrans</title>
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="/linie/styles.css">
</head>
<body>
  <header class="site-header">
    <div class="header-content">
      <div class="logo-section">
        <a href="/" aria-label="Strona główna PPUT Ostrans">
          <img src="https://ostrans.famisska.pl/logo.png" alt="Logo PPUT Ostrans">
        </a>
        <span class="logo-text">PPUT Ostrans</span>
      </div>
      <nav class="header-nav">
        <a href="/">Strona główna</a>
        <a href="/linie.php">Wszystkie linie</a>
        <a href="/panel/index.php">Panel pracowników</a>
      </nav>
    </div>
  </header>

  <main class="main-content">
    <div class="empty-state" style="padding: 80px 20px;">
      <div class="empty-state-icon" style="font-size: 5rem;">🚌</div>
      <h1 class="empty-state-text" style="font-size: 2rem; margin-bottom: 16px;">404 - Linia nie znaleziona</h1>
      <p style="color: var(--text-secondary); font-size: 1.1rem; margin-bottom: 32px;">
        Przepraszamy, nie możemy znaleźć tej linii. Sprawdź listę wszystkich dostępnych linii.
      </p>
      <a href="/linie.php" style="
        display: inline-block;
        background: var(--brand-primary);
        color: white;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.2s;
      " onmouseover="this.style.background='var(--brand-secondary)'" onmouseout="this.style.background='var(--brand-primary)'">
        Zobacz wszystkie linie
      </a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="footer-content">
      <span>© <?= date('Y') ?> PPUT Ostrans</span>
      <div class="footer-links">
        <a href="https://ostrans.famisska.pl/polityka-prywatnosci">Polityka prywatności</a>
      </div>
    </div>
  </footer>
</body>
</html>
