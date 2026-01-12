<?php
// Force HTTPS in production (disabled in development)
$force_https = getenv('FORCE_HTTPS') !== 'false' && (!isset($_SERVER['SERVER_NAME']) || $_SERVER['SERVER_NAME'] !== 'localhost');
if ($force_https && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect, true, 301);
        exit;
    }
}

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

session_start();

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/controllers/HomeController.php';
require_once __DIR__ . '/app/controllers/PanelController.php';

$db = new App\Core\Database();
$controller = new App\Controllers\HomeController($db);
$panel = new App\Controllers\PanelController($db);

$route = $_GET['route'] ?? 'home';

if ($route === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->login();
    exit;
}
if ($route === 'logout') {
    $controller->logout();
    exit;
}
if ($route === 'dashboard') {
    $controller->dashboard();
    exit;
}

// Dynamic panel pages
if ($route === 'grafik') { $panel->grafik(); exit; }
if ($route === 'wnioski') { $panel->wnioski(); exit; }
if ($route === 'raporty') { $panel->raporty(); exit; }
if ($route === 'zgloszenia') { $panel->zgloszenia(); exit; }
if ($route === 'wyslij-raport') { $panel->wyslijRaport(); exit; }
if ($route === 'admin') { $panel->admin(); exit; }
if ($route === 'employees') { $panel->employees(); exit; }
if ($route === 'pojazdy') { $panel->pojazdy(); exit; }
if ($route === 'lines-management') { $panel->linesManagement(); exit; }
if ($route === 'import-pracownicy') { $panel->importPracownicy(); exit; }

// Serve embedded HTML (previously index.html) for home route and inject session user as JS
if ($route === 'home') {
    $user = $_SESSION['user'] ?? null;
    $user_json = json_encode($user, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <title>Panel pracowników — Ostrans</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="employee.css">
</head>
<body>
  <a class="skip-link" href="#mainContent">Pomiń do treści</a>
  <header class="panel-header" role="banner">
    <div class="logo-title">
      <a href="https://ostrans.famisska.pl"><img src="https://ostrans.famisska.pl/logo.png" alt="Logo"></a>
      <span>Panel pracowników — PPUT Ostrans</span>
    </div>
    <button id="themeToggle" type="button" aria-label="Przełącz motyw" aria-pressed="false" style="margin-left:12px">Motyw</button>
    <nav><a href="https://ostrans.famisska.pl">Strona główna</a></nav>
  </header>

  <main id="mainContent" class="panel-main" role="main">
    <div id="authWrapper">
      <div class="auth-panel" id="loginPanel" role="form" aria-labelledby="loginTitle">
        <h3 id="loginTitle">Logowanie</h3>
        <label class="sr-only" for="loginInput">Login</label>
        <input id="loginInput" placeholder="Login" aria-required="true" />
        <label class="sr-only" for="passInput">Hasło</label>
        <input id="passInput" type="password" placeholder="Hasło" aria-required="true" />
        <button id="doLogin" aria-label="Zaloguj">Zaloguj</button>
        <div class="oauth">
          <a id="discordLogin" href="#" title="Logowanie przez Discord">Zaloguj przez Discord</a>
        </div>
        <p class="small-muted">Demo: driver1/dpass, dispo1/dpass, admin1/dpass</p>
        <div id="appNotif" role="status" aria-live="polite" class="sr-only"></div>
      </div>
    </div>

    <div id="dashboard" class="dashboard hidden" aria-hidden="true">
      <div class="dash-top">
        <h2 id="dashTitle">Panel</h2>
        <div>
          <span id="currentUser" class="small-muted"></span>
          <button id="logoutBtn">Wyloguj</button>
        </div>
      </div>

      <div class="nav-role">
        <button data-show="driverView">Kierowca</button>
        <button data-show="dispoView">Dyspozytor</button>
        <button data-show="adminView">Zarząd</button>
      </div>

      <div id="driverView" class="panel role-view hidden" aria-hidden="true" role="region" aria-label="Widok kierowcy">
        <h3>Grafik</h3>
        <div id="grafik" role="region" aria-live="polite">Ładowanie grafiku...</div>
        <h3>Wnioski</h3>
        <form id="wniosekForm">
          <label class="sr-only" for="wniosekTyp">Typ wniosku</label>
          <select id="wniosekTyp">
             <option value="kurs_z_wolnego">Kurs z wolnego</option>
             <option value="urlop">Urlop</option>
             <option value="przydzial_pojazdu">Przydział pojazdu stałego</option>
           </select>
          <label class="sr-only" for="wniosekOpis">Opis wniosku</label>
          <textarea id="wniosekOpis" placeholder="Opis..." rows="3"></textarea>
          <button type="submit" aria-label="Wyślij wniosek">Wyślij wniosek</button>
        </form>
        <div id="wnioskiList" role="region" aria-live="polite" aria-label="Lista wniosków"></div>
      </div>

      <div id="dispoView" class="panel role-view hidden">
        <h3>Przydziały / Linie</h3>
        <div id="przydzialyControls" class="small-muted">Interfejs zarządzania brygadami wymaga backend API.</div>
        <div id="linieBrygady"></div>
        <div style="margin-top:12px;">
          <button onclick="location.href='/panel/grafik'">Otwórz grafik</button>
          <button onclick="location.href='/panel/raporty'">Raporty</button>
          <button onclick="location.href='/panel/zgloszenia'">Zgłoszenia</button>
        </div>
      </div>

      <div id="adminView" class="panel role-view hidden" aria-hidden="true">
        <h3>Zarządzanie pracownikami</h3>
        <div id="adminUsers">Ładowanie użytkowników...</div>
        <div style="margin-top:12px;">
          <button id="openAdminPanel" aria-label="Otwórz panel danych, tylko dla zarządu">Otwórz panel danych (tylko Zarząd)</button>
          <button onclick="location.href='/panel/raporty'">Raporty / Zarządzanie</button>
        </div>
      </div>
    </div>
  </main>

  <footer class="panel-footer">
    <p>PPUT Ostrans • Panel pracowników</p>
  </footer>

  <script>window.OSTRANS_USER = {$user_json};</script>
  <script src="panel.php"></script>
</body>
</html>
HTML;
    exit;
}

// fallback to controller view (login page) for other cases
$controller->index();
exit;
?>
