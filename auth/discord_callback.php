<?php
// Handle Discord OAuth callback, create/find user, sign JWT and redirect to panel with token
$root = __DIR__ . '/..';
if (file_exists($root . '/env.txt')) {
    foreach (file($root . '/env.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($k,$v) = explode('=', $line, 2);
        if (!isset($_ENV[trim($k)])) $_ENV[trim($k)] = trim($v);
    }
}
$clientId = getenv('DISCORD_CLIENT_ID') ?: ($_ENV['DISCORD_CLIENT_ID'] ?? null);
$clientSecret = getenv('DISCORD_CLIENT_SECRET') ?: ($_ENV['DISCORD_CLIENT_SECRET'] ?? null);
$redirectUri = getenv('DISCORD_REDIRECT_URI') ?: ($_ENV['DISCORD_REDIRECT_URI'] ?? null);
$main = getenv('MAIN_SITE') ?: ($_ENV['MAIN_SITE'] ?? 'https://ostrans.famisska.pl');
$guildId = getenv('DISCORD_GUILD_ID') ?: ($_ENV['DISCORD_GUILD_ID'] ?? null);
$botToken = getenv('DISCORD_BOT_TOKEN') ?: ($_ENV['DISCORD_BOT_TOKEN'] ?? null);
$roleZ = getenv('ROLE_ZARZAD_ID') ?: ($_ENV['ROLE_ZARZAD_ID'] ?? null);
$roleD = getenv('ROLE_DYSP_ID') ?: ($_ENV['ROLE_DYSP_ID'] ?? null);
$roleK = getenv('ROLE_KIEROWCA_ID') ?: ($_ENV['ROLE_KIEROWCA_ID'] ?? null);
$jwtSecret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? 'change_this_secret');

$code = $_GET['code'] ?? null;
if (!$code || !$clientId || !$clientSecret) {
    http_response_code(400);
    echo "Missing code or discord client configuration.";
    exit;
}

// exchange code -> token
$tokenUrl = 'https://discord.com/api/oauth2/token';
$post = http_build_query([
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirectUri
]);
$opts = ["http" => ["method" => "POST", "header" => "Content-Type: application/x-www-form-urlencoded\r\n", "content" => $post]];
$context = stream_context_create($opts);
$res = @file_get_contents($tokenUrl, false, $context);
if ($res === false) {
    http_response_code(500);
    echo "Token exchange failed";
    exit;
}
$tokenData = json_decode($res, true);
$accessToken = $tokenData['access_token'] ?? null;
if (!$accessToken) {
    http_response_code(500);
    echo "No access token from Discord";
    exit;
}

// get user info
$opts = ["http" => ["method" => "GET", "header" => "Authorization: Bearer $accessToken\r\n"]];
$context = stream_context_create($opts);
$userRes = @file_get_contents('https://discord.com/api/users/@me', false, $context);
if ($userRes === false) { http_response_code(500); echo "Failed to fetch Discord user"; exit; }
$duser = json_decode($userRes, true);
$discordId = $duser['id'] ?? null;
$username = $duser['username'] ?? null;

// determine roles via guild (bot)
$mapped = null;
if ($guildId && $botToken && $discordId) {
    $opts = ["http" => ["method" => "GET", "header" => "Authorization: Bot $botToken\r\n"]];
    $context = stream_context_create($opts);
    $memberUrl = "https://discord.com/api/guilds/{$guildId}/members/{$discordId}";
    $mres = @file_get_contents($memberUrl, false, $context);
    if ($mres !== false) {
        $member = json_decode($mres, true);
        $roles = $member['roles'] ?? [];
        if ($roleZ && in_array($roleZ, $roles)) $mapped = 'zarzad';
        elseif ($roleD && in_array($roleD, $roles)) $mapped = 'dyspozytor';
        elseif ($roleK && in_array($roleK, $roles)) $mapped = 'kierowca';
    }
}
if (!$mapped) $mapped = 'kierowca';

// DB: find/create pracownik using app core Database
require_once __DIR__ . '/panel/app/core/Database.php';
$db = new App\Core\Database();
$pdo = $db->pdo ?? null;
$user = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT p.*, u.poziom as uprawnienie FROM pracownicy p JOIN uprawnienia u ON p.uprawnienie_id=u.id WHERE p.discord_id = :discord LIMIT 1');
        $stmt->execute(['discord' => $discordId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $user = $row;
            // update uprawnienie if mismatch
            if (($user['uprawnienie'] ?? null) !== $mapped) {
                $upq = $pdo->prepare('SELECT id FROM uprawnienia WHERE poziom = :p LIMIT 1');
                $upq->execute(['p' => $mapped]);
                $upid = $upq->fetchColumn();
                if ($upid) {
                    $pdo->prepare('UPDATE pracownicy SET uprawnienie_id = :up WHERE id = :id')->execute(['up' => $upid, 'id' => $user['id']]);
                    $user['uprawnienie'] = $mapped;
                }
            }
        } else {
            // create minimal pracownik
            $upq = $pdo->prepare('SELECT id FROM uprawnienia WHERE poziom = :p LIMIT 1');
            $upq->execute(['p' => $mapped]);
            $upid = $upq->fetchColumn() ?: null;
            if (!$upid) {
                $any = $pdo->query('SELECT id FROM uprawnienia LIMIT 1')->fetchColumn();
                $upid = $any ?: null;
            }
            $login = 'discord_' . $discordId;
            $imie = $username ? explode(' ', $username)[0] : 'Discord';
            $nazwisko = $username ? (explode(' ', $username)[1] ?? '') : '';
            $ins = $pdo->prepare('INSERT INTO pracownicy (imie,nazwisko,login,discord_id,uprawnienie_id) VALUES (:imie,:nazwisko,:login,:discord,:up) RETURNING id,imie,nazwisko,login');
            // PostgreSQL RETURNING used; try execute and fetch
            if ($ins->execute(['imie'=>$imie,'nazwisko'=>$nazwisko,'login'=>$login,'discord'=>$discordId,'up'=>$upid])) {
                $created = $ins->fetch(\PDO::FETCH_ASSOC);
                $user = $created ?: ['id'=>null,'imie'=>$imie,'nazwisko'=>$nazwisko,'login'=>$login];
                $user['uprawnienie'] = $mapped;
            } else {
                // fallback: insert without RETURNING
                $pdo->prepare('INSERT INTO pracownicy (imie,nazwisko,login,discord_id,uprawnienie_id) VALUES (:imie,:nazwisko,:login,:discord,:up)')->execute(['imie'=>$imie,'nazwisko'=>$nazwisko,'login'=>$login,'discord'=>$discordId,'up'=>$upid]);
                $id = $pdo->lastInsertId();
                $user = ['id'=>$id,'imie'=>$imie,'nazwisko'=>$nazwisko,'login'=>$login,'uprawnienie'=>$mapped];
            }
        }
    } catch (\Throwable $e) {
        // ignore DB errors but log
        error_log('discord callback DB error: ' . $e->getMessage());
    }
}

// create JWT HS256 manually
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
$header = ['alg'=>'HS256','typ'=>'JWT'];
$payload = ['id'=> $user['id'] ?? null, 'login'=> $user['login'] ?? ('discord_'.$discordId), 'uprawnienie' => $user['uprawnienie'] ?? $mapped, 'iat'=>time(), 'exp'=>time()+8*3600];
$header_b = base64url_encode(json_encode($header));
$payload_b = base64url_encode(json_encode($payload));
$sig = hash_hmac('sha256', $header_b . '.' . $payload_b, $jwtSecret, true);
$sig_b = base64url_encode($sig);
$jwt = $header_b . '.' . $payload_b . '.' . $sig_b;

// redirect to panel with token
$redirectTo = rtrim($main, '/') . '/panel/index.php?token=' . urlencode($jwt);
header('Location: ' . $redirectTo);
exit;
?>
