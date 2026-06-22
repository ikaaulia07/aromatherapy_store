<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$pageTitle = 'Kelola Produk';
$extraCss = 'admin.css';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$products = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.id_kategori = k.id_kategori ORDER BY p.id_produk DESC");
        $products = $stmt->fetchAll();
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
            <li><a href="<?= getAppUrl() ?>/admin/produk/index.php" class="sidebar-link active"><i class="fa fa-spa"></i> <span>Produk</span></a></li>
            <li><a href="<?= getAppUrl() ?>/admin/kategori/index.php" class="sidebar-link"><i class="fa fa-folder"></i> <span>Kategori</span></a></li>
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
                <h1>Kelola Produk</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Atur stok, gambar, dan detail lilin aromatherapy di toko Anda.</p>
            </div>
            <a href="tambah.php" class="btn btn-primary" style="height: 40px; display:flex; align-items:center; justify-content:center; gap: 8px;">
                <i class="fa fa-plus"></i> Tambah Produk
            </a>
        </div>

        <?= getFlashMessage() ?>

        <div style="background-color: #fff; padding: 30px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
            <?php if (!empty($products)): ?>
                <div class="table-wrapper" style="margin: 0;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 10%">Gambar</th>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th style="width: 20%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>
                                        <div style="width: 50px; height: 50px; border-radius: 8px; overflow: hidden; background-color: var(--secondary-color); position:relative;">
                                            <?php if ($p['gambar']): ?>
                                                <img src="<?= getAppUrl() ?>/uploads/produk/<?= $p['gambar'] ?>" alt="" style="width:100%; height:100%; object-fit:cover; border-radius:0;">
                                            <?php else: ?>
                                                <div style="font-size:0.6rem; text-align:center; margin-top:15px; color:var(--text-muted);">No Img</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><strong><?= htmlspecialchars($p['nama_produk']) ?></strong></td>
                                    <td><?= htmlspecialchars($p['nama_kategori'] ?? 'Tanpa Kategori') ?></td>
                                    <td style="font-weight: 600; color: var(--primary-hover);"><?= formatRupiah($p['harga']) ?></td>
                                    <td>
                                        <?php if ($p['stok'] > 0): ?>
                                            <span style="background-color: var(--card-green); color: var(--text-green); padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; font-weight:600;"><?= $p['stok'] ?> pcs</span>
                                        <?php else: ?>
                                            <span style="background-color: #F8D7DA; color: #721C24; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; font-weight:600;">Habis</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div style="display: flex; gap: 10px; justify-content: center;">
                                            <a href="edit.php?id=<?= $p['id_produk'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px;">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
                                            <a href="hapus.php?id=<?= $p['id_produk'] ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; border-width: 1px;" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
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
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">Belum ada data produk.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
