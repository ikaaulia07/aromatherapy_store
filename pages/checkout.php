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
        // No auto-fallback. Only load pending order if explicitly requested via id_pesanan in URL.

        // If pending order exists, try to get/generate snap_token
        if ($pendingOrder && empty($pendingOrder['snap_token'])) {
            $snap_token = getMidtransSnapToken($pendingOrder['id_pesanan'], $pendingOrder['total_harga'], $user_info);
            if ($snap_token) {
                $stmt = $pdo->prepare("UPDATE pesanan SET snap_token = ? WHERE id_pesanan = ?");
                $stmt->execute([$snap_token, $pendingOrder['id_pesanan']]);
                $pendingOrder['snap_token'] = $snap_token;
            }
        }
    } catch (\PDOException $e) {
        logError($e);
    }
}

// Process Place Order (POST)
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
            $stmtStock  = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
            
            foreach ($cartItems as $item) {
                $subtotal = $item['harga'] * $item['jumlah'];
                $stmtDetail->execute([$id_pesanan, $item['id_produk'], $item['jumlah'], $item['harga'], $subtotal]);
                $stmtStock->execute([$item['jumlah'], $item['id_produk']]);
            }
            
            // 4. Update customer address if changed
            if ($alamat_kirim !== $user_info['alamat']) {
                $stmt = $pdo->prepare("UPDATE users SET alamat = ? WHERE id_user = ?");
                $stmt->execute([$alamat_kirim, $id_user]);
                $user_info['alamat'] = $alamat_kirim;
            }
            
            // 5. Generate Midtrans Snap Token
            $snap_token = getMidtransSnapToken($id_pesanan, $totalHarga, $user_info);
            if ($snap_token) {
                $stmtToken = $pdo->prepare("UPDATE pesanan SET snap_token = ? WHERE id_pesanan = ?");
                $stmtToken->execute([$snap_token, $id_pesanan]);
            }
            
            // 6. Clear cart
            $stmtClear = $pdo->prepare("DELETE FROM keranjang WHERE id_user = ?");
            $stmtClear->execute([$id_user]);
            
            $pdo->commit();
            setFlashMessage('success', 'Pesanan berhasil dibuat! Silakan selesaikan pembayaran.');
            redirect('checkout.php?id_pesanan=' . $id_pesanan);
            
        } catch (\PDOException $e) {
            logError($e);
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
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

// Load Midtrans Client Key for JS
$midtransConfigPath = __DIR__ . '/../config/midtrans.php';
if (file_exists($midtransConfigPath) && !defined('MIDTRANS_CLIENT_KEY')) {
    require_once $midtransConfigPath;
}
$isProd      = defined('MIDTRANS_IS_PRODUCTION') ? MIDTRANS_IS_PRODUCTION : false;
$snapJsUrl   = $isProd ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';
$clientKey   = defined('MIDTRANS_CLIENT_KEY') ? MIDTRANS_CLIENT_KEY : '';
?>

<div class="container" style="margin-top: 50px; margin-bottom: 80px;">
    <?= getFlashMessage(); ?>
    
    <?php if ($pendingOrder): ?>
        <!-- MIDTRANS PAYMENT SCREEN -->
        <div class="auth-wrapper" style="max-width: 650px; padding: 0; border-radius: 24px; overflow: hidden; text-align: center;">
            
            <!-- Header gradient -->
            <div style="background: linear-gradient(135deg, var(--primary-color), var(--primary-hover)); padding: 35px 40px; color:#fff;">
                <i class="fa fa-shield-halved" style="font-size: 2.8rem; margin-bottom: 12px; display:block; opacity:0.9;"></i>
                <h2 style="color:#fff; font-size:1.6rem; margin:0 0 6px;">Pembayaran Pesanan</h2>
                <p style="opacity:0.85; margin:0; font-size:0.9rem;">Pesanan #<?= $pendingOrder['id_pesanan'] ?> telah dibuat</p>
            </div>

            <div style="padding: 35px 40px;">
                <!-- Total -->
                <div style="background-color: var(--primary-light); color: var(--primary-hover); padding: 18px 24px; border-radius: 14px; font-size: 1.4rem; font-weight:700; text-align:center; margin-bottom: 28px;">
                    Total Bayar: <?= formatRupiah($pendingOrder['total_harga']) ?>
                </div>

                <?php if (!empty($pendingOrder['snap_token'])): ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 22px; line-height:1.6;">
                        Klik tombol di bawah untuk membayar menggunakan <strong>Kartu Kredit, Virtual Account, QRIS, e-Wallet</strong>, atau metode lainnya melalui Midtrans.
                    </p>
                    <button id="pay-button" class="btn btn-primary" style="width: 100%; height: 52px; display:flex; align-items:center; justify-content:center; gap: 10px; font-size: 1.05rem; font-weight: 700;">
                        <i class="fa fa-shield-halved"></i> Bayar Sekarang
                    </button>
                    <p style="margin-top: 18px; font-size: 0.8rem; color: var(--text-muted);">
                        <i class="fa fa-lock"></i> Pembayaran dienkripsi & diproses secara aman oleh Midtrans
                    </p>

                    <!-- Midtrans Snap JS -->
                    <script type="text/javascript" src="<?= $snapJsUrl ?>" data-client-key="<?= $clientKey ?>"></script>
                    <script type="text/javascript">
                        const payButton = document.getElementById('pay-button');
                        payButton.addEventListener('click', function () {
                            snap.pay('<?= $pendingOrder['snap_token'] ?>', {
                                onSuccess: function(result) {
                                    window.location.href = 'pesanan-berhasil.php?id_pesanan=<?= $pendingOrder['id_pesanan'] ?>';
                                },
                                onPending: function(result) {
                                    window.location.href = 'pesanan.php';
                                },
                                onError: function(result) {
                                    alert('Pembayaran gagal! Silakan coba lagi.');
                                },
                                onClose: function() {
                                    // User menutup popup tanpa menyelesaikan pembayaran
                                }
                            });
                        });
                    </script>

                <?php else: ?>
                    <div style="background:#fee2e2; border-left:4px solid #c0392b; padding:18px 20px; border-radius:10px; text-align:left; color:#7f1d1d; font-size:0.9rem;">
                        <strong><i class="fa fa-circle-exclamation"></i> Gagal memuat sistem pembayaran.</strong><br>
                        Silakan muat ulang halaman ini atau coba beberapa saat lagi. Jika masalah berlanjut, hubungi admin toko.
                    </div>
                    <div style="margin-top:18px;">
                        <a href="checkout.php?id_pesanan=<?= $pendingOrder['id_pesanan'] ?>" class="btn btn-outline" style="width:100%; height:44px;">
                            <i class="fa fa-rotate-right"></i> Coba Lagi
                        </a>
                    </div>
                <?php endif; ?>
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
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px; margin-top: 20px;">
                        <i class="fa fa-shield-halved"></i> Buat Pesanan & Bayar via Midtrans
                    </button>
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

                <!-- Payment badges -->
                <div style="background-color: var(--secondary-color); border-radius: 10px; padding: 14px; font-size: 0.8rem; color: var(--text-muted); text-align:center;">
                    <i class="fa fa-lock" style="color:var(--primary-color);"></i> Bayar dengan:
                    <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:6px; justify-content:center;">
                        <span style="background:#fff; border:1px solid var(--border-color); border-radius:6px; padding:3px 10px;">💳 Kartu Kredit</span>
                        <span style="background:#fff; border:1px solid var(--border-color); border-radius:6px; padding:3px 10px;">🏦 Virtual Account</span>
                        <span style="background:#fff; border:1px solid var(--border-color); border-radius:6px; padding:3px 10px;">📱 QRIS</span>
                        <span style="background:#fff; border:1px solid var(--border-color); border-radius:6px; padding:3px 10px;">👛 e-Wallet</span>
                    </div>
                </div>
            </div>
            
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
