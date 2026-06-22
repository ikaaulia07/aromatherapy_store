<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$pageTitle = 'Kelola Kategori';
$extraCss = 'admin.css';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$categories = [];
if ($pdo) {
    try {
        $categories = $pdo->query("SELECT * FROM kategori ORDER BY id_kategori DESC")->fetchAll();
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
            <li><a href="<?= getAppUrl() ?>/admin/kategori/index.php" class="sidebar-link active"><i class="fa fa-folder"></i> <span>Kategori</span></a></li>
            <li><a href="<?= getAppUrl() ?>/admin/pesanan/index.php" class="sidebar-link"><i class="fa fa-shopping-bag"></i> <span>Pesanan</span></a></li>
            <li><a href="<?= getAppUrl() ?>/admin/pelanggan/index.php" class="sidebar-link"><i class="fa fa-users"></i> <span>Pelanggan</span></a></li>
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
                <h1>Kelola Kategori Produk</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Tambah atau atur kategori wewangian di butik Anda.</p>
            </div>
            <a href="tambah.php" class="btn btn-primary" style="height: 40px; display:flex; align-items:center; justify-content:center; gap: 8px;">
                <i class="fa fa-plus"></i> Tambah Kategori
            </a>
        </div>

        <?= getFlashMessage() ?>

        <div style="background-color: #fff; padding: 30px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
            <?php if (!empty($categories)): ?>
                <div class="table-wrapper" style="margin: 0;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 15%">ID Kategori</th>
                                <th>Nama Kategori</th>
                                <th style="width: 25%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><strong>#<?= $cat['id_kategori'] ?></strong></td>
                                    <td><?= htmlspecialchars($cat['nama_kategori']) ?></td>
                                    <td class="text-center">
                                        <div style="display: flex; gap: 10px; justify-content: center;">
                                            <a href="edit.php?id=<?= $cat['id_kategori'] ?>" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.8rem; border-radius: 8px;">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
                                            <a href="hapus.php?id=<?= $cat['id_kategori'] ?>" class="btn btn-outline" style="padding: 6px 14px; font-size: 0.8rem; border-radius: 8px; border-width: 1px;" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini? Semua produk di kategori ini akan kehilangan relasinya.')">
                                                <i class="fa fa-trash"></i> Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">Belum ada data kategori.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
