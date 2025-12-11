<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <title>Panel — logowanie</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/panel/employee.css">
</head>
<body>
  <header class="panel-header" style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px">
    <div class="logo-title"><a href="/"><img src="https://ostrans.famisska.pl/logo.png" alt="Logo" style="height:36px"></a><span>PPUT Ostrans</span></div>
    <button id="themeToggle" type="button" aria-label="Przełącz motyw" aria-pressed="false">Motyw</button>
  </header>
  <main class="panel-main" style="padding-top:40px;">
    <section class="auth-panel" aria-labelledby="loginTitle">
      <h3 id="loginTitle">Logowanie</h3>
      <?php if (!empty($error)): ?>
        <div style="color:#c00;margin-bottom:8px;"><?=htmlspecialchars($error)?></div>
      <?php endif; ?>
      <form method="post" action="?route=login" novalidate>
        <label class="sr-only" for="loginInput">Login</label>
        <input id="loginInput" name="login" placeholder="Login" aria-required="true" />
        <label class="sr-only" for="passInput">Hasło</label>
        <input id="passInput" name="password" type="password" placeholder="Hasło" aria-required="true" />
        <button type="submit">Zaloguj</button>
      </form>
      <p class="small-muted">Demo: driver1/dpass, dispo1/dpass, admin1/dpass</p>
    </section>
  </main>
  <script src="/panel/panel.php"></script>
</body>
</html>
