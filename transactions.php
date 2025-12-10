<?php
session_start();
require_once 'includes/functions.php';

$transactions = loadData('transactions');
$categories = loadData('categories');
$accounts = loadData('accounts');

// Filter berdasarkan kategori/tipe/bulan
$filter_cat = $_GET['category'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_month = $_GET['month'] ?? date('Y-m');

// Filter data
$filtered = $transactions;
if ($filter_cat) {
    $filtered = array_filter($filtered, fn($t) => ($t['category'] ?? '') === $filter_cat);
}
if ($filter_type) {
    $filtered = array_filter($filtered, fn($t) => ($t['type'] ?? '') === $filter_type);
}
if ($filter_month) {
    $filtered = array_filter($filtered, fn($t) => substr($t['date'], 0, 7) === $filter_month);
}

// Urutkan terbaru di atas
usort($filtered, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));

// Handle hapus
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = $_GET['delete'];
    $found = false;

    // Cari & hapus
    foreach ($transactions as $i => $t) {
        if ($t['id'] === $id) {
            $deleted = $t;
            unset($transactions[$i]);
            $found = true;
            break;
        }
    }

    if ($found) {
        //LOGIKA BISNIS: kembalikan saldo akun
        $updatedAccounts = [];
        foreach ($accounts as $acc) {
            if ($acc['name'] === $deleted['account']) {
                $acc['balance'] -= ($deleted['type'] === 'income' ? $deleted['amount'] : -$deleted['amount']);
            }
            $updatedAccounts[] = $acc;
        }
        saveData('accounts', $updatedAccounts);
        saveData('transactions', array_values($transactions)); // reset index

        header("Location: transactions.php?deleted=1");
        exit;
    } else {
        $_SESSION['flash'] = ['success' => 'Transaksi berhasil dihapus!'];
        header("Location: transactions.php");
        exit;
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h1>Daftar Transaksi</h1>
        <a href="add-transaction.php" class="btn btn-primary">➕Tambah Baru</a>
    </div>

    <!-- Filter -->
    <div class="transaction-form">
        <h3>Filter</h3>
        <form method="GET" class="form-section" style="padding: 0;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label>Bulan</label>
                    <input type="month" name="month" value="<?= htmlspecialchars($filter_month) ?>">
                </div>
                <div class="form-group">
                    <label>Tipe</label>
                    <select name="type">
                        <option value="">Semua</option>
                        <option value="income" <?= $filter_type === 'income' ? 'selected' : '' ?>>Pemasukan</option>
                        <option value="expense" <?= $filter_type === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Kategori</label>
                    <select name="category">
                        <option value="">Semua</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $filter_cat === $cat['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-secondary">Terapkan Filter</button>
            <?php if ($filter_cat || $filter_type || $filter_month !== date('Y-m')): ?>
                <a href="transactions.php" class="btn btn-secondary" style="margin-left: 8px;">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Daftar transaksi -->
    <?php if (empty($filtered)): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <p>Tidak ada transaksi.</p>
            <a href="add-transaction.php" class="btn btn-primary" style="margin-top: 16px;">Tambah Transaksi Pertama</a>
        </div>
    <?php else: ?>
        <p><strong><?= count($filtered) ?></strong> transaksi ditemukan.</p>
        <ul class="transaction-list">
            <?php foreach ($filtered as $t): ?>
                <li class="transaction-item">
                    <div class="transaction-desc">
                        <h4><?= htmlspecialchars($t['description']) ?></h4>
                        <p>
                            <strong><?= htmlspecialchars($t['category'] ?? '—') ?></strong> •
                            <?= $t['account'] ?? '—' ?> •
                            <?= $t['date'] ?>
                        </p>
                    </div>
                    <div class="transaction-amount <?= $t['type'] === 'income' ? 'income' : 'expense' ?>">
                        <?= $t['type'] === 'income' ? '+' : '–' ?><?= rupiah($t['amount']) ?>
                    </div>
                    <div class="transaction-actions">
                        <!-- Edit (opsional — bisa dikembangkan nanti) -->
                        <!-- <a href="#" class="btn btn-secondary">Edit</a> -->
                        <a href="?delete=<?= urlencode($t['id']) ?>"
                            class="btn btn-danger"
                            onclick="return confirm('Yakin hapus transaksi ini? Saldo akun akan disesuaikan.')">Hapus</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>