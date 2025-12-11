<?php
session_start();
require_once 'includes/functions.php';

$errors = [];
$formData = [
    'date' => date('Y-m-d'),
    'type' => 'expense',
    'amount' => '',
    'description' => '',
    'category' => '',
    'account' => ''
];

// Load data untuk dropdown
$categories = loadData('categories');
$accounts = loadData('accounts');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitasi input awal
    $input = [
        'date' => trim($_POST['date'] ?? ''),
        'type' => $_POST['type'] ?? 'expense',
        'amount' => trim($_POST['amount'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category' => trim($_POST['category'] ?? ''),
        'account' => trim($_POST['account'] ?? '')
    ];

    // VALIDASI SERVER-SIDE pakai filter_var()
    $amount = filter_var($input['amount'], FILTER_VALIDATE_FLOAT);
    $date = filter_var($input['date'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $desc = filter_var($input['description'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Cek validasi
    if ($amount === false || $amount <= 0) {
        $errors[] = "Jumlah harus angka positif.";
    }
    if (empty($desc)) {
        $errors[] = "Deskripsi wajib diisi.";
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors[] = "Format tanggal harus YYYY-MM-DD.";
    }
    if (empty($input['category'])) {
        $errors[] = "Pilih kategori.";
    }
    if (empty($input['account'])) {
        $errors[] = "Pilih akun.";
    }

    // Jika tidak ada error → proses simpan
    if (empty($errors)) {
        $newTrans = [
            'id' => 't_' . bin2hex(random_bytes(6)),
            'date' => $date,
            'type' => $input['type'],
            'amount' => $amount,
            'description' => $desc,
            'category' => $input['category'],
            'account' => $input['account']
        ];

        // FILE HANDLING: muat → tambah → simpan
        $transactions = loadData('transactions');
        $transactions[] = $newTrans;
        saveData('transactions', $transactions);

        // LOGIKA BISNIS: update saldo akun terkait
        $updatedAccounts = [];
        foreach ($accounts as $acc) {
            if ($acc['name'] === $input['account']) {
                $acc['balance'] += ($input['type'] === 'income' ? $amount : -$amount);
            }
            $updatedAccounts[] = $acc;
        }
        saveData('accounts', $updatedAccounts);

        // LOGIKA BISNIS: auto-kategorisasi berbasis keyword (nilai tambah!)
        $keywords = [
            'Transport' => ['gojek', 'grab', 'ojek', 'taksi', 'bengkel', 'bensin', 'pom', 'shell', 'pertamina'],
            'Makan' => ['warung', 'resto', 'kopi', 'starbuck', 'mcd', 'burger', 'nasi', 'mie', 'soto', 'bakso'],
            'Hiburan' => ['netflix', 'spotify', 'bioskop', 'film', 'game', 'steam', 'playstation'],
            'Kesehatan' => ['apotek', 'rs', 'rumah sakit', 'dokter', 'obat', 'klinik']
        ];
        foreach ($keywords as $cat => $words) {
            foreach ($words as $word) {
                if (stripos($desc, $word) !== false) {
                    $newTrans['category'] = $cat;
                    // Update transaksi terakhir di array
                    $transactions[count($transactions) - 1]['category'] = $cat;
                    saveData('transactions', $transactions);
                    break 2;
                }
            }
        }

        // Redirect agar tidak double-submit
        $_SESSION['flash'] = ['success' => 'Transaksi berhasil ditambahkan!'];
        header("Location: index.php");
        exit;
    } else {
        $formData = $input;
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <a href="index.php" class="btn btn-secondary" style="display: inline-block; margin-bottom: 16px;">&larr; Kembali</a>

    <h1>Tambah Transaksi</h1>

    <form method="POST" class="transaction-form">
        <div class="form-group">
            <label>Tipe Transaksi</label>
            <select name="type" required>
                <option value="expense" <?= $formData['type'] === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
                <option value="income" <?= $formData['type'] === 'income' ? 'selected' : '' ?>>Pemasukan</option>
            </select>
        </div>

        <div class="form-group">
            <label>Tanggal</label>
            <input type="date" name="date" value="<?= htmlspecialchars($formData['date']) ?>" required>
        </div>

        <div class="form-group">
            <label>Jumlah (Rp)</label>
            <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars($formData['amount']) ?>" placeholder="Contoh: 25000" required>
        </div>

        <div class="form-group">
            <label>Deskripsi</label>
            <input type="text" name="description" value="<?= htmlspecialchars($formData['description']) ?>" placeholder="Contoh: Beli kopi di Starbuck" required>
        </div>

        <div class="form-group">
            <label>Kategori</label>
            <select name="category" required>
                <option value="">— Pilih Kategori —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $formData['category'] === $cat['name'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="caption" style="margin-top: 4px;">Deskripsi seperti "Gojek ke kantor" akan otomatis dikategorikan sebagai <strong>Transport</strong>.</p>
        </div>

        <div class="form-group">
            <label>Akun</label>
            <select name="account" required>
                <option value="">— Pilih Akun —</option>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= htmlspecialchars($acc['name']) ?>" <?= $formData['account'] === $acc['name'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($acc['name']) ?> (<?= rupiah($acc['balance']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>