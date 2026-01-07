<?php
// MVC entrypoint for /linie — uses controller + view
require_once __DIR__ . '/panel/app/core/Database.php';
require_once __DIR__ . '/panel/app/controllers/LinesController.php';

use App\Core\Database;
use App\Controllers\LinesController;

$db = new Database();
$linesController = new LinesController($db);

$grouped = $linesController->getGroupedLines();

// Sort numeric/alpha per group for stable UI
foreach ($grouped as $t => &$arr) {
    uksort($arr, function($a, $b) {
        preg_match('/^(\d+)/', $a, $aNum);
        preg_match('/^(\d+)/', $b, $bNum);
        if (!empty($aNum) && !empty($bNum)) {
            return (int)$aNum[1] <=> (int)$bNum[1];
        }
        return strnatcasecmp($a, $b);
    });
}
unset($arr);

$typeLabels = [
    'tram' => 'Tramwaj',
    'trol' => 'Trolejbus',
    'bus'  => 'Autobus'
];

$year = date('Y');

// Render view
require __DIR__ . '/panel/app/views/lines.php';
