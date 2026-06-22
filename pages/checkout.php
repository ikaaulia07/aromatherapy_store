<?php
$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

requireLogin();

$pdo = getDBConnection();
$id_user = $_SESSION['user_id'];

// Get user profile details
$user_info = null;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
    $stmt->execute([$id_user]);
    $user_info = $stmt->fetch();
}

// Check if user has a pending order waiting for payment
$pendingOrder = null;
if ($pdo) {
    try {
        if (isset($_GET['id_pesanan'])) {
            $id_p = (int)$_GET['id_pesanan'];
            $stmt = $pdo->prepare("SELECT * FROM pesanan WHERE id_user = ? AND id_pesanan = ? AND status = 'Pending'");
            $stmt->execute([$id_user, $id_p]);
            $pendingOrder = $stmt->fetch();
        }
        if (!$pendingOrder) {
            $stmt = $pdo->prepare("SELECT * FROM pesanan WHERE id_user = ? AND status = 'Pending' ORDER BY id_pesanan DESC LIMIT 1");
            $stmt->execute([$id_user]);
            $pendingOrder = $stmt->fetch();
        }
    } catch (\PDOException $e) {
        logError($e);
    }
}

// Action 1: Upload Payment Proof
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pembayaran') {
    $id_pesanan = (int)$_POST['id_pesanan'];
    $metode = sanitize($_POST['metode_pembayaran']);
    
    // File Upload handling
    $bukti_nama = '';
    if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['bukti_pembayaran']['tmp_name'];
        $fileName = $_FILES['bukti_pembayaran']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'PAY_' . $id_pesanan . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/../uploads/pembayaran/';
            
            // Create folder if it doesn't exist
            if (!file_exists($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $bukti_nama = $newFileName;
            }
        }
    }

    if (!empty($bukti_nama)) {
        try {
            // Save payment
            $stmt = $pdo->prepare("INSERT INTO pembayaran (id_pesanan, metode_pembayaran, bukti_pembayaran, tanggal_bayar, status_verifikasi) VALUES (?, ?, ?, NOW(), 'Menunggu')");
            $stmt->execute([$id_pesanan, $metode, $bukti_nama]);
            
            // Update order status to 'Diproses'
            $stmt = $pdo->prepare("UPDATE pesanan SET status = 'Diproses' WHERE id_pesanan = ?");
            $stmt->execute([$id_pesanan]);
            
            setFlashMessage('success', 'Bukti pembayaran berhasil diunggah! Admin kami akan memverifikasi pesanan Anda.');
            redirect('pesanan-berhasil.php?id_pesanan=' . $id_pesanan);
        } catch (\PDOException $e) {
            logError($e);
            setFlashMessage('danger', 'Gagal memproses unggah bukti pembayaran.');
        }
    } else {
        setFlashMessage('danger', 'Unggah bukti pembayaran berupa file gambar JPG/PNG yang valid.');
    }
    redirect('checkout.php');
}

// Action 2: Process Place Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $alamat_kirim = sanitize($_POST['alamat_kirim']);
    
    // Fetch cart items
    $stmt = $pdo->prepare("SELECT k.*, p.harga, p.stok FROM keranjang k JOIN produk p ON k.id_produk = p.id_produk WHERE k.id_user = ?");
    $stmt->execute([$id_user]);
    $cartItems = $stmt->fetchAll();
    
    if (!empty($cartItems)) {
        try {
            $pdo->beginTransaction();
            
            // 1. Calculate Total
            $totalHarga = 0;
            foreach ($cartItems as $item) {
                $totalHarga += $item['harga'] * $item['jumlah'];
            }
            
            // 2. Insert into pesanan
            $stmt = $pdo->prepare("INSERT INTO pesanan (id_user, tanggal_pesanan, total_harga, status) VALUES (?, NOW(), ?, 'Pending')");
            $stmt->execute([$id_user, $totalHarga]);
            $id_pesanan = $pdo->lastInsertId();
            
            // 3. Move items to detail_pesanan & deduct product stock
            $stmtDetail = $pdo->prepare("INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmtStock = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
            
            foreach ($cartItems as $item) {
                $subtotal = $item['harga'] * $item['jumlah'];
                $stmtDetail->execute([$id_pesanan, $item['id_produk'], $item['jumlah'], $item['harga'], $subtotal]);
                $stmtStock->execute([$item['jumlah'], $item['id_produk']]);
            }
            
            // 4. Update customer address if changed
            if ($alamat_kirim !== $user_info['alamat']) {
                $stmt = $pdo->prepare("UPDATE users SET alamat = ? WHERE id_user = ?");
                $stmt->execute([$alamat_kirim, $id_user]);
            }
            
            // 5. Clear cart
            $stmtClear = $pdo->prepare("DELETE FROM keranjang WHERE id_user = ?");
            $stmtClear->execute([$id_user]);
            
            $pdo->commit();
            setFlashMessage('success', 'Pesanan berhasil dibuat. Silakan lakukan pembayaran.');
            redirect('checkout.php');
            
        } catch (\PDOException $e) {
            logError($e);
            $pdo->rollBack();
            setFlashMessage('danger', 'Terjadi kesalahan saat memproses pesanan.');
            redirect('keranjang.php');
        }
    } else {
        setFlashMessage('danger', 'Keranjang Anda kosong.');
        redirect('produk.php');
    }
}

// Fetch user's cart if not waiting for payment
$cartItems = [];
$totalHarga = 0;
if (!$pendingOrder && $pdo) {
    $stmt = $pdo->prepare("SELECT k.*, p.nama_produk, p.harga FROM keranjang k JOIN produk p ON k.id_produk = p.id_produk WHERE k.id_user = ?");
    $stmt->execute([$id_user]);
    $cartItems = $stmt->fetchAll();
    foreach ($cartItems as $item) {
        $totalHarga += $item['harga'] * $item['jumlah'];
    }
    
    if (empty($cartItems)) {
        setFlashMessage('warning', 'Tidak ada pesanan aktif atau keranjang Anda kosong.');
        redirect('produk.php');
    }
}
?>

<div class="container" style="margin-top: 50px; margin-bottom: 80px;">
    <?= getFlashMessage(); ?>
    
    <?php if ($pendingOrder): ?>
        <!-- PAYMENT PROOF UPLOAD SCREEN -->
        <div class="auth-wrapper" style="max-width: 650px; padding: 40px; border-radius: 24px;">
            <div class="auth-header">
                <h2>Konfirmasi Pembayaran</h2>
                <p class="subtitle">Pesanan Anda Telah Diterima</p>
                <div style="font-size: 1.1rem; margin-top: 15px; background-color: var(--primary-light); color: var(--primary-hover); padding: 12px; border-radius: 12px; font-weight:700;">
                    Total Transfer: <?= formatRupiah($pendingOrder['total_harga']) ?>
                </div>
            </div>
            
            <form action="checkout.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="pembayaran">
                <input type="hidden" name="id_pesanan" value="<?= $pendingOrder['id_pesanan'] ?>">
                
                <div class="form-group">
                    <label for="metode_pembayaran" class="form-label">Metode Pembayaran</label>
                    <select name="metode_pembayaran" id="metode_pembayaran" class="form-control" required>
                        <option value="QRIS">QRIS (GoPay/DANA/OVO/ShopeePay/LinkAja)</option>
                        <option value="DANA">DANA (082341468870 a.n. Ikha A.A.)</option>
                        <option value="GoPay">GoPay (082341468870 a.n. Ikha A.A.)</option>
                        <option value="BCA Transfer">Transfer Bank BCA (872-045-8120 a.n. Ikha A.A.)</option>
                    </select>
                </div>

                <div id="payment-details-container" style="margin: 25px 0; background-color: #FCF8F2; border: 1px solid var(--border-color); padding: 25px; border-radius: 16px;">
                    <!-- QRIS Detail -->
                    <div id="detail-QRIS" class="payment-detail-item" style="text-align: center;">
                        <h4 style="font-family: var(--font-body); font-weight:600; margin-bottom: 10px; color: var(--text-color);"><i class="fa fa-qrcode" style="color: var(--primary-color);"></i> QRIS Aromatherapy Store</h4>
                        <p style="font-size: 0.9rem; margin-bottom: 15px; color: var(--text-muted);">Pindai kode QR di bawah ini menggunakan aplikasi e-wallet (GoPay, DANA, OVO, ShopeePay) atau M-Banking Anda:</p>
                        <img src="<?= getAppUrl() ?>/assets/images/qris_payment.png" alt="QRIS QR Code" style="max-width: 220px; width: 100%; margin: 0 auto 12px; border: 4px solid #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border-radius: 12px; display: block;">
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 5px;">Nama Merchant: <strong>Aromatherapy Store (Ikha A.A.)</strong></p>
                    </div>

                    <!-- DANA Detail -->
                    <div id="detail-DANA" class="payment-detail-item" style="display: none;">
                        <h4 style="font-family: var(--font-body); font-weight:600; margin-bottom: 10px; color: var(--text-color);"><i class="fa fa-wallet" style="color: var(--primary-color);"></i> Akun DANA Store</h4>
                        <p style="font-size: 0.9rem; margin-bottom: 10px; color: var(--text-muted);">Silakan transfer ke akun DANA resmi kami:</p>
                        <ul style="list-style: none; padding-left: 0; font-size: 0.95rem; line-height: 1.8;">
                            <li>Nomor DANA: <strong>082341468870</strong></li>
                            <li>Atas Nama: <strong>Ikha A.A.</strong></li>
                        </ul>
                    </div>

                    <!-- GoPay Detail -->
                    <div id="detail-GoPay" class="payment-detail-item" style="display: none;">
                        <h4 style="font-family: var(--font-body); font-weight:600; margin-bottom: 10px; color: var(--text-color);"><i class="fa fa-wallet" style="color: var(--primary-color);"></i> Akun GoPay Store</h4>
                        <p style="font-size: 0.9rem; margin-bottom: 10px; color: var(--text-muted);">Silakan transfer ke akun GoPay resmi kami:</p>
                        <ul style="list-style: none; padding-left: 0; font-size: 0.95rem; line-height: 1.8;">
                            <li>Nomor GoPay: <strong>082341468870</strong></li>
                            <li>Atas Nama: <strong>Ikha A.A.</strong></li>
                        </ul>
                    </div>

                    <!-- BCA Detail -->
                    <div id="detail-BCA" class="payment-detail-item" style="display: none;">
                        <h4 style="font-family: var(--font-body); font-weight:600; margin-bottom: 10px; color: var(--text-color);"><i class="fa fa-university" style="color: var(--primary-color);"></i> Rekening Bank BCA</h4>
                        <p style="font-size: 0.9rem; margin-bottom: 10px; color: var(--text-muted);">Silakan transfer ke rekening Bank BCA resmi kami:</p>
                        <ul style="list-style: none; padding-left: 0; font-size: 0.95rem; line-height: 1.8;">
                            <li>Bank: <strong>BCA (Bank Central Asia)</strong></li>
                            <li>Nomor Rekening: <strong>872-045-8120</strong></li>
                            <li>Atas Nama: <strong>Ikha A.A.</strong></li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="bukti_pembayaran" class="form-label">Unggah Bukti Transfer (Format JPG/PNG)</label>
                    <input type="file" name="bukti_pembayaran" id="bukti_pembayaran" class="form-control" accept="image/*" required style="padding: 8px 12px;">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px; margin-top: 10px;">Kirim Bukti Pembayaran</button>
            </form>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectEl = document.getElementById('metode_pembayaran');
                if (selectEl) {
                    selectEl.addEventListener('change', function() {
                        const selectedVal = this.value;
                        const detailItems = document.querySelectorAll('.payment-detail-item');
                        
                        detailItems.forEach(item => {
                            item.style.display = 'none';
                        });
                        
                        if (selectedVal === 'QRIS') {
                            document.getElementById('detail-QRIS').style.display = 'block';
                        } else if (selectedVal === 'DANA') {
                            document.getElementById('detail-DANA').style.display = 'block';
                        } else if (selectedVal === 'GoPay') {
                            document.getElementById('detail-GoPay').style.display = 'block';
                        } else if (selectedVal === 'BCA Transfer') {
                            document.getElementById('detail-BCA').style.display = 'block';
                        }
                    });
                }
            });
            </script>
        </div>
        
    <?php else: ?>
        <!-- PLACE ORDER SCREEN -->
        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 40px; align-items: start;">
            
            <!-- Delivery Info Form -->
            <div style="background-color: #fff; padding: 40px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                <h3 style="font-family: var(--font-heading); margin-bottom: 25px; color: var(--text-color); font-size: 1.7rem;">Informasi Pengiriman</h3>
                
                <form action="checkout.php" method="POST">
                    <input type="hidden" name="action" value="checkout">
                    
                    <div class="form-group">
                        <label class="form-label">Nama Penerima</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user_info['nama_lengkap']) ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user_info['telepon']) ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat_kirim" class="form-label">Alamat Lengkap Tujuan Pengiriman</label>
                        <textarea name="alamat_kirim" id="alamat_kirim" class="form-control" rows="4" required><?= htmlspecialchars($user_info['alamat']) ?></textarea>
                        <small style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px; display:block;">Anda dapat mengubah alamat ini jika paket dikirimkan ke alamat lain.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px; margin-top: 20px;">Buat Pesanan & Bayar</button>
                </form>
            </div>
            
            <!-- Order Breakdown -->
            <div style="background-color: #fff; padding: 30px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                <h3 style="font-family: var(--font-heading); margin-bottom: 20px; color: var(--text-color); font-size: 1.4rem;">Detail Belanja Anda</h3>
                
                <div style="max-height: 250px; overflow-y: auto; margin-bottom: 20px; padding-right: 5px;">
                    <?php foreach ($cartItems as $item): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.95rem;">
                            <span style="color: var(--text-color); font-weight: 500; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= htmlspecialchars($item['nama_produk']) ?> (x<?= $item['jumlah'] ?>)
                            </span>
                            <span style="color: var(--primary-hover); font-weight:600;"><?= formatRupiah($item['harga'] * $item['jumlah']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <hr style="border: 0; border-top: 1px solid var(--border-color); margin-bottom: 15px;">
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1.15rem; font-weight: 700; color: var(--text-color);">
                    <span>Total Bayar</span>
                    <span style="color: var(--primary-hover);"><?= formatRupiah($totalHarga) ?></span>
                </div>
            </div>
            
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
