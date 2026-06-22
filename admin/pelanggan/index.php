<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$pageTitle = 'Daftar Pelanggan';
$extraCss = 'admin.css';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$customers = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY id_user DESC");
        $customers = $stmt->fetchAll();
    } catch (\PDOException $e) {}
}
?>

<body class="admin-body">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-brand">
            <span class="brand-rose">Admin</span> <span class="brand-gold">Aromatherapy</span>
        </div>
        <ul class="sidebar-menu">
            <li><a href="<?= getAppUrl() ?>/admin/dashboard.php" class="sidebar-link"><i class="fa fa-gauge"></i> <span>Dashboard</span></a></li>
            <li><a href="<?= getAppUrl() ?>/admin/produk/index.php" class="sidebar-link"><i class="fa fa-spa"></i> <span>Produk</span></a></li>
            <li><a href="<?= getAppUrl() ?>/admin/kategori/index.php" class="sidebar-link"><i class="fa fa-folder"></i> <span>Kategori</span></a></li>
            <li><a href="<?= getAppUrl() ?>/admin/pesanan/index.php" class="sidebar-link"><i class="fa fa-shopping-bag"></i> <span>Pesanan</span></a></li>
            <li><a href="<?= getAppUrl() ?>/admin/pelanggan/index.php" class="sidebar-link active"><i class="fa fa-users"></i> <span>Pelanggan</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="<?= getAppUrl() ?>/index.php" class="sidebar-link" style="padding: 10px 0;"><i class="fa fa-store"></i> <span>Lihat Toko</span></a>
            <a href="<?= getAppUrl() ?>/auth/logout.php" class="sidebar-link" style="padding: 10px 0; color: #d9534f;"><i class="fa fa-sign-out-alt"></i> <span>Keluar</span></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="admin-header">
            <div>
                <h1>Daftar Pelanggan</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Data lengkap akun pelanggan yang terdaftar di butik Anda.</p>
            </div>
        </div>

        <?= getFlashMessage() ?>

        <div style="background-color: #fff; padding: 30px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
            <?php if (!empty($customers)): ?>
                <div class="table-wrapper" style="margin: 0;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 10%">ID</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Nomor Telepon</th>
                                <th>Alamat Pengiriman</th>
                                <th>Tanggal Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td><strong>#<?= $c['id_user'] ?></strong></td>
                                    <td><strong><?= htmlspecialchars($c['nama_lengkap']) ?></strong></td>
                                    <td><?= htmlspecialchars($c['email']) ?></td>
                                    <td><?= htmlspecialchars($c['telepon'] ? $c['telepon'] : '-') ?></td>
                                    <td style="max-width: 250px; font-size: 0.9rem; line-height: 1.5;"><?= nl2br(htmlspecialchars($c['alamat'])) ?></td>
                                    <td><?= date('d M Y H:i', strtotime($c['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">Belum ada pelanggan terdaftar.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
