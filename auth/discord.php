<?php
// Redirect to Discord OAuth authorize URL
$root = __DIR__ . '/..';
if (file_exists($root . '/.env')) {
    foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
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
$clientId = getenv('DISCORD_CLIENT_ID') ?: ($_ENV['DISCORD_CLIENT_ID'] ?? null);
$redirectUri = getenv('DISCORD_REDIRECT_URI') ?: ($_ENV['DISCORD_REDIRECT_URI'] ?? null);
$main = getenv('MAIN_SITE') ?: ($_ENV['MAIN_SITE'] ?? 'https://ostrans.famisska.pl');

if (!$clientId) {
    http_response_code(500);
    echo "DISCORD_CLIENT_ID not configured";
    exit;
}
if (!$redirectUri) {
    $redirectUri = rtrim($main, '/') . '/auth/discord/callback';
}
$params = [
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'identify email'
];
$auth = 'https://discord.com/api/oauth2/authorize?' . http_build_query($params);
header('Location: ' . $auth);
exit;
?>
