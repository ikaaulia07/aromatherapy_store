<?php
$pageTitle = 'Pembayaran Berhasil Dikirim';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

requireLogin();

$pdo = getDBConnection();
$id_pesanan = isset($_GET['id_pesanan']) ? (int)$_GET['id_pesanan'] : 0;
$order = null;
$items = [];
$payment = null;
$user_info = null;

if ($id_pesanan > 0 && $pdo) {
    try {
        // Fetch order details
        $stmt = $pdo->prepare("SELECT * FROM pesanan WHERE id_pesanan = ? AND id_user = ?");
        $stmt->execute([$id_pesanan, $_SESSION['user_id']]);
        $order = $stmt->fetch();
        
        if ($order) {
            // Fetch items
            $stmtItem = $pdo->prepare("
                SELECT dp.*, p.nama_produk, p.gambar 
                FROM detail_pesanan dp 
                JOIN produk p ON dp.id_produk = p.id_produk 
                WHERE dp.id_pesanan = ?
            ");
            $stmtItem->execute([$id_pesanan]);
            $items = $stmtItem->fetchAll();
            
            // Fetch payment details
            $stmtPay = $pdo->prepare("SELECT * FROM pembayaran WHERE id_pesanan = ? ORDER BY id_pembayaran DESC LIMIT 1");
            $stmtPay->execute([$id_pesanan]);
            $payment = $stmtPay->fetch();
            
            // Fetch user info for address details
            $stmtUser = $pdo->prepare("SELECT alamat, nama_lengkap, telepon FROM users WHERE id_user = ?");
            $stmtUser->execute([$_SESSION['user_id']]);
            $user_info = $stmtUser->fetch();
        }
    } catch (\PDOException $e) {
        logError($e);
    }
}
?>

<div class="container" style="margin-top: 50px; margin-bottom: 100px; display: flex; justify-content: center;">
    <div class="auth-wrapper" style="max-width: 680px; width: 100%; text-align: center; padding: 45px 35px; border-radius: 24px; box-shadow: var(--hover-shadow); background-color: #fff; border: 1px solid var(--border-color);">
        
        <!-- Animated Checkmark Icon -->
        <div class="success-icon-wrapper" style="margin-bottom: 25px;">
            <div class="success-icon-circle">
                <i class="fa fa-check"></i>
            </div>
        </div>
        
        <h2 style="font-family: var(--font-heading); font-size: 2.2rem; color: #2D2525; margin-bottom: 10px;">Bukti Pembayaran Dikirim!</h2>
        <p class="subtitle" style="font-size: 1rem; line-height: 1.6; color: var(--text-muted); max-width: 500px; margin: 0 auto 30px;">
            Terima kasih! Bukti transfer Anda telah berhasil kami terima. Admin kami akan segera melakukan verifikasi pesanan Anda dalam waktu maksimal 1x24 jam.
        </p>

        <?php if ($order): ?>
            <!-- Order Details Receipt Block -->
            <div style="background-color: #FAF8F6; border: 1px solid var(--border-color); border-radius: 16px; padding: 25px; margin-bottom: 30px; text-align: left;">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding-bottom: 15px; margin-bottom: 15px;">
                    <div>
                        <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">No. Pesanan</span>
                        <h4 style="font-family: var(--font-body); font-size: 1.15rem; font-weight: 700; color: var(--text-color); margin: 2px 0 0 0;">#<?= $order['id_pesanan'] ?></h4>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Tanggal Transaksi</span>
                        <div style="font-size: 0.9rem; font-weight: 600; color: var(--text-color); margin-top: 2px;"><?= date('d M Y H:i', strtotime($order['tanggal_pesanan'])) ?></div>
                    </div>
                </div>

                <!-- Items Purchased -->
                <div style="margin-bottom: 15px;">
                    <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 8px;">Rincian Produk:</span>
                    <?php foreach ($items as $item): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; font-size: 0.9rem;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 35px; height: 35px; border-radius: 6px; overflow: hidden; background-color: var(--secondary-color); flex-shrink: 0; position: relative;">
                                    <?php if ($item['gambar']): ?>
                                        <img src="<?= getAppUrl() ?>/uploads/produk/<?= $item['gambar'] ?>" alt="" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0;">
                                    <?php else: ?>
                                        <div style="font-size: 0.5rem; text-align: center; margin-top: 10px;">No img</div>
                                    <?php endif; ?>
                                </div>
                                <span style="font-weight: 500; color: var(--text-color);"><?= htmlspecialchars($item['nama_produk']) ?> <span style="color: var(--text-muted); font-size: 0.8rem;">(x<?= $item['jumlah'] ?>)</span></span>
                            </div>
                            <span style="font-weight: 600; color: var(--text-color);"><?= formatRupiah($item['subtotal']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 15px 0;">

                <!-- Summary Meta Details -->
                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 20px; font-size: 0.85rem; line-height: 1.6;">
                    <div>
                        <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 4px;">Alamat Pengiriman:</span>
                        <strong style="color: var(--text-color);"><?= htmlspecialchars($user_info['nama_lengkap']) ?></strong> (<?= htmlspecialchars($user_info['telepon']) ?>)<br>
                        <span style="color: var(--text-muted);"><?= nl2br(htmlspecialchars($user_info['alamat'] ?? '')) ?></span>
                    </div>
                    <div>
                        <div style="margin-bottom: 8px;">
                            <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 2px;">Metode Pembayaran:</span>
                            <span style="font-weight: 600; color: var(--text-color);"><i class="fa fa-wallet" style="color: var(--primary-color); margin-right: 4px;"></i> <?= htmlspecialchars($payment['metode_pembayaran'] ?? 'Transfer') ?></span>
                        </div>
                        <div>
                            <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 2px;">Total Belanja:</span>
                            <span style="font-size: 1.1rem; font-weight: 700; color: var(--primary-hover);"><?= formatRupiah($order['total_harga']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="background-color: #FCF8F2; border: 1px solid var(--border-color); padding: 18px 22px; border-radius: 16px; margin-bottom: 35px; text-align: left;">
            <h4 style="font-family: var(--font-body); font-weight:600; font-size: 0.95rem; margin-bottom: 8px; color: var(--text-color);"><i class="fa fa-info-circle" style="color: var(--primary-color);"></i> Informasi Verifikasi</h4>
            <p style="font-size: 0.85rem; line-height: 1.6; color: var(--text-muted); margin: 0;">
                Anda dapat memantau status pesanan Anda secara berkala di halaman <strong>Pesanan Saya</strong>. Kami akan memperbarui status menjadi <strong>Diproses</strong> setelah bukti transfer dikonfirmasi oleh Admin.
            </p>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 12px; max-width: 320px; margin: 0 auto;">
            <a href="<?= getAppUrl() ?>/pages/pesanan.php" class="btn btn-primary" style="height: 48px; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                <i class="fa fa-shopping-bag" style="margin-right: 8px;"></i> Lihat Pesanan Saya
            </a>
            <a href="<?= getAppUrl() ?>/index.php" class="btn btn-secondary" style="height: 48px; display: flex; align-items: center; justify-content: center; font-weight: 500;">
                Kembali ke Beranda
            </a>
        </div>
        
    </div>
</div>

<style>
/* Success Checkmark Animation Styles */
.success-icon-wrapper {
    display: inline-flex;
    justify-content: center;
    align-items: center;
}

.success-icon-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #E8F5E9;
    color: #2E7D32;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    box-shadow: 0 0 0 0 rgba(46, 125, 50, 0.4);
    animation: pulseCheckmark 2s infinite;
}

@keyframes pulseCheckmark {
    0% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(46, 125, 50, 0.4);
    }
    70% {
        transform: scale(1);
        box-shadow: 0 0 0 15px rgba(46, 125, 50, 0);
    }
    100% {
        transform: scale(0.95);
        box-shadow: 0 0 0 0 rgba(46, 125, 50, 0);
    }
}
</style>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
