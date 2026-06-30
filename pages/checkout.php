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
                $user_info['alamat'] = $alamat_kirim; // update local context
            }
            
            // 5. Clear cart
            $stmtClear = $pdo->prepare("DELETE FROM keranjang WHERE id_user = ?");
            $stmtClear->execute([$id_user]);
            
            $pdo->commit();
            setFlashMessage('success', 'Pesanan berhasil dibuat! Silakan selesaikan pembayaran transfer bank.');
            redirect('checkout.php?id_pesanan=' . $id_pesanan);
            
        } catch (\PDOException $e) {
            logError($e);
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlashMessage('danger', 'Terjadi kesalahan saat memproses pesanan.');
            redirect('keranjang.php');
        }
    } else {
        setFlashMessage('danger', 'Keranjang Anda kosong.');
        redirect('produk.php');
    }
}

// Action: Pelanggan klik "Saya Sudah Transfer"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'konfirmasi_transfer') {
    $id_pesanan_konfirm = (int)$_POST['id_pesanan'];
    if ($pdo && $id_pesanan_konfirm > 0) {
        try {
            // Insert payment record as 'Menunggu' verification
            $stmtCheckPay = $pdo->prepare("SELECT id_pembayaran FROM pembayaran WHERE id_pesanan = ?");
            $stmtCheckPay->execute([$id_pesanan_konfirm]);
            if (!$stmtCheckPay->fetch()) {
                $stmtPay = $pdo->prepare("INSERT INTO pembayaran (id_pesanan, metode_pembayaran, bukti_pembayaran, tanggal_bayar, status_verifikasi) VALUES (?, 'Transfer Bank', 'Menunggu konfirmasi admin', NOW(), 'Menunggu')");
                $stmtPay->execute([$id_pesanan_konfirm]);
            }
            setFlashMessage('success', 'Konfirmasi transfer berhasil dikirim! Admin akan segera memverifikasi pembayaran Anda.');
            redirect('pesanan.php');
        } catch (\PDOException $e) {
            logError($e);
            setFlashMessage('danger', 'Terjadi kesalahan. Silakan coba lagi.');
            redirect('checkout.php?id_pesanan=' . $id_pesanan_konfirm);
        }
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
        <!-- PAYMENT SCREEN - Transfer Bank Manual -->
        <div class="auth-wrapper" style="max-width: 680px; padding: 0; border-radius: 24px; overflow: hidden; text-align: left;">
            
            <!-- Header -->
            <div style="background: linear-gradient(135deg, var(--primary-color), var(--primary-hover)); padding: 35px 40px; text-align:center; color:#fff;">
                <i class="fa fa-building-columns" style="font-size: 2.8rem; margin-bottom: 12px; display:block; opacity:0.9;"></i>
                <h2 style="color:#fff; font-size:1.6rem; margin:0 0 6px;">Pembayaran via Transfer Bank</h2>
                <p style="opacity:0.85; margin:0; font-size:0.9rem;">Pesanan #<?= $pendingOrder['id_pesanan'] ?></p>
            </div>
            
            <div style="padding: 35px 40px;">
                <!-- Total Badge -->
                <div style="background-color: var(--primary-light); color: var(--primary-hover); padding: 18px 24px; border-radius: 14px; font-size: 1.4rem; font-weight:700; text-align:center; margin-bottom: 30px;">
                    Total Bayar: <?= formatRupiah($pendingOrder['total_harga']) ?>
                </div>
                
                <!-- Bank Account Info -->
                <h4 style="font-family:var(--font-body); font-weight:700; margin-bottom:16px; color:var(--text-color); font-size:1rem;"><i class="fa fa-info-circle" style="color:var(--primary-color);"></i> Transfer ke Rekening Berikut:</h4>
                
                <div style="border: 1px solid var(--border-color); border-radius: 14px; overflow:hidden; margin-bottom: 25px;">
                    <!-- Bank 1 -->
                    <div style="padding: 18px 22px; display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--border-color);">
                        <div>
                            <div style="font-weight:700; color:var(--text-color); font-size:0.95rem;">🏦 BCA</div>
                            <div style="font-size:1.05rem; font-weight:800; letter-spacing:1px; color:var(--primary-hover); margin-top:2px;">1234567890</div>
                            <div style="font-size:0.82rem; color:var(--text-muted);">a.n. Toko Aromatherapy</div>
                        </div>
                        <button onclick="navigator.clipboard.writeText('1234567890'); this.innerHTML='✅ Disalin!'" style="background:var(--primary-light); border:none; color:var(--primary-hover); padding:6px 14px; border-radius:8px; font-size:0.8rem; cursor:pointer; font-weight:600;">Salin</button>
                    </div>
                    <!-- Bank 2 -->
                    <div style="padding: 18px 22px; display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--border-color);">
                        <div>
                            <div style="font-weight:700; color:var(--text-color); font-size:0.95rem;">🏦 Mandiri</div>
                            <div style="font-size:1.05rem; font-weight:800; letter-spacing:1px; color:var(--primary-hover); margin-top:2px;">0987654321</div>
                            <div style="font-size:0.82rem; color:var(--text-muted);">a.n. Toko Aromatherapy</div>
                        </div>
                        <button onclick="navigator.clipboard.writeText('0987654321'); this.innerHTML='✅ Disalin!'" style="background:var(--primary-light); border:none; color:var(--primary-hover); padding:6px 14px; border-radius:8px; font-size:0.8rem; cursor:pointer; font-weight:600;">Salin</button>
                    </div>
                    <!-- Bank 3 -->
                    <div style="padding: 18px 22px; display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div style="font-weight:700; color:var(--text-color); font-size:0.95rem;">🏦 BNI</div>
                            <div style="font-size:1.05rem; font-weight:800; letter-spacing:1px; color:var(--primary-hover); margin-top:2px;">1122334455</div>
                            <div style="font-size:0.82rem; color:var(--text-muted);">a.n. Toko Aromatherapy</div>
                        </div>
                        <button onclick="navigator.clipboard.writeText('1122334455'); this.innerHTML='✅ Disalin!'" style="background:var(--primary-light); border:none; color:var(--primary-hover); padding:6px 14px; border-radius:8px; font-size:0.8rem; cursor:pointer; font-weight:600;">Salin</button>
                    </div>
                </div>

                <!-- Instructions -->
                <div style="background-color: #FFF8E1; border-left: 4px solid #F9A825; padding: 15px 18px; border-radius: 8px; margin-bottom: 25px; font-size:0.875rem; color: #5a4000;">
                    <strong>⚠️ Penting:</strong> Sertakan 3 digit terakhir nomor pesanan <strong>#<?= $pendingOrder['id_pesanan'] ?></strong> sebagai berita transfer agar pembayaran mudah diidentifikasi.
                </div>

                <!-- Confirm Button -->
                <form action="checkout.php" method="POST">
                    <input type="hidden" name="action" value="konfirmasi_transfer">
                    <input type="hidden" name="id_pesanan" value="<?= $pendingOrder['id_pesanan'] ?>">
                    <button type="submit" class="btn btn-primary" style="width:100%; height:52px; font-size:1.05rem; font-weight:700; display:flex; align-items:center; justify-content:center; gap:10px;" onclick="return confirm('Konfirmasi bahwa Anda telah melakukan transfer bank sesuai jumlah yang tertera?')">
                        <i class="fa fa-circle-check"></i> Saya Sudah Transfer
                    </button>
                </form>
                
                <p style="text-align:center; font-size:0.8rem; color:var(--text-muted); margin-top: 15px;">
                    <i class="fa fa-clock"></i> Admin akan memverifikasi pembayaran Anda dalam 1×24 jam.
                </p>
            </div>
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
