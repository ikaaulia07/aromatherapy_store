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

        // If pending order exists but snap_token is empty, try to generate it dynamically
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
            
            // 5. Generate Snap Token and update pesanan
            $snap_token = getMidtransSnapToken($id_pesanan, $totalHarga, $user_info);
            if ($snap_token) {
                $stmtToken = $pdo->prepare("UPDATE pesanan SET snap_token = ? WHERE id_pesanan = ?");
                $stmtToken->execute([$snap_token, $id_pesanan]);
            }
            
            // 6. Clear cart
            $stmtClear = $pdo->prepare("DELETE FROM keranjang WHERE id_user = ?");
            $stmtClear->execute([$id_user]);
            
            $pdo->commit();
            setFlashMessage('success', 'Pesanan berhasil dibuat. Silakan lakukan pembayaran.');
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
        <!-- MIDTRANS PAYMENT SCREEN -->
        <div class="auth-wrapper" style="max-width: 650px; padding: 40px; border-radius: 24px; text-align: center;">
            <div class="auth-header">
                <i class="fa fa-credit-card" style="font-size: 3.5rem; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h2>Pembayaran Pesanan</h2>
                <p class="subtitle">Pesanan Anda #<?= $pendingOrder['id_pesanan'] ?> Telah Dibuat</p>
                <div style="font-size: 1.3rem; margin-top: 20px; background-color: var(--primary-light); color: var(--primary-hover); padding: 15px; border-radius: 12px; font-weight:700;">
                    Total Bayar: <?= formatRupiah($pendingOrder['total_harga']) ?>
                </div>
            </div>

            <?php if (!empty($pendingOrder['snap_token'])): ?>
                <p style="color: var(--text-muted); margin: 20px 0; font-size: 0.95rem;">
                    Silakan klik tombol di bawah ini untuk menyelesaikan pembayaran menggunakan Kartu Kredit, Virtual Account, QRIS, e-Wallet, atau metode lainnya melalui Midtrans.
                </p>
                <button id="pay-button" class="btn btn-primary" style="width: 100%; height: 50px; display:flex; align-items:center; justify-content:center; gap: 10px; font-size: 1.05rem; font-weight: 600;">
                    <i class="fa fa-shield-halved"></i> Bayar Sekarang
                </button>
            <?php else: ?>
                <div class="alert alert-danger" style="margin-top: 20px;">
                    Gagal memuat sistem pembayaran Midtrans. Silakan coba muat ulang halaman ini atau hubungi bantuan.
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 25px; font-size: 0.85rem; color: var(--text-muted);">
                <i class="fa fa-lock"></i> Pembayaran Anda dienkripsi secara aman dan diproses otomatis.
            </div>
        </div>

        <?php if (!empty($pendingOrder['snap_token'])): ?>
            <?php
            // Load configuration to get Client Key
            $midtransConfigPath = __DIR__ . '/../config/midtrans.php';
            if (file_exists($midtransConfigPath)) {
                require_once $midtransConfigPath;
            }
            $isProd = defined('MIDTRANS_IS_PRODUCTION') ? MIDTRANS_IS_PRODUCTION : false;
            $snapJsUrl = $isProd ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';
            $clientKey = defined('MIDTRANS_CLIENT_KEY') ? MIDTRANS_CLIENT_KEY : '';
            ?>
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
                            alert("Pembayaran gagal! Silakan coba lagi.");
                        },
                        onClose: function() {
                            // User closed the popup without finishing the payment
                        }
                    });
                });
            </script>
        <?php endif; ?>
        
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
