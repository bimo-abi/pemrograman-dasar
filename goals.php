<?php
session_start();
require_once 'includes/functions.php';

$goals = loadData('goals');
$transactions = loadData('transactions');

// Handle tambah goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_goal'])) {
    $name = trim(filter_var($_POST['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    /*$desc = trim(filter_var($_POST['description'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_SPECIAL_CHARS));*/
    $target = filter_var($_POST['target'] ?? '', FILTER_VALIDATE_FLOAT);
    $deadline = filter_var($_POST['deadline'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $errors = [];
    if (empty($name)) $errors[] = "Nama goal wajib diisi.";
    if ($target <= 0) $errors[] = "Target harus lebih dari 0.";
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) $errors[] = "Format deadline: YYYY-MM-DD.";

    if (empty($errors)) {
        $newGoal = [
            'id' => 'g_' . bin2hex(random_bytes(6)),
            'name' => $name,
            'description' => $desc,
            'target_amount' => $target,
            'current_amount' => 0,
            'deadline' => $deadline,
            'created_at' => date('Y-m-d')
        ];
        $goals[] = $newGoal;
        saveData('goals', $goals);
        header("Location: goals.php?added=1");
        exit;
    }
}

// Handle hapus goal
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = $_GET['delete'];
    $goals = array_filter($goals, fn($g) => $g['id'] !== $id);
    saveData('goals', array_values($goals));
    header("Location: goals.php?deleted=1");
    exit;
}

// Hitung progres otomatis dari transaksi
foreach ($goals as &$g) {
    $saved = 0;
    foreach ($transactions as $t) {
        // Cari transaksi pengeluaran yang mengindikasikan tabungan
        if (
            $t['type'] === 'expense' &&
            (stripos($t['description'], 'tabung') !== false ||
                stripos($t['description'], 'simpan') !== false ||
                $t['category'] === 'Tabungan')
        ) {
            $saved += $t['amount'];
        }
    }
    $g['current_amount'] = $saved; // override current_amount dengan hitungan otomatis
}
saveData('goals', $goals); // simpan perubahan progres
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h1>Target Tabungan</h1>
        <button class="btn btn-primary" onclick="document.getElementById('addGoalForm').style.display='block'">
        Buat Goal Baru
        </button>
    </div>

    <?php if (empty($goals)): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <h3>Belum ada target tabungan.</h3>
            <p>Mulailah dengan membuat goal pertamamu</p>
            <br>
            <button class="btn btn-primary" onclick="document.getElementById('addGoalForm').style.display='block'">
                Buat Goal Sekarang
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($goals as $g):
            $progress = $g['target_amount'] > 0 ? min(100, ($g['current_amount'] / $g['target_amount']) * 100) : 0;
            $remaining = $g['target_amount'] - $g['current_amount'];
            $daysLeft = max(0, (new DateTime($g['deadline']))->diff(new DateTime())->days);
        ?>
            <div class="goal-item">
                <div class="goal-header">
                    <div>
                        <h3><?= htmlspecialchars($g['name']) ?></h3>
                        <?php if (!empty($g['description'])): ?>
                            <p><?= htmlspecialchars($g['description']) ?></p>
                        <?php endif; ?>
                        <p class="caption">
                            Deadline: <?= (new DateTime($g['deadline']))->format('d M Y') ?>
                            (<?= $daysLeft ?> hari lagi)
                        </p>
                    </div>
                    <span><?= round($progress) ?>%</span>
                </div>
                <div class="goal-progress">
                    <div class="goal-progress-bar" style="width: <?= round($progress) ?>%"></div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                    <div>
                        <strong><?= rupiah($g['current_amount']) ?></strong> / <?= rupiah($g['target_amount']) ?>
                        <?php if ($remaining > 0): ?>
                            <span class="caption">Sisa: <?= rupiah($remaining) ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="?delete=<?= urlencode($g['id']) ?>"
                        class="btn btn-danger"
                        onclick="return confirm('Yakin hapus goal ini?')">Hapus</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Form Tambah Goal (hidden by default) -->
    <div id="addGoalForm" class="transaction-form" style="display: none; margin-top: 32px;">
        <h2>Buat Goal Baru</h2>
        <form method="POST">
            <input type="hidden" name="add_goal" value="1">
            <div class="form-group">
                <label>Nama Goal</label>
                <input type="text" name="name" placeholder="Contoh: DP Motor" required>
            </div>
            <div class="form-group">
                <label>Deskripsi (opsional)</label>
                <input type="text" name="description" placeholder="Contoh: Tabungan 6 bulan">
            </div>
            <div class="form-group">
                <label>Target (Rp)</label>
                <input type="number" step="0.01" name="target" placeholder="Contoh: 5000000" required>
            </div>
            <div class="form-group">
                <label>Deadline</label>
                <input type="date" name="deadline" value="<?= date('Y-m-d', strtotime('+6 months')) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Goal</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('addGoalForm').style.display='none'">
                Batal
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>