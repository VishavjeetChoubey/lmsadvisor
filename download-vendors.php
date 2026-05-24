#!/usr/bin/env php
<?php
/**
 * LMSAdvisor — Vendor Downloader
 *
 * Downloads all CDN dependencies to /public/assets/vendor/ for offline use.
 *
 * Usage (from project root):
 *   php download-vendors.php
 *
 * Requires: PHP with allow_url_fopen = On (default on XAMPP)
 */

$vendorDir = __DIR__ . '/public/assets/vendor';

$files = [
    // Bootstrap CSS + JS
    'bootstrap/bootstrap.min.css'        => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'bootstrap/bootstrap.bundle.min.js'  => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',

    // Bootstrap Icons (CSS only — fonts loaded via CSS @font-face from CDN)
    'bootstrap-icons/bootstrap-icons.min.css' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',

    // jQuery
    'jquery/jquery.min.js'               => 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js',

    // SortableJS
    'sortablejs/Sortable.min.js'         => 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js',

    // Quill editor
    'quill/quill.snow.css'               => 'https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css',
    'quill/quill.js'             => 'https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js',

    // Chart.js
    'chartjs/chart.umd.min.js'           => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js',

    // Font Awesome (CSS — webfonts still load from CDN)
    'fontawesome/css/all.min.css'        => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',

    // Plyr video player
    'plyr/plyr.css'                      => 'https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.css',
    'plyr/plyr.min.js'                   => 'https://cdn.jsdelivr.net/npm/plyr@3.7.8/dist/plyr.min.js',

    // Tom Select
    'tom-select/tom-select.complete.min.css' => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css',
    'tom-select/tom-select.complete.min.js'  => 'https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js',

    // FullCalendar
    'fullcalendar/index.global.min.js'   => 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js',
];

$ok   = 0;
$fail = 0;

foreach ($files as $dest => $src) {
    $path = $vendorDir . '/' . $dest;
    $dir  = dirname($path);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (file_exists($path)) {
        echo "\033[33mSKIP\033[0m  $dest (already exists)\n";
        $ok++;
        continue;
    }

    echo "GET   $src\n      → $dest … ";
    flush();

    $ctx  = stream_context_create(['http' => ['timeout' => 30, 'user_agent' => 'LMSAdvisor-Downloader/1.0']]);
    $data = @file_get_contents($src, false, $ctx);

    if ($data === false || strlen($data) < 100) {
        echo "\033[31mFAIL\033[0m\n";
        $fail++;
    } else {
        file_put_contents($path, $data);
        echo "\033[32mOK\033[0m (" . round(strlen($data) / 1024) . " KB)\n";
        $ok++;
    }
}

echo "\n";
echo "\033[32m$ok file(s) ready.\033[0m";
if ($fail > 0) echo " \033[31m$fail failed — check internet connection.\033[0m";
echo "\n\nNOTE: Font Awesome webfonts and Bootstrap Icons fonts still load from CDN.\n";
echo "For a fully offline setup, download the full zip releases manually.\n";
