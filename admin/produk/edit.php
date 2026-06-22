<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$pageTitle = 'Edit Produk';
$extraCss = 'admin.css';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$product = null;
$categories = [];
$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_produk > 0 && $pdo) {
    // Fetch categories
    try {
        $categories = $pdo->query("SELECT * FROM kategori")->fetchAll();
    } catch (\PDOException $e) {}
    
    // Process form update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nama_produk = sanitize($_POST['nama_produk']);
        $id_kategori = (int)$_POST['id_kategori'];
        $deskripsi = sanitize($_POST['deskripsi']);
        $harga = (float)$_POST['harga'];
        $stok = (int)$_POST['stok'];
        
        $gambar_nama = $_POST['gambar_lama'];
        
        // Image upload replacement
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['gambar']['tmp_name'];
            $fileName = $_FILES['gambar']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $gambar_nama = 'PROD_' . time() . '.' . $fileExtension;
                $uploadFileDir = __DIR__ . '/../../uploads/produk/';
                
                if (!file_exists($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                
                $dest_path = $uploadFileDir . $gambar_nama;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Remove old image if exist
                    if (!empty($_POST['gambar_lama']) && file_exists($uploadFileDir . $_POST['gambar_lama'])) {
                        unlink($uploadFileDir . $_POST['gambar_lama']);
                    }
                }
            }
        }
        
        if (!empty($nama_produk) && $harga > 0 && $stok >= 0) {
            try {
                $stmt = $pdo->prepare("UPDATE produk SET id_kategori = ?, nama_produk = ?, deskripsi = ?, harga = ?, stok = ?, gambar = ? WHERE id_produk = ?");
                $stmt->execute([$id_kategori, $nama_produk, $deskripsi, $harga, $stok, $gambar_nama, $id_produk]);
                setFlashMessage('success', 'Produk berhasil diperbarui.');
                redirect('index.php');
            } catch (\PDOException $e) {
                setFlashMessage('danger', 'Gagal memperbarui data produk.');
            }
        } else {
            setFlashMessage('danger', 'Semua kolom wajib diisi dengan benar.');
        }
    }

    // Fetch product details
    try {
        $stmt = $pdo->prepare("SELECT * FROM produk WHERE id_produk = ?");
        $stmt->execute([$id_produk]);
        $product = $stmt->fetch();
    } catch (\PDOException $e) {}
}

if (!$product) {
    setFlashMessage('danger', 'Produk tidak ditemukan.');
    redirect('index.php');
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
                <h1>Edit Produk</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Perbarui detail lilin aromaterapi dan essential oil.</p>
            </div>
            <a href="index.php" class="btn btn-secondary" style="height: 40px; display:flex; align-items:center; justify-content:center; gap: 8px;">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?= getFlashMessage() ?>

        <div class="auth-wrapper" style="margin: 0 auto; max-width: 700px; padding: 40px; border-radius: 20px;">
            <form action="edit.php?id=<?= $id_produk ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($product['gambar']) ?>">
                
                <div class="form-group">
                    <label for="nama_produk" class="form-label">Nama Produk</label>
                    <input type="text" name="nama_produk" id="nama_produk" class="form-control" value="<?= htmlspecialchars($product['nama_produk']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="id_kategori" class="form-label">Kategori</label>
                    <select name="id_kategori" id="id_kategori" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id_kategori'] ?>" <?= $cat['id_kategori'] == $product['id_kategori'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" id="deskripsi" class="form-control" rows="4" required><?= htmlspecialchars($product['deskripsi']) ?></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="harga" class="form-label">Harga (Rupiah)</label>
                        <input type="number" name="harga" id="harga" class="form-control" value="<?= (int)$product['harga'] ?>" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="stok" class="form-label">Stok</label>
                        <input type="number" name="stok" id="stok" class="form-control" value="<?= $product['stok'] ?>" min="0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Gambar Saat Ini</label>
                    <?php if ($product['gambar']): ?>
                        <img src="<?= getAppUrl() ?>/uploads/produk/<?= $product['gambar'] ?>" alt="" style="width: 120px; border-radius: 8px; margin-bottom: 10px;">
                    <?php else: ?>
                        <p style="font-size:0.9rem; color:var(--text-muted); margin-bottom: 10px;">Belum ada gambar.</p>
                    <?php endif; ?>
                    <label for="gambar" class="form-label">Ganti Gambar Baru (Format JPG/PNG - Opsional)</label>
                    <input type="file" name="gambar" id="gambar" class="form-control" accept="image/*" style="padding: 8px 12px;">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px; margin-top: 10px;">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</body>
</html>
