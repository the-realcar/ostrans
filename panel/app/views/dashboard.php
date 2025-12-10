<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <title>Panel — dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/panel/employee.css">
</head>
<body>
  <header class="panel-header">
    <div class="logo-title">
      <a href="/"><img src="https://ostrans.famisska.pl/logo.png" alt="Logo"></a>
      <span>Panel pracowników — PPUT Ostrans</span>
    </div>
    <nav><a href="/">Strona główna</a></nav>
  </header>

  <main class="panel-main">
    <div id="dashboard" class="dashboard">
      <div class="dash-top">
        <h2 id="dashTitle">Panel</h2>
        <div>
          <span id="currentUser" class="small-muted"><?=htmlspecialchars(($user['imie'] ?? $user['login']) . ' ' . ($user['nazwisko'] ?? ''))?> (<?=htmlspecialchars($user['uprawnienie'] ?? '')?>)</span>
          <a href="?route=logout"><button id="logoutBtn">Wyloguj</button></a>
        </div>
      </div>

      <div class="nav-role">
        <button data-show="driverView">Kierowca</button>
        <button data-show="dispoView">Dyspozytor</button>
        <button data-show="adminView">Zarząd</button>
      </div>

      <div id="driverView" class="panel role-view hidden">
        <h3>Grafik</h3>
        <div id="grafik">Ładowanie grafiku...</div>
        <h3>Wnioski</h3>
        <form id="wniosekForm">
          <select id="wniosekTyp">
            <option value="kurs_z_wolnego">Kurs z wolnego</option>
            <option value="urlop">Urlop</option>
            <option value="przydzial_pojazdu">Przydział pojazdu stałego</option>
          </select>
          <textarea id="wniosekOpis" placeholder="Opis..." rows="3"></textarea>
          <button type="submit">Wyślij wniosek</button>
        </form>
        <div id="wnioskiList"></div>
      </div>

      <div id="dispoView" class="panel role-view hidden">
        <h3>Przydziały / Linie</h3>
        <div id="przydzialyControls" class="small-muted">Interfejs zarządzania brygadami wymaga backend API.</div>
        <div id="linieBrygady"></div>
      </div>

      <div id="adminView" class="panel role-view hidden">
        <h3>Zarządzanie pracownikami</h3>
        <div id="adminUsers">Ładowanie użytkowników...</div>
      </div>
    </div>
  </main>

  <script>
    // expose minimal user info for existing panel.js
    window.OSTRANS_USER = <?=json_encode($user)?>;
  </script>
  <script src="/panel/panel.js"></script>
</body>
</html>
