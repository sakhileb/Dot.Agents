<?php
/**
 * One-time deployment cache-clear script for shared hosting (cPanel).
 *
 * Usage:  https://agents.infodot.co.za/clear-cache.php?token=DEPLOY_CLEAR_2026
 * Delete this file from the server after use.
 */

define('SECRET', 'DEPLOY_CLEAR_2026');

if (($_GET['token'] ?? '') !== SECRET) {
    http_response_code(403);
    exit('Forbidden.');
}

// Try to locate the project root regardless of where public/ sits
$basePath = dirname(__DIR__);

// On cPanel the storage dir is sometimes symlinked; realpath() follows it.
$storageBase = realpath($basePath . '/storage') ?: ($basePath . '/storage');

$cleared = [];
$errors  = [];

// Helper: delete all files in a directory (non-recursive)
function clearDir(string $dir, array &$cleared, array &$errors): void
{
    if (!is_dir($dir)) return;
    foreach (glob($dir . '/*') as $file) {
        if (is_file($file)) {
            @unlink($file) ? $cleared[] = basename($file) : $errors[] = basename($file);
        }
    }
}

// 1. Compiled Blade views  (main cause of toJSON 500)
clearDir($storageBase . '/framework/views', $cleared, $errors);

// 2. Config cache
foreach ([
    $basePath . '/bootstrap/cache/config.php',
    $basePath . '/bootstrap/cache/routes-v7.php',
    $basePath . '/bootstrap/cache/events.php',
    $basePath . '/bootstrap/cache/services.php',
    $basePath . '/bootstrap/cache/packages.php',
] as $f) {
    if (file_exists($f)) {
        @unlink($f) ? $cleared[] = basename($f) : $errors[] = basename($f);
    }
}

// 3. Application cache files
clearDir($storageBase . '/framework/cache/data', $cleared, $errors);

header('Content-Type: text/plain; charset=utf-8');
echo "=== Dot.Agents Cache Clear ===\n\n";
echo "Project root : $basePath\n";
echo "Storage path : $storageBase\n\n";
echo "Cleared (" . count($cleared) . "):\n";
foreach ($cleared as $f) echo "  + $f\n";
if ($errors) {
    echo "\nFailed (" . count($errors) . "):\n";
    foreach ($errors as $f) echo "  x $f\n";
}
echo "\nDone. Delete this file now: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
