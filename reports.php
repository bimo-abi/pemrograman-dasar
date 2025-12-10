<?php
require_once 'includes/functions.php';

$transactions = loadData('transactions');
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

// Hitung ringkasan per bulan
$monthly = [];
$catTotals = [];
$incomeTotal = $expenseTotal = 0;

foreach ($transactions as $t) {
    $y = substr($t['date'], 0, 4);
    $m = substr($t['date'], 5, 2);
    $key = "$y-$m";

    if (!isset($monthly[$key])) {
        $monthly[$key] = ['income' => 0, 'expense' => 0];
    }

    $amount = $t['amount'];
    if ($t['type'] === 'income') {
        $monthly[$key]['income'] += $amount;
        $incomeTotal += $amount;
    } else {
        $monthly[$key]['expense'] += $amount;
        $expenseTotal += $amount;
        $cat = $t['category'] ?? 'Lainnya';
        $catTotals[$cat] = ($catTotals[$cat] ?? 0) + $amount;
    }
}

// Urutkan bulan terbaru di atas
krsort($monthly);

// Filter bulan ini
$currentKey = "$year-$month";
$current = $monthly[$currentKey] ?? ['income' => 0, 'expense' => 0];
$balance = $current['income'] - $current['expense'];

// Siapkan data untuk Chart.js
$catLabels = array_keys($catTotals);
$catValues = array_values($catTotals);

// Ambil 6 bulan terakhir untuk bar chart
$last6Months = array_slice(array_keys($monthly), 0, 6);
$barLabels = [];
$barIncome = [];
$barExpense = [];
foreach ($last6Months as $key) {
    $m = DateTime::createFromFormat('Y-m', $key);
    $barLabels[] = $m->format('M Y');
    $barIncome[] = $monthly[$key]['income'];
    $barExpense[] = $monthly[$key]['expense'];
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h1>📊 Laporan Keuangan</h1>
        <form method="GET" style="display: flex; gap: 12px; align-items: center;">
            <select name="year">
                <?php for ($y = 2023; $y <= date('Y'); $y++): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <select name="month">
                <?php
                $months = [
                    '01' => 'Jan',
                    '02' => 'Feb',
                    '03' => 'Mar',
                    '04' => 'Apr',
                    '05' => 'Mei',
                    '06' => 'Jun',
                    '07' => 'Jul',
                    '08' => 'Agu',
                    '09' => 'Sep',
                    '10' => 'Okt',
                    '11' => 'Nov',
                    '12' => 'Des'
                ];
                foreach ($months as $num => $name):
                ?>
                    <option value="<?= $num ?>" <?= $month == $num ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Tampilkan</button>
        </form>
    </div>

    <!-- Ringkasan Bulan Ini -->
    <section class="summary-grid">
        <div class="card">
            <h3>Pemasukan</h3>
            <p class="value income"><?= rupiah($current['income']) ?></p>
        </div>
        <div class="card">
            <h3>Pengeluaran</h3>
            <p class="value expense"><?= rupiah($current['expense']) ?></p>
        </div>
        <div class="card">
            <h3>Saldo</h3>
            <p class="value <?= $balance >= 0 ? 'positive' : 'negative' ?>">
                <?= rupiah($balance) ?>
            </p>
        </div>
        <div class="card">
            <h3>Net Year-to-Date</h3>
            <p class="value <?= ($incomeTotal - $expenseTotal) >= 0 ? 'positive' : 'negative' ?>">
                <?= rupiah($incomeTotal - $expenseTotal) ?>
            </p>
        </div>
    </section>

    <!-- Grafik 1: Pengeluaran per Kategori (Pie) -->
    <section>
        <h2>🎯 Distribusi Pengeluaran (<?= DateTime::createFromFormat('!m', $month)->format('F') ?> <?= $year ?>)</h2>
        <?php if (!empty($catTotals)): ?>
            <div style="height: 300px; background: white; border-radius: 12px; padding: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <canvas id="categoryChart"></canvas>
            </div>
        <?php else: ?>
            <p class="card" style="text-align: center; padding: 32px;">Belum ada pengeluaran di bulan ini.</p>
        <?php endif; ?>
    </section>

    <!-- Grafik 2: Perbandingan Bulanan (Bar) -->
    <section>
        <h2>📈 Aktivitas 6 Bulan Terakhir</h2>
        <div style="height: 350px; background: white; border-radius: 12px; padding: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <canvas id="monthlyChart"></canvas>
        </div>
    </section>

    <!-- Detail Transaksi Bulan Ini -->
    <section>
        <h2>Detail Transaksi Bulan Ini</h2>
        <ul class="transaction-list">
            <?php
            $thisMonthTrans = array_filter($transactions, fn($t) => substr($t['date'], 0, 7) === "$year-$month");
            usort($thisMonthTrans, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));

            foreach ($thisMonthTrans as $t):
            ?>
                <li class="transaction-item">
                    <div class="transaction-desc">
                        <h4><?= htmlspecialchars($t['description']) ?></h4>
                        <p><?= $t['category'] ?? '—' ?> • <?= $t['date'] ?></p>
                    </div>
                    <div class="transaction-amount <?= $t['type'] === 'income' ? 'income' : 'expense' ?>">
                        <?= $t['type'] === 'income' ? '+' : '–' ?><?= rupiah($t['amount']) ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if (empty($thisMonthTrans)): ?>
            <p class="card" style="text-align: center; padding: 24px;">Tidak ada transaksi di bulan ini.</p>
        <?php endif; ?>
    </section>
</div>

<!-- Chart.js Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Pie Chart: Kategori
        <?php if (!empty($catLabels)): ?>
            const ctx1 = document.getElementById('categoryChart').getContext('2d');
            new Chart(ctx1, {
                type: 'pie',
                data: {
                    labels: <?= json_encode($catLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($catValues) ?>,
                        backgroundColor: [
                            '#4CAF50', '#81C784', '#388E3C', '#66BB6A',
                            '#FF7043', '#FFA726', '#26A69A', '#7E57C2'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        <?php endif; ?>
        // ✅ Bar Chart: 6 Bulan Terakhir
        <?php if (!empty($barLabels)): ?>
            const ctx2 = document.getElementById('monthlyChart').getContext('2d');
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($barLabels) ?>,
                    datasets: [{
                            label: 'Pemasukan',
                            data: <?= json_encode($barIncome) ?>,
                            backgroundColor: '#2E7D32',
                            borderRadius: 4,
                            borderSkipped: false
                        },
                        {
                            label: 'Pengeluaran',
                            data: <?= json_encode($barExpense) ?>,
                            backgroundColor: '#D32F2F',
                            borderRadius: 4,
                            borderSkipped: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        <?php endif; ?>
    });
</script>

<?php include 'includes/footer.php'; ?>