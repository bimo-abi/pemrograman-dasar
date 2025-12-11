<?php
function getUserId() {
    if (isset($_COOKIE['user_id']) && preg_match('/^user_[a-z0-9]{8}$/', $_COOKIE['user_id'])) {
        return $_COOKIE['user_id'];
    }
    $id = 'user_' . substr(bin2hex(random_bytes(4)), 0, 8);
    setcookie('user_id', $id, [
        'expires' => time() + 365*24*3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    return $id;
}

function loadData($type) {
    $userId = getUserId();
    $file = __DIR__ . "/../data/{$type}_{$userId}.json";

    $defaults = [
        'accounts' => [['id' => 'acc_1', 'name' => 'Kas', 'balance' => 0]],
        'categories' => [
            ['id' => 'cat_1', 'name' => 'Makan', 'type' => 'expense'],
            ['id' => 'cat_2', 'name' => 'Transport', 'type' => 'expense'],
            ['id' => 'cat_3', 'name' => 'liburan', 'type' => 'expense'],
            ['id' => 'cat_4', 'name' => 'Penghasilan', 'type' => 'income']
        ],
        'goals' => [],
        'transactions' => []
    ];

    // Jika file belum ada → buat otomatis
    if (!file_exists($file)) {
        $data = $defaults[$type] ?? [];
        saveData($type, $data);
        return $data;
    }

    // Baca file dengan error handling
    $content = @file_get_contents($file);
    if ($content === false) {
        error_log("Gagal membaca file: $file");
        return $defaults[$type] ?? [];
    }

    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON error di $file: " . json_last_error_msg());
        return $defaults[$type] ?? [];
    }

    return is_array($data) ? $data : ($defaults[$type] ?? []);
}

function saveData($type, $data) {
    $userId = getUserId();
    $file = __DIR__ . "/../data/{$type}_{$userId}.json";

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        error_log("Gagal encode JSON untuk $type");
        return false;
    }

    $result = file_put_contents($file, $json);
    if ($result === false) {
        error_log("Gagal menulis file: $file");
        return false;
    }

    return true;
}

// Helper: format Rupiah
function rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
