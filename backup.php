<?php
require_once 'includes/functions.php';

$message = '';
$error = '';

// Handle ekspor
if (isset($_GET['export']) && in_array($_GET['export'], ['json', 'csv'])) {
    $type = $_GET['export'];
    $userId = getUserId();

    // Muat semua data
    $data = [
        'transactions' => loadData('transactions'),
        'categories' => loadData('categories'),
        'accounts' => loadData('accounts'),
        'goals' => loadData('goals'),
        'exported_at' => date('Y-m-d H:i:s'),
        'user_id' => $userId
    ];

    $filename = "budgetku_backup_" . date('Ymd_His');

    if ($type === 'json') {
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        header('Content-Type: application/json');
        header("Content-Disposition: attachment; filename={$filename}.json");
        echo $content;
        exit;
    } elseif ($type === 'csv') {
        // Ekspor transaksi ke CSV (yang paling relevan untuk analisis)
        $transactions = $data['transactions'];
        if (empty($transactions)) {
            $error = "Tidak ada transaksi untuk diekspor.";
        } else {
            header('Content-Type: text/csv');
            header("Content-Disposition: attachment; filename={$filename}_transactions.csv");

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Tanggal', 'Tipe', 'Jumlah', 'Deskripsi', 'Kategori', 'Akun']);

            foreach ($transactions as $t) {
                fputcsv($output, [
                    $t['date'],
                    $t['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran',
                    $t['amount'],
                    $t['description'],
                    $t['category'] ?? '—',
                    $t['account'] ?? '—'
                ]);
            }
            fclose($output);
            exit;
        }
    }
}

// Handle impor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Error upload file.";
    } elseif (!in_array($file['type'], ['application/json', 'text/csv', 'application/octet-stream'])) {
        $error = "Hanya file .json atau .csv yang diizinkan.";
    } else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $content = file_get_contents($file['tmp_name']);

        if ($ext === 'json') {
            $import = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = "File JSON tidak valid.";
            } else {
                // ✅ FILE HANDLING: simpan ke file masing-masing
                $types = ['transactions', 'categories', 'accounts', 'goals'];
                $successCount = 0;
                foreach ($types as $t) {
                    if (isset($import[$t]) && is_array($import[$t])) {
                        saveData($t, $import[$t]);
                        $successCount++;
                    }
                }
                $message = "Berhasil mengimpor $successCount data dari backup.";
            }
        } elseif ($ext === 'csv') {
            // Impor CSV → transaksi baru
            $rows = array_map('str_getcsv', explode("\n", $content));
            if (count($rows) < 2) {
                $error = "File CSV kosong atau tidak valid.";
            } else {
                array_shift($rows); // hapus header
                $newTrans = [];
                foreach ($rows as $row) {
                    if (count($row) < 6) continue;
                    $type = trim($row[1]) === 'Pemasukan' ? 'income' : 'expense';
                    $amount = floatval(str_replace(',', '.', $row[2]));
                    if ($amount <= 0) continue;

                    $newTrans[] = [
                        'id' => 't_' . bin2hex(random_bytes(6)),
                        'date' => $row[0],
                        'type' => $type,
                        'amount' => $amount,
                        'description' => $row[3],
                        'category' => $row[4] ?? 'Lainnya',
                        'account' => $row[5] ?? 'Kas'
                    ];
                }

                if (!empty($newTrans)) {
                    $existing = loadData('transactions');
                    $existing = array_merge($existing, $newTrans);
                    saveData('transactions', $existing);
                    $message = "Berhasil mengimpor " . count($newTrans) . " transaksi dari CSV.";
                } else {
                    $error = "Tidak ada data valid dalam CSV.";
                }
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <h1>⚙️ Backup & Restore</h1>

    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <section class="transaction-form">
        <h2>📤 Ekspor Data</h2>
        <p>Simpan data ke file lokal sebagai cadangan.</p>
        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 16px;">
            <a href="?export=json" class="btn btn-primary">Ekspor JSON (Semua Data)</a>
            <a href="?export=csv" class="btn btn-secondary">Ekspor CSV (Transaksi Saja)</a>
        </div>
        <p class="caption" style="margin-top: 12px;">
            🔹 JSON: semua data (transaksi, kategori, akun, goal) — bisa diimpor kembali.<br>
            🔹 CSV: hanya transaksi — cocok untuk analisis di Excel/Google Sheets.
        </p>
    </section>

    <section class="transaction-form" style="margin-top: 32px;">
        <h2>📥 Impor Data</h2>
        <p>Pulihkan data dari file backup (.json atau .csv).</p>
        <form method="POST" enctype="multipart/form-data" style="margin-top: 16px;">
            <div class="form-group">
                <label>Pilih File</label>
                <input type="file" name="import_file" accept=".json,.csv" required>
            </div>
            <button type="submit" class="btn btn-primary">Impor Sekarang</button>
        </form>
        <p class="caption" style="margin-top: 12px;">
            ⚠️ Impor JSON akan **menimpa** data saat ini. Pastikan file berasal dari BudgetKu.
        </p>
    </section>

    <div class="card" style="margin-top: 32px; background: #fff8e1; border-left: 4px solid #FFC107;">
        <h3>💡 Tips</h3>
        <ul>
            <li>Lakukan backup rutin sebelum membersihkan data.</li>
            <li>File JSON bisa dipakai untuk pindah perangkat (copy ke folder <code>data/</code> dengan nama sesuai user ID).</li>
            <li>Impor CSV hanya menambah transaksi, tidak mengubah kategori/akun.</li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>