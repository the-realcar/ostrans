<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <title>Panel — Zgłoszenia</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/panel/employee.css">
</head>
<body>
  <header class="panel-header">
    <div class="logo-title">
      <a href="/"><img src="https://ostrans.famisska.pl/logo.png" alt="Logo"></a>
      <span>Panel — Zgłoszenia</span>
    </div>
    <button id="themeToggle" type="button" aria-label="Przełącz motyw" aria-pressed="false">Motyw</button>
    <nav>
      <span style="color:#dbeafe;margin-right:12px"><?=htmlspecialchars($user['imie'] ?? '')?> (<?=htmlspecialchars($user['uprawnienie'] ?? '')?>)</span>
      <a href="/panel/index.php?route=dashboard">Dashboard</a>
      <a href="/">Strona główna</a>
      <a href="/panel/index.php?route=logout">Wyloguj</a>
    </nav>
  </header>

  <main class="panel-main">
    <section class="panel">
      <h2>Zgłoszenia (awaria/wypadek)</h2>
      <form id="zgloszenieForm" enctype="multipart/form-data">
        <div class="files-input" id="zgloszenieFiles"></div>
      </form>
      <div id="zgloszeniaList"></div>
    </section>
  </main>

  <footer class="panel-footer">PPUT Ostrans • Panel</footer>

  <script>window.OSTRANS_USER = <?=json_encode($user)?>;</script>
  <script src="/panel/panel.php"></script>
</body>
</html>
