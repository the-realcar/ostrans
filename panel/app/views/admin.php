<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <title>Panel danych — Ostrans (Admin)</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/panel/employee.css">
</head>
<body>
  <a class="sr-only skip-link" href="#adminMain">Pomiń do treści</a>
  <header class="panel-header">
    <div class="logo-title">
      <a href="/"><img src="https://ostrans.famisska.pl/logo.png" alt="Logo"></a>
      <span>Panel — Admin (Zarząd)</span>
    </div>
    <button id="themeToggle" type="button" aria-label="Przełącz motyw" aria-pressed="false">Motyw</button>
    <nav>
      <span style="color:#dbeafe;margin-right:12px"><?=htmlspecialchars($user['imie'] ?? '')?> (<?=htmlspecialchars($user['uprawnienie'] ?? '')?>)</span>
      <a href="/panel/index.php?route=dashboard">Dashboard</a>
      <a href="/panel/index.php?route=pojazdy">Pojazdy</a>
      <a href="/">Strona główna</a>
      <a href="/panel/index.php?route=logout">Wyloguj</a>
    </nav>
  </header>
  <main id="adminMain" role="main" style="max-width:900px;margin:28px auto;padding:18px">
    <!-- Miejsce na formularze administracyjne; logika w panel.js korzysta z /api/admin/* -->
    <section class="panel">
      <h2>Zarządzanie danymi</h2>
      <div id="adminUsers">Ładowanie...</div>
    </section>
  </main>

  <script>window.OSTRANS_USER = <?=json_encode($user)?>;</script>
  <script src="/panel/panel.php"></script>
</body>
</html>
