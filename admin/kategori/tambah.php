<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$pageTitle = 'Tambah Kategori';
$extraCss = 'admin.css';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = sanitize($_POST['nama_kategori']);
    
    if (!empty($nama_kategori)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
            $stmt->execute([$nama_kategori]);
            setFlashMessage('success', 'Kategori baru berhasil ditambahkan.');
            redirect('index.php');
        } catch (\PDOException $e) {
            setFlashMessage('danger', 'Gagal menambahkan kategori baru.');
        }
    } else {
        setFlashMessage('danger', 'Nama kategori tidak boleh kosong.');
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
                <h1>Tambah Kategori</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Tuliskan nama kategori produk yang baru.</p>
            </div>
            <a href="index.php" class="btn btn-secondary" style="height: 40px; display:flex; align-items:center; justify-content:center; gap: 8px;">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?= getFlashMessage() ?>

        <div class="auth-wrapper" style="margin: 0 auto; max-width: 600px; padding: 40px; border-radius: 20px;">
            <form action="tambah.php" method="POST">
                <div class="form-group">
                    <label for="nama_kategori" class="form-label">Nama Kategori</label>
                    <input type="text" name="nama_kategori" id="nama_kategori" class="form-control" placeholder="Contoh: Aromatherapy Candles" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px; margin-top: 10px;">Simpan Kategori</button>
            </form>
        </div>
    </div>
</body>
</html>
