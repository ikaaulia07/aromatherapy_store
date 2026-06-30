<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$pageTitle = 'Kelola Pesanan';
$extraCss = 'admin.css';
require_once __DIR__ . '/../../includes/header.php';

$pdo = getDBConnection();
$orders = [];
$selectedOrder = null;
$orderItems = [];
$paymentInfo = null;

$id_pesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pdo) {
    // 1. Process Status Update (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $id = (int)$_POST['id_pesanan'];
        $new_status = sanitize($_POST['status']);
        
        try {
            $stmt = $pdo->prepare("UPDATE pesanan SET status = ? WHERE id_pesanan = ?");
            $stmt->execute([$new_status, $id]);
            setFlashMessage('success', 'Status pesanan #' . $id . ' berhasil diperbarui menjadi ' . $new_status . '.');
        } catch (\PDOException $e) {
            setFlashMessage('danger', 'Gagal memperbarui status pesanan.');
        }
        redirect('index.php?id=' . $id);
    }

    // 2. Process Payment Verification Status (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_payment') {
        $id = (int)$_POST['id_pesanan'];
        $verif_status = sanitize($_POST['status_verifikasi']);
        $id_payment = (int)$_POST['id_pembayaran'];
        
        try {
            $pdo->beginTransaction();
            
            // Update payment verification status
            $stmt = $pdo->prepare("UPDATE pembayaran SET status_verifikasi = ? WHERE id_pembayaran = ?");
            $stmt->execute([$verif_status, $id_payment]);
            
            // Automatically transition order status depending on verification
            if ($verif_status === 'Diterima') {
                $stmt = $pdo->prepare("UPDATE pesanan SET status = 'Diproses' WHERE id_pesanan = ?");
                $stmt->execute([$id]);
                setFlashMessage('success', '✅ Pembayaran dikonfirmasi! Pesanan #' . $id . ' berubah menjadi Diproses.');
            } else {
                $stmt = $pdo->prepare("UPDATE pesanan SET status = 'Pending' WHERE id_pesanan = ?");
                $stmt->execute([$id]);
                setFlashMessage('warning', 'Pembayaran ditolak. Pesanan #' . $id . ' dikembalikan ke Pending.');
            }
            
            $pdo->commit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            setFlashMessage('danger', 'Gagal memverifikasi pembayaran.');
        }
        redirect('index.php?id=' . $id);
    }

    // 3. Fetch Single Order Details if ID is selected
    if ($id_pesanan > 0) {
        try {
            // Get order & customer info
            $stmt = $pdo->prepare("SELECT p.*, u.nama_lengkap, u.email, u.telepon, u.alamat FROM pesanan p JOIN users u ON p.id_user = u.id_user WHERE p.id_pesanan = ?");
            $stmt->execute([$id_pesanan]);
            $selectedOrder = $stmt->fetch();
            
            if ($selectedOrder) {
                // Get order items
                $stmt = $pdo->prepare("SELECT dp.*, p.nama_produk, p.gambar FROM detail_pesanan dp JOIN produk p ON dp.id_produk = p.id_produk WHERE dp.id_pesanan = ?");
                $stmt->execute([$id_pesanan]);
                $orderItems = $stmt->fetchAll();
                
                // Get payment proof info
                $stmt = $pdo->prepare("SELECT * FROM pembayaran WHERE id_pesanan = ? ORDER BY id_pembayaran DESC LIMIT 1");
                $stmt->execute([$id_pesanan]);
                $paymentInfo = $stmt->fetch();
            }
        } catch (\PDOException $e) {}
    }

    // 4. Fetch All Orders
    try {
        $stmt = $pdo->query("SELECT p.*, u.nama_lengkap FROM pesanan p JOIN users u ON p.id_user = u.id_user ORDER BY p.id_pesanan DESC");
        $orders = $stmt->fetchAll();
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
            <li><a href="<?= getAppUrl() ?>/admin/pesanan/index.php" class="sidebar-link active"><i class="fa fa-shopping-bag"></i> <span>Pesanan</span></a></li>
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
                <h1>Kelola Transaksi Pesanan</h1>
                <p style="color: var(--text-muted); margin-top: 5px;">Konfirmasi pembayaran transfer dan kirim paket pelanggan.</p>
            </div>
            <?php if ($selectedOrder): ?>
                <a href="index.php" class="btn btn-secondary" style="height: 40px; display:flex; align-items:center; justify-content:center; gap: 8px;">
                    <i class="fa fa-arrow-left"></i> Kembali ke Daftar
                </a>
            <?php endif; ?>
        </div>

        <?= getFlashMessage() ?>

        <?php if ($selectedOrder): ?>
            <!-- SELECTED ORDER DETAIL PANEL -->
            <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 40px; align-items: start;">
                
                <!-- Order breakdown & customer info -->
                <div style="background-color: #fff; padding: 30px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
                        <h3 style="font-family: var(--font-heading); color: var(--text-color); font-size: 1.5rem;">Detail Pesanan #<?= $selectedOrder['id_pesanan'] ?></h3>
                        <span class="badge badge-<?= strtolower($selectedOrder['status'] === 'Diproses' ? 'process' : ($selectedOrder['status'] === 'Dikirim' ? 'shipped' : ($selectedOrder['status'] === 'Selesai' ? 'completed' : ($selectedOrder['status'] === 'Dibatalkan' ? 'cancelled' : 'pending')))) ?>">
                            <?= $selectedOrder['status'] ?>
                        </span>
                    </div>

                    <!-- Customer Profile Card -->
                    <div style="background-color: var(--secondary-color); padding: 20px; border-radius: 12px; margin-bottom: 25px; border:1px solid var(--border-color); font-size: 0.95rem;">
                        <h4 style="font-family:var(--font-body); font-weight:600; margin-bottom:10px;"><i class="fa fa-user"></i> Informasi Pelanggan</h4>
                        <p style="margin-bottom:6px;"><strong>Nama Penerima:</strong> <?= htmlspecialchars($selectedOrder['nama_lengkap']) ?></p>
                        <p style="margin-bottom:6px;"><strong>Email:</strong> <?= htmlspecialchars($selectedOrder['email']) ?></p>
                        <p style="margin-bottom:6px;"><strong>Telepon / WA:</strong> <?= htmlspecialchars($selectedOrder['telepon']) ?></p>
                        <p><strong>Alamat Kirim:</strong> <?= nl2br(htmlspecialchars($selectedOrder['alamat'])) ?></p>
                    </div>

                    <!-- Purchased items -->
                    <h4 style="font-family: var(--font-body); font-weight:600; margin-bottom:15px;">Item yang Dibeli</h4>
                    <div class="table-wrapper" style="margin: 0 0 25px; border-radius:12px;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama Produk</th>
                                    <th>Harga</th>
                                    <th class="text-center">Jumlah</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex; align-items:center; gap: 10px;">
                                                <div style="width:40px; height:40px; border-radius:6px; overflow:hidden; background-color:var(--secondary-color); position:relative;">
                                                    <?php if ($item['gambar']): ?>
                                                        <img src="<?= getAppUrl() ?>/uploads/produk/<?= $item['gambar'] ?>" alt="" style="width:100%; height:100%; object-fit:cover; border-radius:0;">
                                                    <?php else: ?>
                                                        <div style="font-size:0.5rem; text-align:center; margin-top:12px;">No img</div>
                                                    <?php endif; ?>
                                                </div>
                                                <span><?= htmlspecialchars($item['nama_produk']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= formatRupiah($item['harga']) ?></td>
                                        <td class="text-center"><?= $item['jumlah'] ?> pcs</td>
                                        <td style="font-weight:600; color:var(--primary-hover);"><?= formatRupiah($item['subtotal']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="3" class="text-right" style="font-weight:700;">Total Tagihan:</td>
                                    <td style="font-weight:700; color:var(--primary-hover); font-size:1.1rem;"><?= formatRupiah($selectedOrder['total_harga']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Action Verification controls & Payment Proof -->
                <div style="background-color: #fff; padding: 30px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                    
                    <!-- 1. Payment Info Card -->
                    <h3 style="font-family: var(--font-heading); color: var(--text-color); font-size: 1.4rem; margin-bottom: 20px;">Informasi Pembayaran</h3>
                    
                    <?php if ($paymentInfo): ?>
                        <div style="background-color: #FCF8F2; padding: 20px; border-radius: 12px; border:1px solid var(--border-color); font-size: 0.9rem; margin-bottom: 20px;">
                            <p style="margin-bottom:8px;"><strong>Metode:</strong> <?= htmlspecialchars($paymentInfo['metode_pembayaran']) ?></p>
                            <p style="margin-bottom:8px;"><strong>Tanggal Konfirmasi:</strong> <?= date('d M Y H:i', strtotime($paymentInfo['tanggal_bayar'])) ?></p>
                            <p style="margin-bottom:8px;"><strong>Status Pembayaran:</strong> 
                                <span style="font-weight:700; color: <?= $paymentInfo['status_verifikasi'] === 'Diterima' ? 'var(--text-green)' : ($paymentInfo['status_verifikasi'] === 'Ditolak' ? '#721c24' : '#856404') ?>;">
                                    <?= $paymentInfo['status_verifikasi'] === 'Diterima' ? '✅ LUNAS' : ($paymentInfo['status_verifikasi'] === 'Ditolak' ? '❌ DITOLAK' : '⏳ MENUNGGU KONFIRMASI ADMIN') ?>
                                </span>
                            </p>
                        </div>
                        
                        <?php if ($paymentInfo['status_verifikasi'] === 'Menunggu'): ?>
                            <div style="background-color: #FFF8E1; border-left:4px solid #F9A825; padding:15px; border-radius:8px; margin-bottom:20px; font-size:0.875rem; color:#5a4000;">
                                ⏳ Pelanggan telah mengklik "Saya Sudah Transfer". Verifikasi dan konfirmasi pembayaran ini.
                            </div>
                            <form action="index.php?id=<?= $id_pesanan ?>" method="POST" style="display:flex; gap:10px;">
                                <input type="hidden" name="action" value="verify_payment">
                                <input type="hidden" name="id_pesanan" value="<?= $id_pesanan ?>">
                                <input type="hidden" name="id_pembayaran" value="<?= $paymentInfo['id_pembayaran'] ?>">
                                <button type="submit" name="status_verifikasi" value="Diterima" class="btn btn-primary" style="flex:1; height:44px; font-size:0.9rem;" onclick="return confirm('Konfirmasi pembayaran ini sebagai LUNAS?')"><i class="fa fa-circle-check"></i> Konfirmasi Pembayaran</button>
                                <button type="submit" name="status_verifikasi" value="Ditolak" class="btn btn-outline" style="flex:1; height:44px; font-size:0.9rem;" onclick="return confirm('Tolak pembayaran ini?')"><i class="fa fa-circle-xmark"></i> Tolak</button>
                            </form>
                        <?php else: ?>
                            <div style="background-color: #E8F5E9; border-left: 4px solid #2E7D32; padding: 15px; border-radius: 6px; font-size: 0.85rem; color: #2E7D32;">
                                <i class="fa fa-circle-check"></i> Pembayaran sudah diverifikasi.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="background-color: #f5f5f5; padding: 20px; border-radius: 10px; text-align:center; color:var(--text-muted);">
                            <i class="fa fa-clock" style="font-size:1.8rem; display:block; margin-bottom:8px;"></i>
                            <p style="margin:0; font-size:0.95rem;">Pelanggan belum mengkonfirmasi transfer.</p>
                        </div>
                    <?php endif; ?>

                    <hr style="border:0; border-top:1px solid var(--border-color); margin: 25px 0;">

                    <!-- 2. Update Order Status Dropdown -->
                    <h3 style="font-family: var(--font-heading); color: var(--text-color); font-size: 1.4rem; margin-bottom: 20px;">Ubah Status Paket</h3>
                    <form action="index.php?id=<?= $id_pesanan ?>" method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id_pesanan" value="<?= $id_pesanan ?>">
                        
                        <div class="form-group">
                            <select name="status" class="form-control" style="height: 44px;" required>
                                <option value="Pending" <?= $selectedOrder['status'] === 'Pending' ? 'selected' : '' ?>>Pending (Menunggu Bayar)</option>
                                <option value="Diproses" <?= $selectedOrder['status'] === 'Diproses' ? 'selected' : '' ?>>Diproses (Sedang Dikemas)</option>
                                <option value="Dikirim" <?= $selectedOrder['status'] === 'Dikirim' ? 'selected' : '' ?>>Dikirim (Paket Kurir)</option>
                                <option value="Selesai" <?= $selectedOrder['status'] === 'Selesai' ? 'selected' : '' ?>>Selesai (Diterima)</option>
                                <option value="Dibatalkan" <?= $selectedOrder['status'] === 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary" style="width:100%; height:44px; margin-top:10px;">Update Status Paket</button>
                    </form>

                </div>

            </div>

        <?php else: ?>
            <!-- ALL ORDERS INDEX DISPLAY -->
            <div style="background-color: #fff; padding: 30px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                <?php if (!empty($orders)): ?>
                    <div class="table-wrapper" style="margin: 0;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No. Pesanan</th>
                                    <th>Nama Pelanggan</th>
                                    <th>Tanggal</th>
                                    <th>Total Tagihan</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
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
                                        <td class="text-center">
                                            <a href="index.php?id=<?= $order['id_pesanan'] ?>" class="btn btn-primary" style="padding: 6px 14px; font-size: 0.8rem; border-radius: 8px;">
                                                <i class="fa fa-eye"></i> Detail & Verifikasi
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 20px;">Belum ada riwayat transaksi pesanan.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
