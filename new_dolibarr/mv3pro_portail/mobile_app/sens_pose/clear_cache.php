<?php
/**
 * Clear PHP Cache (OPcache)
 */

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>';
echo '<html><head><meta charset="utf-8"><title>Clear Cache</title></head><body>';
echo '<h1>Clear PHP Cache</h1>';

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo '<p style="color: green; font-weight: bold;">✓ OPcache cleared successfully!</p>';
    } else {
        echo '<p style="color: red;">✗ Failed to clear OPcache</p>';
    }
} else {
    echo '<p style="color: orange;">OPcache is not enabled on this server</p>';
}

if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo '<p style="color: green;">✓ APC cache cleared</p>';
}

echo '<h2>Cache Info</h2>';
echo '<ul>';
echo '<li><strong>OPcache enabled:</strong> '.(function_exists('opcache_get_status') ? 'YES' : 'NO').'</li>';

if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    if ($status) {
        echo '<li><strong>OPcache status:</strong> '.($status['opcache_enabled'] ? 'ENABLED' : 'DISABLED').'</li>';
        echo '<li><strong>Cache full:</strong> '.($status['cache_full'] ? 'YES' : 'NO').'</li>';
    }
}

echo '</ul>';

echo '<h2>Next Steps</h2>';
echo '<ol>';
echo '<li><a href="view.php?id=37">Test view.php (should show v2.1 debug)</a></li>';
echo '<li><a href="test_url_construction.php">Test URL construction</a></li>';
echo '</ol>';

echo '</body></html>';
