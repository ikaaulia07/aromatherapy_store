<?php
$pageTitle = 'Pesanan Saya';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

requireLogin();

$pdo = getDBConnection();
$id_user = $_SESSION['user_id'];
$orders = [];

if ($pdo) {
    // Action: Confirm Received (Selesai)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'konfirmasi_selesai') {
        $id_pesanan = (int)$_POST['id_pesanan'];
        try {
            $stmt = $pdo->prepare("UPDATE pesanan SET status = 'Selesai' WHERE id_pesanan = ? AND id_user = ? AND status = 'Dikirim'");
            $stmt->execute([$id_pesanan, $id_user]);
            setFlashMessage('success', 'Terima kasih! Pesanan #' . $id_pesanan . ' telah dinyatakan selesai.');
        } catch (\PDOException $e) {
            logError($e);
            setFlashMessage('danger', 'Gagal memperbarui status pesanan.');
        }
        redirect('pesanan.php');
    }

    // Fetch all orders for current user
    try {
        $stmt = $pdo->prepare("SELECT * FROM pesanan WHERE id_user = ? ORDER BY id_pesanan DESC");
        $stmt->execute([$id_user]);
        $orders = $stmt->fetchAll();
    } catch (\PDOException $e) {
        logError($e);
    }
}
?>

<style>
    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-pending { background-color: #FFF3CD; color: #856404; border: 1px solid #ffeeba; }
    .badge-process { background-color: #CCE5FF; color: #004085; border: 1px solid #b8daff; }
    .badge-shipped { background-color: #E2F0D9; color: #385723; border: 1px solid #c8e5bc; }
    .badge-completed { background-color: #D4EDDA; color: #155724; border: 1px solid #c3e6cb; }
    .badge-cancelled { background-color: #F8D7DA; color: #721C24; border: 1px solid #f5c6cb; }

    .order-card {
        background-color: #fff;
        border-radius: 16px;
        border: 1px solid var(--border-color);
        box-shadow: var(--card-shadow);
        padding: 25px;
        margin-bottom: 25px;
        transition: var(--transition);
    }
    .order-card:hover {
        box-shadow: var(--hover-shadow);
    }
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    .order-details-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    .order-details-table th, .order-details-table td {
        padding: 8px 0;
        text-align: left;
    }
    .order-details-table th {
        color: var(--text-muted);
        font-weight: 500;
        border-bottom: 1px solid var(--border-color);
    }
    .order-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid var(--border-color);
    }
</style>

<div class="container" style="margin-top: 50px; margin-bottom: 80px;">
    <div class="title-container text-center">
        <h2 class="section-title">Pesanan Saya</h2>
        <p class="subtitle">Pantau status transaksi, bukti pembayaran, dan lacak pengiriman paket Anda</p>
    </div>

    <?= getFlashMessage(); ?>

    <?php if (!empty($orders)): ?>
        <div style="max-width: 900px; margin: 0 auto;">
            <?php foreach ($orders as $order): 
                // Fetch items for this order
                $items = [];
                if ($pdo) {
                    try {
                        $stmtItem = $pdo->prepare("
                            SELECT dp.*, p.nama_produk, p.gambar 
                            FROM detail_pesanan dp 
                            JOIN produk p ON dp.id_produk = p.id_produk 
                            WHERE dp.id_pesanan = ?
                        ");
                        $stmtItem->execute([$order['id_pesanan']]);
                        $items = $stmtItem->fetchAll();
                    } catch (\PDOException $e) {
                        logError($e);
                    }
                }
                
                // Get verification status if payment exists
                $payment = null;
                if ($pdo) {
                    try {
                        $stmtPay = $pdo->prepare("SELECT * FROM pembayaran WHERE id_pesanan = ? ORDER BY id_pembayaran DESC LIMIT 1");
                        $stmtPay->execute([$order['id_pesanan']]);
                        $payment = $stmtPay->fetch();
                    } catch (\PDOException $e) {
                        logError($e);
                    }
                }
            ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span style="color: var(--text-muted); font-size: 0.85rem;">No. Pesanan</span>
                            <h3 style="font-family: var(--font-body); font-size: 1.15rem; font-weight: 700; color: var(--text-color);">
                                #<?= $order['id_pesanan'] ?>
                            </h3>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                <i class="fa fa-calendar-alt"></i> <?= date('d M Y H:i', strtotime($order['tanggal_pesanan'])) ?>
                            </span>
                        </div>
                        <div>
                            <?php
                            $status_class = 'badge-pending';
                            if ($order['status'] === 'Diproses') $status_class = 'badge-process';
                            elseif ($order['status'] === 'Dikirim') $status_class = 'badge-shipped';
                            elseif ($order['status'] === 'Selesai') $status_class = 'badge-completed';
                            elseif ($order['status'] === 'Dibatalkan') $status_class = 'badge-cancelled';
                            ?>
                            <span class="badge <?= $status_class ?>"><?= $order['status'] ?></span>
                        </div>
                    </div>

                    <!-- Items List -->
                    <table class="order-details-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th style="text-align: center; width: 15%;">Jumlah</th>
                                <th style="text-align: right; width: 20%;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="width: 40px; height: 40px; border-radius: 6px; overflow: hidden; background-color: var(--secondary-color); position: relative; flex-shrink: 0;">
                                                <?php if ($item['gambar']): ?>
                                                    <img src="<?= $appUrl ?>/uploads/produk/<?= $item['gambar'] ?>" alt="" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0;">
                                                <?php else: ?>
                                                    <div style="font-size: 0.5rem; text-align: center; margin-top: 12px;">No image</div>
                                                <?php endif; ?>
                                            </div>
                                            <span style="font-weight: 500; color: var(--text-color);"><?= htmlspecialchars($item['nama_produk']) ?></span>
                                        </div>
                                    </td>
                                    <td style="text-align: center; color: var(--text-color);"><?= $item['jumlah'] ?> pcs</td>
                                    <td style="text-align: right; font-weight: 600; color: var(--text-color);"><?= formatRupiah($item['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Footer & Action -->
                    <div class="order-footer">
                        <div>
                            <span style="color: var(--text-muted); font-size: 0.85rem;">Total Tagihan:</span>
                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary-hover);">
                                <?= formatRupiah($order['total_harga']) ?>
                            </div>
                            <?php if ($payment): ?>
                                <small style="display: block; margin-top: 5px; color: var(--text-muted);">
                                    Metode: <strong><?= htmlspecialchars($payment['metode_pembayaran']) ?></strong>
                                </small>
                                <small style="display: block; color: var(--text-muted);">
                                    Status: 
                                    <strong style="color: <?= $payment['status_verifikasi'] === 'Diterima' ? 'var(--text-green, #2e7d32)' : ($payment['status_verifikasi'] === 'Ditolak' ? '#d32f2f' : '#f57f17') ?>;">
                                        <?= $payment['status_verifikasi'] === 'Diterima' ? '✅ Lunas' : ($payment['status_verifikasi'] === 'Ditolak' ? '❌ Ditolak' : '⏳ Menunggu') ?>
                                    </strong>
                                </small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($order['status'] === 'Pending'): ?>
                                <div style="display:flex; flex-direction:column; gap:8px;">
                                    <a href="checkout.php?id_pesanan=<?= $order['id_pesanan'] ?>" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.85rem;">
                                        <i class="fa fa-shield-halved"></i> Bayar Sekarang
                                    </a>
                                    <!-- DEV SIMULATOR BUTTON (Localhost Only) -->
                                    <a href="../tes-bayar.php?id_pesanan=<?= $order['id_pesanan'] ?>" onclick="return confirm('Gunakan ini HANYA saat testing di localhost untuk melewati verifikasi Midtrans. Lanjutkan?')" class="btn btn-outline" style="padding: 6px 15px; font-size: 0.75rem; border-color:#FF9800; color:#FF9800;">
                                        <i class="fa fa-bolt"></i> Simulasikan Lunas (Lokal)
                                    </a>
                                </div>
                            <?php elseif ($order['status'] === 'Dikirim'): ?>
                                <form action="pesanan.php" method="POST" onsubmit="return confirm('Apakah Anda yakin barang sudah diterima dengan baik?');">
                                    <input type="hidden" name="action" value="konfirmasi_selesai">
                                    <input type="hidden" name="id_pesanan" value="<?= $order['id_pesanan'] ?>">
                                    <button type="submit" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.85rem;">
                                        Konfirmasi Diterima
                                    </button>
                                </form>
                            <?php elseif ($order['status'] === 'Diproses'): ?>
                                <span style="font-size: 0.85rem; color: var(--text-muted); font-style: italic;">
                                    <i class="fa fa-box"></i> Sedang dikemas penjual
                                </span>
                            <?php elseif ($order['status'] === 'Selesai'): ?>
                                <span style="font-size: 0.85rem; color: #2e7d32; font-weight: 600;">
                                    <i class="fa fa-check-circle"></i> Selesai &amp; Diterima
                                </span>
                            <?php elseif ($order['status'] === 'Dibatalkan'): ?>
                                <span style="font-size: 0.85rem; color: #d32f2f; font-weight: 600;">
                                    Pesanan Dibatalkan
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Lacak Pesanan Button -->
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); display:flex; justify-content: flex-end;">
                        <a href="lacak-pesanan.php?id=<?= $order['id_pesanan'] ?>" class="btn btn-outline" style="padding: 7px 18px; font-size: 0.83rem; display:flex; align-items:center; gap:6px;">
                            <i class="fa fa-route"></i> Lacak Pesanan
                        </a>
                    </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="background-color: #fff; padding: 60px; border-radius: 20px; text-align: center; border: 1px solid var(--border-color); box-shadow: var(--card-shadow); max-width: 600px; margin: 0 auto;">
            <i class="fa fa-shopping-bag" style="font-size: 4rem; color: var(--primary-color); margin-bottom: 20px; opacity: 0.5;">
            </i>
            <h3 style="font-family: var(--font-heading); margin-bottom: 10px;">Belum Ada Pesanan</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 25px;">Anda belum melakukan transaksi pembelian apapun di toko kami.</p>
            <a href="produk.php" class="btn btn-primary">Mulai Belanja</a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
