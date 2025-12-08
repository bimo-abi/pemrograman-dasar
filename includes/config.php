<?php
// includes/config.php
// Konfigurasi dasar aplikasi

// Pastikan folder data ada
if (!is_dir(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0755, true);
}

// Helper: redirect dengan pesan flash (opsional)
function redirect($url, $params = []) {
    $query = http_build_query($params);
    $url .= $query ? (strpos($url, '?') ? '&' : '?') . $query : '';
    header("Location: $url");
    exit;
}