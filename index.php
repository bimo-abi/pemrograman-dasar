<?php
require_once 'includes/functions.php';

// Load semua data
$transactions = loadData('transactions');
$accounts = loadData('accounts');
$goals = loadData('goals');
$categories = loadData('categories');

// Hitung ringkasan bulan ini
$today = new DateTime();
$monthStart = (new DateTime('first day of this month'))->format('Y-m-d');
$monthEnd = (new DateTime('last day of this month'))->format('Y-m-d');

$income = $expense = 0;
$catTotals = [];
foreach ($transactions as $t) {
    if ($t['date'] >= $monthStart && $t['date'] <= $monthEnd) {
        $amount = $t['amount'];
        if ($t['type'] === 'income') {
            $income += $amount;
        } else {
            $expense += $amount;
            $cat = $t['category'] ?? 'Lainnya';
            $catTotals[$cat] = ($catTotals[$cat] ?? 0) + $amount;
        }
    }
}
$balance = $income - $expense;

// Ambil 5 transaksi terbaru
$recent = array_slice($transactions, -5);
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <h1>Selamat Datang di BudgetKu 💰</h1>

    <!-- Ringkasan Bulanan -->
    <section class="summary-grid">
        <div class="card">
            <h3>Pemasukan</h3>
            <p class="value income"><?= rupiah($income) ?></p>
        </div>
        <div class="card">
            <h3>Pengeluaran</h3>
            <p class="value expense"><?= rupiah($expense) ?></p>
        </div>
        <div class="card">
            <h3>Saldo</h3>
            <p class="value <?= $balance >= 0 ? 'positive' : 'negative' ?>">
                <?= rupiah($balance) ?>
            </p>
        </div>
        <div class="card">
            <h3>Akun</h3>
            <p class="value"><?= count($accounts) ?></p>
            <p class="caption">Total saldo: <?= rupiah(array_sum(array_column($accounts, 'balance'))) ?></p>
        </div>
    </section>

    <!-- Quick Add -->
    <section class="transaction-form">
        <h2>➕ Tambah Transaksi Cepat</h2>
        <form action="add-transaction.php" method="POST">
            <input type="hidden" name="quick" value="1">
            <div class="form-group">
                <label>Jumlah</label>
                <input type="number" step="0.01" name="amount" placeholder="Contoh: 25000" required>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <input type="text" name="description" placeholder="Contoh: Beli kopi" required>
            </div>
            <div class="form-group">
                <label>Tipe</label>
                <select name="type" required>
                    <option value="expense">Pengeluaran</option>
                    <option value="income">Pemasukan</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </section>

    <!-- Goals -->
    <?php if (!empty($goals)): ?>
        <section>
            <h2>🎯 Target Tabungan</h2>
            <?php foreach ($goals as $g):
                $progress = $g['target_amount'] > 0 ? min(100, ($g['current_amount'] / $g['target_amount']) * 100) : 0;
                $remaining = $g['target_amount'] - $g['current_amount'];
            ?>
                <div class="goal-item">
                    <div class="goal-header">
                        <div>
                            <h3><?= htmlspecialchars($g['name']) ?></h3>
                            <p><?= !empty($g['description']) ? htmlspecialchars($g['description']) : '—' ?></p>
                        </div>
                        <span><?= round($progress) ?>%</span>
                    </div>
                    <div class="goal-progress">
                        <div class="goal-progress-bar" style="width: <?= round($progress) ?>%"></div>
                    </div>
                    <div>
                        <strong><?= rupiah($g['current_amount']) ?></strong> / <?= rupiah($g['target_amount']) ?>
                        <?php if ($remaining > 0): ?>
                            <span class="caption">Sisa: <?= rupiah($remaining) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <a href="goals.php" class="btn btn-secondary" style="display: inline-block; margin-top: 12px;">Kelola Goals</a>
        </section>
    <?php endif; ?>

    <!-- Transaksi Terbaru -->
    <?php if (!empty($recent)): ?>
        <section>
            <h2>📋 5 Transaksi Terbaru</h2>
            <ul class="transaction-list">
                <?php foreach (array_reverse($recent) as $t): ?>
                    <li class="transaction-item">
                        <div class="transaction-desc">
                            <h4><?= htmlspecialchars($t['description']) ?></h4>
                            <p><?= $t['date'] ?> • <?= htmlspecialchars($t['category'] ?? '—') ?></p>
                        </div>
                        <div class="transaction-amount <?= $t['type'] === 'income' ? 'income' : 'expense' ?>">
                            <?= $t['type'] === 'income' ? '+' : '–' ?><?= rupiah($t['amount']) ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a href="transactions.php" class="btn btn-secondary">Lihat Semua</a>
        </section>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>