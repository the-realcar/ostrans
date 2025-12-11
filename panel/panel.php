<?php
// Serve the JavaScript file via PHP so pages can load /panel/panel.php
header('Content-Type: application/javascript; charset=utf-8');
$jsPath = __DIR__ . '/panel.js';
if (file_exists($jsPath)) {
    echo file_get_contents($jsPath);
} else {
    // graceful fallback: minimal script to avoid console errors
    echo "console.error('panel.js not found on server');";
}
