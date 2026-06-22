<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$pageTitle = 'Dashboard Admin';
$extraCss = 'admin.css';
require_once __DIR__ . '/../includes/header.php';

$pdo = getDBConnection();

// Summary Counters
$countProduk = 0;
$countKategori = 0;
$countPesanan = 0;
$countUser = 0;
$recentOrders = [];

if ($pdo) {
    try {
        // Consolidated count query (resolves multiple queries in one DB trip)
        $counts = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM produk) AS total_produk,
                (SELECT COUNT(*) FROM kategori) AS total_kategori,
                (SELECT COUNT(*) FROM pesanan) AS total_pesanan,
                (SELECT COUNT(*) FROM users WHERE role = 'user') AS total_user
        ")->fetch();

        if ($counts) {
            $countProduk = (int)$counts['total_produk'];
            $countKategori = (int)$counts['total_kategori'];
            $countPesanan = (int)$counts['total_pesanan'];
            $countUser = (int)$counts['total_user'];
        }
        
        $stmt = $pdo->query("SELECT p.*, u.nama_lengkap FROM pesanan p JOIN users u ON p.id_user = u.id_user ORDER BY p.id_pesanan DESC LIMIT 5");
        $recentOrders = $stmt->fetchAll();
    } catch (\PDOException $e) {
        logError($e);
    }
}
?>

<body class="admin-body">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-brand">
            <span class="brand-rose">Admin</span> <span class="brand-gold">Aromatherapy</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="<?= getAppUrl() ?>/admin/dashboard.php" class="sidebar-link active"><i class="fa fa-gauge"></i> <span>Dashboard</span></a></li>
            <li><a href="<?= getAppUrl() ?>/admin/produk/index.php" class="sidebar-link"><i class="fa fa-spa"></i> <span>Produk</span></a></li>
            <li><a href="<?= getAppUrl() ?>/admin/kategori/index.php" class="sidebar-link"><i class="fa fa-folder"></i> <span>Kategori</span></a></li>
            <li><a href="<?= getAppUrl() ?>/admin/pesanan/index.php" class="sidebar-link"><i class="fa fa-shopping-bag"></i> <span>Pesanan</span></a></li>
            <li><a href="<?= getAppUrl() ?>/admin/pelanggan/index.php" class="sidebar-link"><i class="fa fa-users"></i> <span>Pelanggan</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="<?= getAppUrl() ?>/index.php" class="sidebar-link" style="padding: 10px 0;"><i class="fa fa-store"></i> <span>Lihat Toko</span></a>
            <a href="<?= getAppUrl() ?>/auth/logout.php?role=admin" class="sidebar-link" style="padding: 10px 0; color: #d9534f;"><i class="fa fa-sign-out-alt"></i> <span>Keluar</span></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="admin-header">
            <div>
                <h1>Dashboard Admin</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Ringkasan toko lilin aromaterapi Anda hari ini.</p>
            </div>
            <div style="font-weight: 500; font-size: 0.95rem; color: var(--text-color);">
                <i class="fa fa-calendar"></i> <?= date('d M Y') ?>
            </div>
        </div>

        <?= getFlashMessage() ?>

        <!-- Stats Cards Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-spa"></i></div>
                <div class="stat-info">
                    <h3><?= $countProduk ?></h3>
                    <p>Produk</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa fa-folder"></i></div>
                <div class="stat-info">
                    <h3><?= $countKategori ?></h3>
                    <p>Kategori</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa fa-shopping-cart"></i></div>
                <div class="stat-info">
                    <h3><?= $countPesanan ?></h3>
                    <p>Pesanan</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa fa-users"></i></div>
                <div class="stat-info">
                    <h3><?= $countUser ?></h3>
                    <p>Pelanggan</p>
                </div>
            </div>
        </div>

        <!-- Recent Orders Table -->
        <div style="background-color: #fff; padding: 30px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                <h3 style="font-family: var(--font-heading); color: var(--text-color); font-size: 1.5rem;">Pesanan Terbaru</h3>
                <a href="pesanan/index.php" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.8rem; border-radius: 20px;">Lihat Semua</a>
            </div>

            <?php if (!empty($recentOrders)): ?>
                <div class="table-wrapper" style="margin: 0;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Nama Pelanggan</th>
                                <th>Tanggal</th>
                                <th>Total Tagihan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><strong>#<?= $order['id_pesanan'] ?></strong></td>
                                    <td><?= htmlspecialchars($order['nama_lengkap']) ?></td>
                                    <td><?= date('d M Y H:i', strtotime($order['tanggal_pesanan'])) ?></td>
                                    <td style="font-weight: 600; color: var(--primary-hover);"><?= formatRupiah($order['total_harga']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= strtolower($order['status'] === 'Diproses' ? 'process' : ($order['status'] === 'Dikirim' ? 'shipped' : ($order['status'] === 'Selesai' ? 'completed' : ($order['status'] === 'Dibatalkan' ? 'cancelled' : 'pending')))) ?>">
                                            <?= $order['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="pesanan/index.php" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px;">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">Belum ada pesanan masuk.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
