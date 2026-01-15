<?php
/**
 * Lines Listing Page - PPUT Ostrans
 * Fetches data from external API (sil.kanbeq.me/ostrans)
 */

// Fetch lines data from external API
$apiUrl = 'https://sil.kanbeq.me/ostrans/lines';
$linesData = @file_get_contents($apiUrl);
$allLines = [];

if ($linesData) {
    $decoded = json_decode($linesData, true);
    $allLines = $decoded['lines'] ?? $decoded ?? [];
}

// Group lines by type
$grouped = [];
foreach ($allLines as $line) {
    $type = $line['type'] ?? 'bus';
    $lineNum = $line['line'] ?? '';
    $variant = $line['variant'] ?? '01';
    
    if (!isset($grouped[$type])) {
        $grouped[$type] = [];
    }
    if ($lineNum) {
        if (!isset($grouped[$type][$lineNum])) {
            $grouped[$type][$lineNum] = [];
        }
        // Store variant information
        $grouped[$type][$lineNum][] = [
            'variant' => $variant,
            'from' => $line['from'] ?? '',
            'to' => $line['to'] ?? '',
            'route' => $line['route'] ?? ''
        ];
    }
}
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
