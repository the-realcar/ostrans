<?php
// Simple PHP API front-controller replacing Node server.js
use App\Core\Database;
use App\Controllers\ApiController;

// Load .env file
if (file_exists(__DIR__ . '/../.env')) {
    foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($k,$v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        // Remove quotes
        if ((substr($v, 0, 1) === '"' && substr($v, -1) === '"') || 
            (substr($v, 0, 1) === "'" && substr($v, -1) === "'")) {
            $v = substr($v, 1, -1);
        }
        if (!getenv($k)) putenv("$k=$v");
        if (!isset($_ENV[$k])) $_ENV[$k] = $v;
    }
}

// Basic CORS
header('Access-Control-Allow-Origin: ' . (getenv('ALLOW_ORIGIN') ?: ($_ENV['ALLOW_ORIGIN'] ?? $_SERVER['HTTP_ORIGIN'] ?? '*')));
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/controllers/ApiController.php';

// util
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function b64url($d){ return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
function verify_jwt($jwt, $secret) {
    $parts = explode('.', $jwt);
    if (count($parts)!==3) return null;
    [$h,$p,$s] = $parts;
    $sig = b64url(hash_hmac('sha256', "$h.$p", $secret, true));
    if (!hash_equals($sig, $s)) return null;
    $payload = json_decode(base64_decode(strtr($p,'-_','+/')), true);
    if (!$payload) return null;
    if (isset($payload['exp']) && time() > $payload['exp']) return null;
    return $payload;
}
function get_bearer_user($secret) {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$h) return null;
    if (stripos($h, 'Bearer ') !== 0) return null;
    $token = trim(substr($h, 7));
    return verify_jwt($token, $secret);
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($uri, '/api') !== 0) {
    json_response(['error' => 'not found'], 404);
}

$db = new Database();
if (!$db->pdo) json_response(['error' => 'db'], 500);
$api = new ApiController($db);
$jwtSecret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?? 'change_this_secret';

$method = $_SERVER['REQUEST_METHOD'];

// routing
switch (true) {
    case $uri === '/api/login' && $method === 'POST':
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$out, $err] = $api->login($body['login'] ?? '', $body['password'] ?? '', $jwtSecret);
        if ($err) json_response(['error'=>'invalid'], 401);
        json_response($out);
        break;
    case $uri === '/api/me' && $method === 'GET':
        $u = get_bearer_user($jwtSecret); if (!$u) json_response(['error'=>'no auth'],401);
        $me = $api->me($u['id']); if (!$me) json_response(['error'=>'no user'],404);
        json_response(['user'=>$me]);
        break;
    case $uri === '/api/wnioski' && $method === 'GET':
        $u = get_bearer_user($jwtSecret); if (!$u) json_response(['error'=>'no auth'],401);
        $uid = $_GET['userId'] ?? null;
        $rows = $api->getWnioski($u, $uid);
        json_response($rows);
        break;
    case $uri === '/api/wnioski' && $method === 'POST':
        $u = get_bearer_user($jwtSecret); if (!$u) json_response(['error'=>'no auth'],401);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$res,$err] = $api->addWniosek($u, $body);
        if ($err) json_response(['error'=>$err],400);
        json_response(['ok'=>true,'wniosek'=>$res]);
        break;
    case $uri === '/api/zgloszenia' && $method === 'POST':
        $u = get_bearer_user($jwtSecret); if (!$u) json_response(['error'=>'no auth'],401);
        [$res,$err] = $api->addZgloszenie($u, $_POST, $_FILES['dowody'] ?? []);
        if ($err) json_response(['error'=>$err],400);
        json_response(['ok'=>true,'zgloszenie'=>$res]);
        break;
    case $uri === '/api/raporty/pending':
        $u = get_bearer_user($jwtSecret); if (!$u) json_response(['error'=>'no auth'],401);
        json_response($api->raportyPending());
        break;
    case $uri === '/api/raporty/sent':
        $u = get_bearer_user($jwtSecret); if (!$u) json_response(['error'=>'no auth'],401);
        json_response($api->raportySent());
        break;
    case $uri === '/api/raporty/cancelled':
        $u = get_bearer_user($jwtSecret); if (!$u) json_response(['error'=>'no auth'],401);
        json_response($api->raportyCancelled());
        break;
    case $uri === '/api/pojazdy':
        $u = get_bearer_user($jwtSecret); if (!$u) json_response(['error'=>'no auth'],401);
        json_response($api->pojazdy());
        break;
    case $uri === '/api/linie':
        $u = get_bearer_user($jwtSecret); if (!$u) json_response(['error'=>'no auth'],401);
        json_response($api->linie());
        break;
    case $uri === '/api/brygady':
        $u = get_bearer_user($jwtSecret); if (!$u) json_response(['error'=>'no auth'],401);
        json_response($api->getBrygady());
        break;
    case $uri === '/api/grafik':
        $u = get_bearer_user($jwtSecret); if (!$u) json_response(['error'=>'no auth'],401);
        $uid = $_GET['userId'] ?? null;
        json_response($api->grafik($uid));
        break;
    case $uri === '/api/pracownicy':
        $u = get_bearer_user($jwtSecret); if (!$u || ($u['uprawnienie'] ?? '')!=='zarzad') json_response(['error'=>'forbidden'],403);
        json_response($api->pracownicy());
        break;
    case $uri === '/api/admin/pracownicy':
        $u = get_bearer_user($jwtSecret); if (!$u || ($u['uprawnienie'] ?? '')!=='zarzad') json_response(['error'=>'forbidden'],403);
        json_response($api->pracownicyAll());
        break;
    case $uri === '/api/admin/pracownik' && $method === 'POST':
        $u = get_bearer_user($jwtSecret); if (!$u || ($u['uprawnienie'] ?? '')!=='zarzad') json_response(['error'=>'forbidden'],403);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$res, $err] = $api->adminPracownik($body);
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true, 'pracownik'=>$res]);
        break;
    case preg_match('#^/api/admin/employee/(\d+)/deactivate$#', $uri, $m) && $method === 'POST':
        $u = get_bearer_user($jwtSecret); if (!$u || ($u['uprawnienie'] ?? '')!=='zarzad') json_response(['error'=>'forbidden'],403);
        [$res, $err] = $api->deactivateEmployee((int)$m[1]);
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true]);
        break;
    case $uri === '/api/activity-log':
        $u = get_bearer_user($jwtSecret); if (!$u || ($u['uprawnienie'] ?? '')!=='zarzad') json_response(['error'=>'forbidden'],403);
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'entity_type' => $_GET['entity_type'] ?? null,
            'entity_id' => $_GET['entity_id'] ?? null,
        ];
        json_response($api->getActivityLog($filters));
        break;
    case $uri === '/api/password-reset/request' && $method === 'POST':
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$res, $err] = $api->requestPasswordReset($body['login'] ?? '');
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true, 'message'=>'Reset link sent']);
        break;
    case $uri === '/api/password-reset/confirm' && $method === 'POST':
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$res, $err] = $api->resetPassword($body['token'] ?? '', $body['newPassword'] ?? '');
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true]);
        break;
    case $uri === '/api/password/change' && $method === 'POST':
        $u = get_bearer_user($jwtSecret); if (!$u) json_response(['error'=>'no auth'],401);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$res, $err] = $api->changePassword($u['id'], $body['oldPassword'] ?? '', $body['newPassword'] ?? '');
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true]);
        break;
    case $uri === '/api/admin/rejestracja' && $method === 'POST':
        $u = get_bearer_user($jwtSecret); if (!$u || ($u['uprawnienie'] ?? '')!=='zarzad') json_response(['error'=>'forbidden'],403);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        json_response($api->adminRejestracja($body));
        break;
    case $uri === '/api/admin/grafik' && $method === 'POST':
        $u = get_bearer_user($jwtSecret); if (!$u || ($u['uprawnienie'] ?? '')!=='zarzad') json_response(['error'=>'forbidden'],403);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        json_response($api->adminGrafik($body));
        break;
    case $uri === '/api/admin/pojazd' && $method === 'POST':
        $u = get_bearer_user($jwtSecret); if (!$u || ($u['uprawnienie'] ?? '')!=='zarzad') json_response(['error'=>'forbidden'],403);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $res = $api->adminPojazd($body, 'POST', null, $u);
        if (is_array($res) && isset($res[1]) && $res[1]) json_response(['error'=>$res[1]],400);
        json_response(['ok'=>true, 'pojazd'=>$res]);
        break;
    case preg_match('#^/api/admin/pojazd/(\d+)$#', $uri, $m) && $method === 'PUT':
        $u = get_bearer_user($jwtSecret); if (!$u || ($u['uprawnienie'] ?? '')!=='zarzad') json_response(['error'=>'forbidden'],403);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $res = $api->adminPojazd($body, 'PUT', (int)$m[1], $u);
        if (is_array($res) && isset($res[1]) && $res[1]) json_response(['error'=>$res[1]],400);
        json_response(['ok'=>true, 'pojazd'=>$res]);
        break;
    case preg_match('#^/api/admin/pojazd/(\d+)$#', $uri, $m) && $method === 'DELETE':
        $u = get_bearer_user($jwtSecret); if (!$u || ($u['uprawnienie'] ?? '')!=='zarzad') json_response(['error'=>'forbidden'],403);
        $res = $api->adminPojazd([], 'DELETE', (int)$m[1], $u);
        if (is_array($res) && isset($res[1]) && $res[1]) json_response(['error'=>$res[1]],400);
        json_response(['ok'=>true, 'pojazd'=>$res]);
        break;
    case preg_match('#^/api/admin/pracownik/(\d+)/pojazd-staly$#', $uri, $m) && $method === 'POST':
        $u = get_bearer_user($jwtSecret); if (!$u || !in_array(($u['uprawnienie'] ?? ''), ['zarzad','dyspozytor'])) json_response(['error'=>'forbidden'],403);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$res,$err] = $api->assignPermanentVehicle($u, (int)$m[1], $body['pojazd_id'] ?? null);
        if ($err) json_response(['error'=>$err],400);
        json_response(['ok'=>true, 'pojazd_staly'=>$res]);
        break;
    case preg_match('#^/api/export/(grafiki|pojazdy|brygady)$#', $uri, $m) && $method === 'GET':
        $u = get_bearer_user($jwtSecret); 
        if (!$u || !in_array(($u['uprawnienie'] ?? ''), ['zarzad','dyspozytor'])) 
            json_response(['error'=>'forbidden'],403);
        
        $entity = $m[1];
        $params = $_GET;
        
        switch ($entity) {
            case 'grafiki':
                $result = $api->exportGrafiki($params);
                break;
            case 'pojazdy':
                $result = $api->exportPojazdy($params);
                break;
            case 'brygady':
                $result = $api->exportBrygady($params);
                break;
        }
        
        if (isset($result['error'])) {
            json_response($result, 500);
        }
        
        header('Content-Type: ' . $result['mime']);
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        echo $result['content'];
        exit;
        break;
    // F14-F16: Lines management
    case $uri === '/api/admin/linia' && $method === 'POST':
        $u = get_bearer_user($jwtSecret); 
        if (!$u || !in_array(($u['uprawnienie'] ?? ''), ['zarzad','dyspozytor'])) 
            json_response(['error'=>'forbidden'],403);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$res, $err] = $api->adminLinia($body, 'POST', null, $u);
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true, 'linia'=>$res]);
        break;
    case preg_match('#^/api/admin/linia/(\d+)$#', $uri, $m) && $method === 'PUT':
        $u = get_bearer_user($jwtSecret); 
        if (!$u || !in_array(($u['uprawnienie'] ?? ''), ['zarzad','dyspozytor'])) 
            json_response(['error'=>'forbidden'],403);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$res, $err] = $api->adminLinia($body, 'PUT', (int)$m[1], $u);
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true, 'linia'=>$res]);
        break;
    case preg_match('#^/api/admin/linia/(\d+)$#', $uri, $m) && $method === 'DELETE':
        $u = get_bearer_user($jwtSecret); 
        if (!$u || !in_array(($u['uprawnienie'] ?? ''), ['zarzad','dyspozytor'])) 
            json_response(['error'=>'forbidden'],403);
        [$res, $err] = $api->adminLinia([], 'DELETE', (int)$m[1], $u);
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true, 'linia'=>$res]);
        break;
    // F14-F16: Brigade management
    case $uri === '/api/admin/brygada' && $method === 'POST':
        $u = get_bearer_user($jwtSecret); 
        if (!$u || !in_array(($u['uprawnienie'] ?? ''), ['zarzad','dyspozytor'])) 
            json_response(['error'=>'forbidden'],403);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$res, $err] = $api->adminBrygada($body, 'POST', null, $u);
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true, 'brygada'=>$res]);
        break;
    case preg_match('#^/api/admin/brygada/(\d+)$#', $uri, $m) && $method === 'PUT':
        $u = get_bearer_user($jwtSecret); 
        if (!$u || !in_array(($u['uprawnienie'] ?? ''), ['zarzad','dyspozytor'])) 
            json_response(['error'=>'forbidden'],403);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$res, $err] = $api->adminBrygada($body, 'PUT', (int)$m[1], $u);
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true, 'brygada'=>$res]);
        break;
    case preg_match('#^/api/admin/brygada/(\d+)$#', $uri, $m) && $method === 'DELETE':
        $u = get_bearer_user($jwtSecret); 
        if (!$u || !in_array(($u['uprawnienie'] ?? ''), ['zarzad','dyspozytor'])) 
            json_response(['error'=>'forbidden'],403);
        [$res, $err] = $api->adminBrygada([], 'DELETE', (int)$m[1], $u);
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true, 'brygada'=>$res]);
        break;
    // F17-F20: Schedule management - update and delete
    case preg_match('#^/api/admin/grafik/(\d+)$#', $uri, $m) && $method === 'PUT':
        $u = get_bearer_user($jwtSecret); 
        if (!$u || !in_array(($u['uprawnienie'] ?? ''), ['zarzad','dyspozytor'])) 
            json_response(['error'=>'forbidden'],403);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$res, $err] = $api->updateGrafik((int)$m[1], $body, $u);
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true, 'grafik'=>$res]);
        break;
    case preg_match('#^/api/admin/grafik/(\d+)$#', $uri, $m) && $method === 'DELETE':
        $u = get_bearer_user($jwtSecret); 
        if (!$u || !in_array(($u['uprawnienie'] ?? ''), ['zarzad','dyspozytor'])) 
            json_response(['error'=>'forbidden'],403);
        [$res, $err] = $api->deleteGrafik((int)$m[1], $u);
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true, 'grafik'=>$res]);
        break;
    // F12: Vehicle usage history
    case preg_match('#^/api/pojazd/(\d+)/usage$#', $uri, $m) && $method === 'GET':
        $u = get_bearer_user($jwtSecret); 
        if (!$u || !in_array(($u['uprawnienie'] ?? ''), ['zarzad','dyspozytor'])) 
            json_response(['error'=>'forbidden'],403);
        json_response($api->getVehicleUsageHistory((int)$m[1]));
        break;
    // F22-F24: Update request status (approve/reject with reason)
    case preg_match('#^/api/wnioski/(\d+)/status$#', $uri, $m) && $method === 'PUT':
        $u = get_bearer_user($jwtSecret); 
        if (!$u || !in_array(($u['uprawnienie'] ?? ''), ['zarzad','dyspozytor'])) 
            json_response(['error'=>'forbidden'],403);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        [$res, $err] = $api->updateWniosekStatus((int)$m[1], $body['status'] ?? '', $u, $body['reason'] ?? null);
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true, 'wniosek'=>$res]);
        break;
    // F28: Import employees from CSV
    case $uri === '/api/admin/import/pracownicy' && $method === 'POST':
        $u = get_bearer_user($jwtSecret); 
        if (!$u || ($u['uprawnienie'] ?? '') !== 'zarzad') 
            json_response(['error'=>'forbidden'],403);
        
        if (empty($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            json_response(['error' => 'no_file_uploaded'], 400);
        }
        
        $tmpFile = $_FILES['csv']['tmp_name'];
        [$res, $err] = $api->importPracownicyCSV($tmpFile, $u);
        if ($err) json_response(['error'=>$err], 400);
        json_response(['ok'=>true, 'result'=>$res]);
        break;
    
    // Public API endpoints (no authentication required)
    case $uri === '/api/public/lines' && $method === 'GET':
        // Priority: SIL API (with cache) → MySQL database → Fallback
        
        // Try SIL API cache first (5 min TTL)
        $cacheFile = sys_get_temp_dir() . '/ostrans_lines_cache.json';
        $cacheTime = file_exists($cacheFile) ? filemtime($cacheFile) : 0;
        $linesData = null;
        
        if (time() - $cacheTime < 300 && $cacheTime > 0) {
            $linesData = @file_get_contents($cacheFile);
        }
        
        // Try SIL API if no cache
        if (!$linesData) {
            $context = stream_context_create(['http' => ['timeout' => 3]]);
            $linesData = @file_get_contents('https://sil.kanbeq.me/ostrans/api/lines', false, $context);
            if ($linesData) {
                @file_put_contents($cacheFile, $linesData);
            }
        }
        
        // If SIL succeeded, return it
        if ($linesData) {
            $lines = json_decode($linesData, true);
            if (!empty($lines)) {
                json_response(['lines' => $lines, 'source' => 'sil']);
            }
        }
        
        // Try MySQL database
        if ($db && $db->pdo) {
            try {
                $stmt = $db->pdo->query('SELECT nr_linii as line, typ as type, start_point as `from`, end_point as `to` FROM linie ORDER BY typ, nr_linii');
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                if (!empty($rows)) {
                    // Map first variant as 01
                    foreach ($rows as &$row) {
                        $row['variant'] = '01';
                        $row['route'] = '';
                    }
                    json_response(['lines' => $rows, 'source' => 'mysql']);
                }
            } catch (\Throwable $e) {
                // Fall through to fallback
            }
        }
        
        // Fallback: Sample data
        $fallbackLines = [
            ['line' => '1', 'type' => 'tram', 'from' => 'Centrum', 'to' => 'Dworzec', 'variant' => '01', 'route' => ''],
            ['line' => '2', 'type' => 'tram', 'from' => 'Dworzec', 'to' => 'Nowy Świat', 'variant' => '01', 'route' => ''],
            ['line' => '1', 'type' => 'bus', 'from' => 'Centrum', 'to' => 'Lotnisko', 'variant' => '01', 'route' => ''],
            ['line' => '2', 'type' => 'bus', 'from' => 'Dworzec', 'to' => 'Fabryka', 'variant' => '01', 'route' => ''],
            ['line' => '3', 'type' => 'bus', 'from' => 'Centrum', 'to' => 'Park', 'variant' => '01', 'route' => ''],
            ['line' => '10', 'type' => 'trol', 'from' => 'Centrum', 'to' => 'Terminal', 'variant' => '01', 'route' => ''],
        ];
        json_response(['lines' => $fallbackLines, 'source' => 'fallback']);
        break;
    
    case preg_match('#^/api/public/lines/([^/]+)/([^/]+)/stops$#', $uri, $m) && $method === 'GET':
        $line = urldecode($m[1]);
        $variant = urldecode($m[2]);
        
        // Priority: SIL API (with cache) → MySQL database → Fallback
        
        // Try SIL API cache first (5 min TTL)
        $cacheKey = md5("$line-$variant");
        $cacheFile = sys_get_temp_dir() . "/ostrans_stops_{$cacheKey}.json";
        $cacheTime = file_exists($cacheFile) ? filemtime($cacheFile) : 0;
        $stopsData = null;
        
        if (time() - $cacheTime < 300 && $cacheTime > 0) {
            $stopsData = @file_get_contents($cacheFile);
        }
        
        // Try SIL API if no cache
        if (!$stopsData) {
            $context = stream_context_create(['http' => ['timeout' => 3]]);
            $stopsData = @file_get_contents("https://sil.kanbeq.me/ostrans/api/lines/" . urlencode($line) . "/" . urlencode($variant) . "/stops", false, $context);
            if ($stopsData) {
                @file_put_contents($cacheFile, $stopsData);
            }
        }
        
        // If SIL succeeded, return it
        if ($stopsData) {
            $stops = json_decode($stopsData, true);
            if (!empty($stops)) {
                json_response(['stops' => $stops, 'source' => 'sil']);
            }
        }
        
        // Try MySQL database - search for stops for this line
        if ($db && $db->pdo) {
            try {
                // Assuming there's a stops/przystanki table with line number reference
                $stmt = $db->pdo->prepare('SELECT DISTINCT nazwa as name, latitude as lat, longitude as lng FROM przystanki WHERE linia = :line ORDER BY lp ASC');
                $stmt->execute([':line' => $line]);
                $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                if (!empty($rows)) {
                    json_response(['stops' => $rows, 'source' => 'mysql']);
                }
            } catch (\Throwable $e) {
                // Fall through to fallback
            }
        }
        
        // Fallback: Sample stops
        $fallbackStops = [
            ['name' => 'Przystanek Centrum', 'lat' => 50.0475, 'lng' => 14.4379],
            ['name' => 'Przystanek Św. Krzyża', 'lat' => 50.0485, 'lng' => 14.4389],
            ['name' => 'Przystanek Nowy Świat', 'lat' => 50.0495, 'lng' => 14.4399],
            ['name' => 'Przystanek Dworzec', 'lat' => 50.0505, 'lng' => 14.4409],
        ];
        json_response(['stops' => $fallbackStops, 'source' => 'fallback']);
        break;
    
    default:
        json_response(['error' => 'not found'], 404);
}
