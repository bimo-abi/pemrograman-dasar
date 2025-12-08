<?php
// Ambil pesan flash (opsional)
$flash = [];
if (isset($_GET['success'])) $flash['success'] = "Berhasil!";
if (isset($_GET['added'])) $flash['success'] = "Transaksi berhasil ditambahkan!!";
if (isset($_GET['deleted'])) $flash['success'] = "Transaksi berhasil dihapus!";
if (isset($_GET['error'])) $flash['error'] = htmlspecialchars($_GET['error']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetKu — Atur Keuanganmu</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <?php if (!empty($flash)): ?>
        <div class="flash-messages">
            <?php foreach ($flash as $type => $msg): ?>
                <div class="alert <?= $type ?>"><?= $msg ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <header class="navbar">
        <div class="container">
            <a href="index.php" class="logo">💰 BudgetKu</a>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="add-transaction.php">➕ Tambah</a>
                <a href="transactions.php">📋 Riwayat</a>
                <a href="reports.php">📊 Laporan</a>
                <a href="goals.php">🎯 Goals</a>
            </nav>
        </div>
    </header>

    <main class="main-content">