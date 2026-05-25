<?php
// Run this once to clear PHP OPcache after updating files
// Then DELETE this file immediately — do not leave it on production
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OPcache cleared successfully.';
} else {
    echo 'OPcache not enabled (safe to ignore).';
}
echo ' Done. Delete this file now.';
