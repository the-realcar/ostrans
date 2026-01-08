<?php
// Simple PHP API front-controller replacing Node server.js
use App\Core\Database;
use App\Controllers\ApiController;

// Basic CORS
header('Access-Control-Allow-Origin: ' . ($_ENV['ALLOW_ORIGIN'] ?? $_SERVER['HTTP_ORIGIN'] ?? '*'));
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
    default:
        json_response(['error' => 'not found'], 404);
}
