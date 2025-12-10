<?php
// prosty front-controller MVC dla panelu — routes: /panel/?route=home|login|logout|dashboard
session_start();

require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/controllers/HomeController.php';

$db = new App\Core\Database(); // initialises PDO (reads env)
$controller = new App\Controllers\HomeController($db);

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
// default: show login page
$controller->index();
exit;
?>
