x<?php
/**
 * Dynamic Line Detail Page - PPUT Ostrans
 * Displays comprehensive information about a specific line variant
 * Based on GZM design patterns
 */

// Extract line number and variant from URL
$path = $_SERVER['REQUEST_URI'];
preg_match('#/linie/([^/]+)\.php#', $path, $matches);
$slug = $matches[1] ?? '';

// Parse slug (format: lineNum-variant, e.g., 107-01 or A-01)
$parts = explode('-', $slug, 2);
$lineNum = $parts[0] ?? '';
$variantId = $parts[1] ?? '01';

if (!$lineNum) {
  http_response_code(404);
  include __DIR__ . '/404.php';
  exit;
}

// Fetch line data from local API (with SIL cache)
$apiBaseUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$linesApiUrl = $apiBaseUrl . '/panel/api.php/api/public/lines';
$linesData = @file_get_contents($linesApiUrl);
$allLines = [];

if ($linesData) {
    $decoded = json_decode($linesData, true);
    $allLines = $decoded['lines'] ?? [];
}

// Find all variants for this line number
$lineVariants = array_filter($allLines, function($l) use ($lineNum) {
    return strtoupper($l['line'] ?? '') === strtoupper($lineNum);
});

if (empty($lineVariants)) {
  http_response_code(404);
  include __DIR__ . '/404.php';
  exit;
}

// Find the requested variant
$currentVariant = null;
foreach ($lineVariants as $v) {
    if (($v['variant'] ?? '') === $variantId) {
        $currentVariant = $v;
        break;
    }
}

// Fallback to first variant if requested not found
if (!$currentVariant) {
    $currentVariant = reset($lineVariants);
    $variantId = $currentVariant['variant'] ?? '01';
}

$lineType = $currentVariant['type'] ?? 'bus';
$from = $currentVariant['from'] ?? '';
$to = $currentVariant['to'] ?? '';
$route = $currentVariant['route'] ?? '';

// Fetch stops for this variant from local API
$stopsApiUrl = $apiBaseUrl . '/panel/api.php/api/public/lines/' . urlencode($lineNum) . '/' . urlencode($variantId) . '/stops';
$stopsData = @file_get_contents($stopsApiUrl);
$stops = [];

if ($stopsData) {
    $decoded = json_decode($stopsData, true);
    $stops = $decoded['stops'] ?? [];
}

// Type labels and icons
$typeLabels = [
    'tram' => 'Tramwaj',
    'trol' => 'Trolejbus',
    'bus' => 'Autobus',
    'metro' => 'Metro'
];
$typeLabel = $typeLabels[$lineType] ?? 'Linia';

$pageTitle = "$typeLabel $lineNum - $from → $to | PPUT Ostrans";
$pageDescription = "Szczegółowe informacje o linii $lineNum ($typeLabel): trasa $from → $to, przystanki, kierunki i warianty.";

// Sort variants by variant ID
usort($lineVariants, function($a, $b) {
    return strcmp($a['variant'] ?? '', $b['variant'] ?? '');
});
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  
  <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&family=Oswald:wght@400;700;800&family=Doto:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/linie/styles.css">
  <style>
    .site-header {
      background-color: #003366;
      color: white;
      padding: 20px;
    }
    .header-content {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
    }
    .logo-section {
      display: flex;
      align-items: center;
      gap: 15px;
      font-size: 1.5rem;
      font-weight: 700;
    }
    .logo-section img {
      height: 50px;
    }
    .header-nav {
      display: flex;
      gap: 12px;
      align-items: center;
    }
    .header-nav a {
      text-decoration: none;
      color: white;
      font-weight: bold;
      padding: 10px 16px;
      border-radius: 6px;
      background: rgba(255,255,255,.15);
      border: 1px solid rgba(255,255,255,.25);
      transition: all 0.2s;
    }
    .header-nav a:hover {
      background: rgba(255,255,255,.25);
    }
    .theme-toggle {
      background: rgba(255,255,255,.15);
      color: white;
      border: 1px solid rgba(255,255,255,.25);
      border-radius: 6px;
      padding: 10px 16px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
    }
    .theme-toggle:hover {
      background: rgba(255,255,255,.25);
    }
    .site-footer {
      background-color: #003366;
      color: white;
      padding: 40px 20px;
      text-align: center;
      margin-top: 60px;
    }
    .site-footer .footer-logo {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
      font-size: 2rem;
      margin-bottom: 10px;
    }
    .site-footer .footer-logo img {
      height: 80px;
    }
  </style>
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
      
      <button id="themeToggle" class="theme-toggle" type="button" aria-label="Przełącz motyw" aria-pressed="false">
        🌙 Motyw
      </button>
      
      <nav class="header-nav">
        <a href="/pracuj.php">Pracuj z nami</a>
        <a href="/linie.php">Linie</a>
        <a href="/panel/index.php">Panel</a>
      </nav>
    </div>
  </header>

  <main class="main-content">
    <!-- Breadcrumb Navigation -->
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="/">Strona główna</a>
      <span class="breadcrumb-separator">/</span>
      <a href="/linie.php">Linie</a>
      <span class="breadcrumb-separator">/</span>
      <span><?= htmlspecialchars("$typeLabel $lineNum") ?></span>
    </nav>

    <!-- Line Header Card -->
    <section class="line-header-card">
      <div class="line-title-section">
        <div class="line-badge-large <?= htmlspecialchars($lineType) ?>" role="img" aria-label="<?= htmlspecialchars("$typeLabel $lineNum") ?>">
          <?= htmlspecialchars($lineNum) ?>
        </div>
        
        <div class="line-info">
          <div class="line-type-label"><?= htmlspecialchars($typeLabel) ?></div>
          <h1 class="line-route">
            <?= htmlspecialchars($from) ?>
            <span class="route-arrow">→</span>
            <?= htmlspecialchars($to) ?>
          </h1>
          <?php if ($route): ?>
          <p class="line-description">
            <strong>Trasa:</strong> <?= htmlspecialchars($route) ?>
          </p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Variants Section -->
      <?php if (count($lineVariants) > 1): ?>
      <div class="variants-section">
        <div class="variants-label">Dostępne warianty (<?= count($lineVariants) ?>)</div>
        <div class="variants-list">
          <?php foreach ($lineVariants as $v): 
            $vId = $v['variant'] ?? '01';
            $vFrom = $v['from'] ?? '';
            $vTo = $v['to'] ?? '';
            $vUrl = "/linie/" . urlencode($lineNum) . "-" . urlencode($vId) . ".php";
            $isActive = ($vId === $variantId);
          ?>
          <a 
            href="<?= htmlspecialchars($vUrl) ?>" 
            class="variant-card <?= $isActive ? 'active' : '' ?>" 
            title="<?= htmlspecialchars("Wariant $vId: $vFrom → $vTo") ?>"
            <?= $isActive ? 'aria-current="page"' : '' ?>
          >
            <div class="variant-id">WARIANT <?= htmlspecialchars($vId) ?></div>
            <div class="variant-route">
              <?= htmlspecialchars($vFrom) ?> → <?= htmlspecialchars($vTo) ?>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </section>

    <!-- Info Cards Grid -->
    <?php if (!empty($stops)): ?>
    <div class="info-grid">
      <div class="info-card">
        <div class="info-card-title">Liczba przystanków</div>
        <div class="info-card-content"><?= count($stops) ?></div>
      </div>
      
      <div class="info-card">
        <div class="info-card-title">Pierwszy przystanek</div>
        <div class="info-card-content">
          <?php 
            $firstStop = is_array($stops[0]) ? ($stops[0]['name'] ?? $stops[0]['stop'] ?? '') : $stops[0];
            echo htmlspecialchars($firstStop);
          ?>
        </div>
      </div>
      
      <div class="info-card">
        <div class="info-card-title">Ostatni przystanek</div>
        <div class="info-card-content">
          <?php 
            $lastStop = is_array($stops[count($stops) - 1]) ? ($stops[count($stops) - 1]['name'] ?? $stops[count($stops) - 1]['stop'] ?? '') : $stops[count($stops) - 1];
            echo htmlspecialchars($lastStop);
          ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Stops Section -->
    <section class="stops-card">
      <div class="stops-header">
        <h2 class="stops-title">Przystanki</h2>
        <?php if (!empty($stops)): ?>
        <div class="stops-count">
          Wariant <?= htmlspecialchars($variantId) ?> • <?= count($stops) ?> przystanków
        </div>
        <?php endif; ?>
      </div>
      
      <?php if (!empty($stops)): ?>
      <ul class="stops-list" role="list">
        <?php foreach ($stops as $idx => $stop): 
          $stopName = is_array($stop) ? ($stop['name'] ?? $stop['stop'] ?? 'Nieznany przystanek') : $stop;
          $stopNumber = $idx + 1;
        ?>
        <li class="stop-item">
          <div class="stop-marker" aria-hidden="true"></div>
          <div class="stop-number"><?= $stopNumber ?></div>
          <div class="stop-name"><?= htmlspecialchars($stopName) ?></div>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?>
      <div class="empty-state">
        <div class="empty-state-icon" aria-hidden="true">📍</div>
        <p class="empty-state-text">Brak danych o przystankach dla tego wariantu.</p>
        <p style="color: var(--text-muted); margin-top: 8px; font-size: 0.9rem;">
          Dane są pobierane z zewnętrznego API. Spróbuj odświeżyć stronę później.
        </p>
      </div>
      <?php endif; ?>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-logo">
      <a href="/"><img src="https://ostrans.famisska.pl/logo.png" alt="Logo PPUT Ostrans"></a>
      <span>PPUT Ostrans</span>
    </div>
    <p>Copyright © <?= date('Y') ?> Ostrans. Wszelkie prawa zastrzeżone</p>
  </footer>

  <script>
    // Theme toggle functionality
    (function() {
      const themeToggle = document.getElementById('themeToggle');
      
      function applyTheme(mode) {
        document.body.setAttribute('data-theme', mode);
        themeToggle.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
        themeToggle.textContent = mode === 'dark' ? '☀️ Motyw' : '🌙 Motyw';
        
        try {
          localStorage.setItem('theme', mode);
        } catch (e) {
          console.warn('Cannot save theme preference:', e);
        }
      }
      
      // Load saved theme or use system preference
      let initialTheme = 'light';
      try {
        initialTheme = localStorage.getItem('theme') || 
                      (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      } catch (e) {
        console.warn('Cannot load theme preference:', e);
      }
      
      applyTheme(initialTheme);
      
      // Toggle theme on button click
      themeToggle.addEventListener('click', () => {
        const currentTheme = document.body.getAttribute('data-theme');
        applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
      });
      
      // Listen for system theme changes
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!localStorage.getItem('theme')) {
          applyTheme(e.matches ? 'dark' : 'light');
        }
      });
    })();

    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });

    // Add loading state feedback
    window.addEventListener('beforeunload', () => {
      document.body.style.opacity = '0.7';
    });
  </script>
</body>
</html>
