<?php
$pageTitle = 'Lacak Pesanan';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

requireLogin();

$pdo = getDBConnection();
$id_user = $_SESSION['user_id'];
$id_pesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$order = null;
$items = [];
$payment = null;
$user_info = null;

if ($id_pesanan > 0 && $pdo) {
    try {
        // Fetch order (must belong to logged-in user)
        $stmt = $pdo->prepare("SELECT p.*, u.nama_lengkap, u.telepon, u.alamat FROM pesanan p JOIN users u ON p.id_user = u.id_user WHERE p.id_pesanan = ? AND p.id_user = ?");
        $stmt->execute([$id_pesanan, $id_user]);
        $order = $stmt->fetch();

        if ($order) {
            // Fetch items
            $stmtItem = $pdo->prepare("
                SELECT dp.*, p.nama_produk, p.gambar, p.harga
                FROM detail_pesanan dp
                JOIN produk p ON dp.id_produk = p.id_produk
                WHERE dp.id_pesanan = ?
            ");
            $stmtItem->execute([$id_pesanan]);
            $items = $stmtItem->fetchAll();

            // Fetch payment
            $stmtPay = $pdo->prepare("SELECT * FROM pembayaran WHERE id_pesanan = ? ORDER BY id_pembayaran DESC LIMIT 1");
            $stmtPay->execute([$id_pesanan]);
            $payment = $stmtPay->fetch();
        }
    } catch (\PDOException $e) {
        logError($e);
    }
}

// If order not found, redirect back
if (!$order) {
    setFlashMessage('warning', 'Pesanan tidak ditemukan atau Anda tidak memiliki akses ke pesanan ini.');
    redirect('pesanan.php');
}

// Define steps for the timeline
$steps = [
    ['key' => 'Pending',    'label' => 'Pesanan Dibuat',      'icon' => 'fa-bag-shopping',    'desc' => 'Pesanan Anda telah berhasil dibuat dan menunggu konfirmasi pembayaran.'],
    ['key' => 'Diproses',   'label' => 'Diproses Penjual',    'icon' => 'fa-box-open',         'desc' => 'Pembayaran telah dikonfirmasi. Pesanan sedang dikemas oleh tim kami.'],
    ['key' => 'Dikirim',    'label' => 'Dalam Pengiriman',    'icon' => 'fa-truck',            'desc' => 'Paket Anda telah diserahkan kepada kurir dan sedang dalam perjalanan.'],
    ['key' => 'Selesai',    'label' => 'Pesanan Selesai',     'icon' => 'fa-circle-check',     'desc' => 'Paket telah diterima. Terima kasih sudah berbelanja di toko kami! 🎉'],
];

$statusOrder  = ['Pending' => 0, 'Diproses' => 1, 'Dikirim' => 2, 'Selesai' => 3];
$currentStep  = $statusOrder[$order['status']] ?? -1;
$isCancelled  = ($order['status'] === 'Dibatalkan');
?>

<style>
    /* ====== TRACKING PAGE STYLES ====== */
    .track-wrapper {
        max-width: 860px;
        margin: 0 auto;
    }

    /* ---- Hero Status Card ---- */
    .status-hero {
        border-radius: 24px;
        padding: 0;
        overflow: hidden;
        border: 1px solid var(--border-color);
        box-shadow: var(--hover-shadow);
        margin-bottom: 30px;
        background: #fff;
    }
    .status-hero-header {
        padding: 30px 35px;
        display: flex;
        align-items: center;
        gap: 20px;
    }
    .status-hero-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        flex-shrink: 0;
    }
    .status-hero-body h2 { font-family: var(--font-heading); font-size: 1.5rem; color: var(--text-color); margin: 0 0 4px; }
    .status-hero-body p  { margin: 0; color: var(--text-muted); font-size: 0.9rem; }

    /* ---- Timeline ---- */
    .timeline-section {
        background: #fff;
        border-radius: 20px;
        padding: 30px 35px;
        border: 1px solid var(--border-color);
        box-shadow: var(--card-shadow);
        margin-bottom: 30px;
    }
    .timeline {
        display: flex;
        flex-direction: column;
        gap: 0;
        position: relative;
    }
    .timeline-step {
        display: flex;
        gap: 22px;
        position: relative;
        padding-bottom: 32px;
    }
    .timeline-step:last-child { padding-bottom: 0; }
    .timeline-step:last-child .timeline-line { display: none; }

    .timeline-dot-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-shrink: 0;
        width: 44px;
    }
    .timeline-dot {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 700;
        border: 3px solid transparent;
        transition: all 0.3s;
        flex-shrink: 0;
    }
    .timeline-dot.done      { background: linear-gradient(135deg, var(--primary-color), var(--primary-hover)); color: #fff; border-color: var(--primary-color); }
    .timeline-dot.active    { background: #fff; color: var(--primary-hover); border-color: var(--primary-color); box-shadow: 0 0 0 5px rgba(var(--primary-rgb, 200,100,100), 0.15); }
    .timeline-dot.inactive  { background: #f5f5f5; color: #bbb; border-color: #e0e0e0; }
    .timeline-dot.cancelled { background: #FEE2E2; color: #c0392b; border-color: #fca5a5; }

    .timeline-line {
        flex: 1;
        width: 2px;
        min-height: 32px;
        margin-top: 6px;
        border-radius: 4px;
    }
    .timeline-line.done     { background: linear-gradient(to bottom, var(--primary-color), var(--primary-color)); }
    .timeline-line.inactive { background: #e9ecef; }

    .timeline-content {
        padding-top: 8px;
        padding-bottom: 8px;
    }
    .timeline-content h4 {
        font-family: var(--font-body);
        font-weight: 700;
        font-size: 1rem;
        margin: 0 0 5px;
    }
    .timeline-content h4.done    { color: var(--text-color); }
    .timeline-content h4.active  { color: var(--primary-hover); }
    .timeline-content h4.inactive{ color: #aaa; }
    .timeline-content p {
        margin: 0;
        font-size: 0.85rem;
        color: var(--text-muted);
        line-height: 1.5;
    }
    .timeline-content p.inactive { color: #ccc; }

    /* Active step pulsing dot */
    @keyframes pulse-ring {
        0%   { box-shadow: 0 0 0 0 rgba(197, 87, 87, 0.35); }
        70%  { box-shadow: 0 0 0 10px rgba(197, 87, 87, 0); }
        100% { box-shadow: 0 0 0 0 rgba(197, 87, 87, 0); }
    }
    .timeline-dot.active { animation: pulse-ring 2s infinite; }

    /* ---- Cancelled Banner ---- */
    .cancelled-banner {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        border: 1px solid #fca5a5;
        border-radius: 16px;
        padding: 24px 28px;
        display: flex;
        align-items: center;
        gap: 18px;
        margin-bottom: 30px;
    }
    .cancelled-banner i { font-size: 2rem; color: #c0392b; }
    .cancelled-banner h3 { margin: 0 0 4px; color: #7f1d1d; font-size: 1.1rem; }
    .cancelled-banner p  { margin: 0; color: #991b1b; font-size: 0.87rem; }

    /* ---- Detail Cards ---- */
    .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 30px;
    }
    @media (max-width: 640px) { .detail-grid { grid-template-columns: 1fr; } }

    .detail-card {
        background: #fff;
        border-radius: 18px;
        border: 1px solid var(--border-color);
        box-shadow: var(--card-shadow);
        padding: 24px 26px;
    }
    .detail-card-title {
        font-family: var(--font-body);
        font-weight: 700;
        font-size: 0.92rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-color);
    }
    .detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem; }
    .detail-row:last-child { margin-bottom: 0; }
    .detail-label { color: var(--text-muted); }
    .detail-value { font-weight: 600; color: var(--text-color); text-align: right; max-width: 55%; }

    /* ---- Items Table ---- */
    .items-card {
        background: #fff;
        border-radius: 18px;
        border: 1px solid var(--border-color);
        box-shadow: var(--card-shadow);
        padding: 24px 26px;
        margin-bottom: 30px;
    }
    .items-table { width: 100%; border-collapse: collapse; }
    .items-table th { font-size: 0.82rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; padding: 8px 0; border-bottom: 1px solid var(--border-color); }
    .items-table td { padding: 14px 0; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; vertical-align: middle; }
    .items-table tr:last-child td { border-bottom: none; }
    .product-thumb { width: 42px; height: 42px; border-radius: 8px; object-fit: cover; background: #f0f0f0; }

    /* ---- Payment Status badge ---- */
    .pay-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 700; }
    .pay-badge.lunas    { background: #D4EDDA; color: #155724; }
    .pay-badge.menunggu { background: #FFF3CD; color: #856404; }
    .pay-badge.ditolak  { background: #F8D7DA; color: #721C24; }
    .pay-badge.belum    { background: #e9ecef; color: #666; }

    /* ---- Back button ---- */
    .back-btn { display: inline-flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.88rem; text-decoration: none; margin-bottom: 22px; font-weight: 500; transition: color 0.2s; }
    .back-btn:hover { color: var(--primary-color); }
</style>

<div class="container" style="margin-top: 50px; margin-bottom: 90px;">
    <a href="pesanan.php" class="back-btn"><i class="fa fa-arrow-left"></i> Kembali ke Daftar Pesanan</a>

    <div class="track-wrapper">

        <?= getFlashMessage(); ?>

        <!-- ===== HERO STATUS CARD ===== -->
        <?php
        $heroColors = [
            'Pending'    => ['bg' => 'linear-gradient(135deg,#FFF9C4,#FFF3CD)', 'icon_bg' => '#FFF3CD', 'icon_c' => '#856404', 'border' => '#ffe082'],
            'Diproses'   => ['bg' => 'linear-gradient(135deg,#E3F2FD,#BBDEFB)', 'icon_bg' => '#BBDEFB', 'icon_c' => '#0d47a1', 'border' => '#90caf9'],
            'Dikirim'    => ['bg' => 'linear-gradient(135deg,#E8F5E9,#C8E6C9)', 'icon_bg' => '#C8E6C9', 'icon_c' => '#1b5e20', 'border' => '#a5d6a7'],
            'Selesai'    => ['bg' => 'linear-gradient(135deg,#F3E5F5,#E1BEE7)', 'icon_bg' => '#E1BEE7', 'icon_c' => '#4a148c', 'border' => '#ce93d8'],
            'Dibatalkan' => ['bg' => 'linear-gradient(135deg,#FEE2E2,#FECACA)', 'icon_bg' => '#FECACA', 'icon_c' => '#c0392b', 'border' => '#fca5a5'],
        ];
        $heroIcons = [
            'Pending'    => 'fa-clock',
            'Diproses'   => 'fa-box-open',
            'Dikirim'    => 'fa-truck',
            'Selesai'    => 'fa-circle-check',
            'Dibatalkan' => 'fa-circle-xmark',
        ];
        $heroLabels = [
            'Pending'    => 'Menunggu Pembayaran',
            'Diproses'   => 'Sedang Diproses & Dikemas',
            'Dikirim'    => 'Sedang Dalam Pengiriman',
            'Selesai'    => 'Pesanan Telah Selesai',
            'Dibatalkan' => 'Pesanan Dibatalkan',
        ];
        $heroDesc = [
            'Pending'    => 'Silakan lakukan pembayaran transfer bank dan konfirmasi kepada kami.',
            'Diproses'   => 'Pembayaran Anda telah dikonfirmasi. Tim kami sedang mengemas produk pesanan Anda.',
            'Dikirim'    => 'Paket sedang dalam perjalanan menuju alamat pengiriman Anda.',
            'Selesai'    => 'Pesanan Anda telah berhasil diterima. Terima kasih sudah berbelanja! 🎉',
            'Dibatalkan' => 'Pesanan ini telah dibatalkan. Stok produk sudah dikembalikan.',
        ];
        $hc = $heroColors[$order['status']] ?? $heroColors['Pending'];
        $hi = $heroIcons[$order['status']] ?? 'fa-question';
        ?>
        <div class="status-hero" style="border-color: <?= $hc['border'] ?>;">
            <div class="status-hero-header" style="background: <?= $hc['bg'] ?>;">
                <div class="status-hero-icon" style="background:<?= $hc['icon_bg'] ?>; color:<?= $hc['icon_c'] ?>;">
                    <i class="fa <?= $hi ?>"></i>
                </div>
                <div class="status-hero-body">
                    <h2><?= $heroLabels[$order['status']] ?? $order['status'] ?></h2>
                    <p><?= $heroDesc[$order['status']] ?? '' ?></p>
                </div>
                <div style="margin-left:auto; text-align:right; flex-shrink:0;">
                    <div style="font-size:0.78rem; color:var(--text-muted); margin-bottom:3px;">Nomor Pesanan</div>
                    <div style="font-family:var(--font-heading); font-size:1.3rem; font-weight:800; color:<?= $hc['icon_c'] ?>;">#<?= $order['id_pesanan'] ?></div>
                    <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;"><?= date('d M Y', strtotime($order['tanggal_pesanan'])) ?></div>
                </div>
            </div>

            <!-- Action Bar -->
            <div style="padding: 16px 35px; border-top: 1px solid <?= $hc['border'] ?>; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; background:#fff;">
                <span style="font-size:0.85rem; color:var(--text-muted);"><i class="fa fa-calendar-alt"></i> Tanggal Pesanan: <strong style="color:var(--text-color);"><?= date('d F Y, H:i', strtotime($order['tanggal_pesanan'])) ?> WIB</strong></span>
                <?php if ($order['status'] === 'Pending' && !$payment): ?>
                    <a href="checkout.php?id_pesanan=<?= $order['id_pesanan'] ?>" class="btn btn-primary" style="height:36px; padding:0 20px; font-size:0.85rem; display:flex; align-items:center; gap:6px;">
                        <i class="fa fa-building-columns"></i> Konfirmasi Pembayaran
                    </a>
                <?php elseif ($order['status'] === 'Dikirim'): ?>
                    <form action="pesanan.php" method="POST" onsubmit="return confirm('Konfirmasi bahwa paket sudah Anda terima?');">
                        <input type="hidden" name="action" value="konfirmasi_selesai">
                        <input type="hidden" name="id_pesanan" value="<?= $order['id_pesanan'] ?>">
                        <button type="submit" class="btn btn-primary" style="height:36px; padding:0 20px; font-size:0.85rem; display:flex; align-items:center; gap:6px;">
                            <i class="fa fa-circle-check"></i> Konfirmasi Sudah Diterima
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== CANCELLED BANNER ===== -->
        <?php if ($isCancelled): ?>
        <div class="cancelled-banner">
            <i class="fa fa-circle-xmark"></i>
            <div>
                <h3>Pesanan Ini Telah Dibatalkan</h3>
                <p>Pesanan #<?= $order['id_pesanan'] ?> sudah dibatalkan. Jika ada kendala, silakan hubungi admin toko kami. Stok produk sudah dikembalikan ke sistem.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== TRACKING TIMELINE ===== -->
        <?php if (!$isCancelled): ?>
        <div class="timeline-section">
            <h3 style="font-family:var(--font-heading); font-size:1.2rem; color:var(--text-color); margin:0 0 28px;">
                <i class="fa fa-route" style="color:var(--primary-color);"></i> Lacak Status Pesanan
            </h3>
            <div class="timeline">
                <?php foreach ($steps as $i => $step):
                    if ($currentStep > $i) {
                        $dotClass  = 'done';
                        $lineClass = 'done';
                        $h4Class   = 'done';
                        $pClass    = '';
                        $icon      = 'fa-check';
                    } elseif ($currentStep === $i) {
                        $dotClass  = 'active';
                        $lineClass = 'inactive';
                        $h4Class   = 'active';
                        $pClass    = '';
                        $icon      = $step['icon'];
                    } else {
                        $dotClass  = 'inactive';
                        $lineClass = 'inactive';
                        $h4Class   = 'inactive';
                        $pClass    = 'inactive';
                        $icon      = $step['icon'];
                    }
                ?>
                <div class="timeline-step">
                    <div class="timeline-dot-wrap">
                        <div class="timeline-dot <?= $dotClass ?>">
                            <i class="fa <?= $icon ?>"></i>
                        </div>
                        <div class="timeline-line <?= $lineClass ?>"></div>
                    </div>
                    <div class="timeline-content">
                        <h4 class="<?= $h4Class ?>"><?= $step['label'] ?>
                            <?php if ($currentStep === $i): ?>
                                <span style="font-size:0.72rem; background:var(--primary-light); color:var(--primary-hover); padding:2px 8px; border-radius:50px; margin-left:6px; vertical-align:middle; font-weight:700;">SAAT INI</span>
                            <?php endif; ?>
                        </h4>
                        <p class="<?= $pClass ?>"><?= $step['desc'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== DETAIL GRID ===== -->
        <div class="detail-grid">
            <!-- Payment Info -->
            <div class="detail-card">
                <div class="detail-card-title"><i class="fa fa-wallet"></i> Informasi Pembayaran</div>
                <?php if ($payment): ?>
                    <div class="detail-row">
                        <span class="detail-label">Metode</span>
                        <span class="detail-value"><?= htmlspecialchars($payment['metode_pembayaran']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Waktu Konfirmasi</span>
                        <span class="detail-value"><?= date('d M Y, H:i', strtotime($payment['tanggal_bayar'])) ?></span>
                    </div>
                    <div class="detail-row" style="margin-top:6px;">
                        <span class="detail-label">Status</span>
                        <span>
                            <?php if ($payment['status_verifikasi'] === 'Diterima'): ?>
                                <span class="pay-badge lunas"><i class="fa fa-circle-check"></i> Lunas</span>
                            <?php elseif ($payment['status_verifikasi'] === 'Ditolak'): ?>
                                <span class="pay-badge ditolak"><i class="fa fa-circle-xmark"></i> Ditolak</span>
                            <?php else: ?>
                                <span class="pay-badge menunggu"><i class="fa fa-clock"></i> Menunggu Verifikasi</span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding: 20px 0; color:var(--text-muted);">
                        <i class="fa fa-clock" style="font-size:1.6rem; display:block; margin-bottom:8px;"></i>
                        <span class="pay-badge belum">Belum Ada Pembayaran</span>
                        <?php if ($order['status'] === 'Pending'): ?>
                            <p style="margin:12px 0 0; font-size:0.83rem;">Silakan selesaikan pembayaran transfer bank dan klik konfirmasi.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Shipping Info -->
            <div class="detail-card">
                <div class="detail-card-title"><i class="fa fa-location-dot"></i> Informasi Pengiriman</div>
                <div class="detail-row">
                    <span class="detail-label">Penerima</span>
                    <span class="detail-value"><?= htmlspecialchars($order['nama_lengkap']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Telepon</span>
                    <span class="detail-value"><?= htmlspecialchars($order['telepon']) ?></span>
                </div>
                <div class="detail-row" style="align-items:flex-start;">
                    <span class="detail-label">Alamat</span>
                    <span class="detail-value" style="text-align:right;"><?= nl2br(htmlspecialchars($order['alamat'])) ?></span>
                </div>
            </div>
        </div>

        <!-- ===== ITEMS LIST ===== -->
        <div class="items-card">
            <div class="detail-card-title" style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom:0;">
                <i class="fa fa-list"></i> Detail Produk Pesanan
            </div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th colspan="2">Produk</th>
                        <th style="text-align:center; width:12%;">Qty</th>
                        <th style="text-align:right; width:18%;">Harga</th>
                        <th style="text-align:right; width:18%;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="width:50px; padding-right: 12px;">
                            <?php if ($item['gambar']): ?>
                                <img src="<?= getAppUrl() ?>/uploads/produk/<?= htmlspecialchars($item['gambar']) ?>" alt="" class="product-thumb">
                            <?php else: ?>
                                <div class="product-thumb" style="display:flex;align-items:center;justify-content:center;color:#ccc;"><i class="fa fa-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600; color:var(--text-color);"><?= htmlspecialchars($item['nama_produk']) ?></td>
                        <td style="text-align:center; color:var(--text-muted);"><?= $item['jumlah'] ?>×</td>
                        <td style="text-align:right; color:var(--text-muted);"><?= formatRupiah($item['harga']) ?></td>
                        <td style="text-align:right; font-weight:700; color:var(--primary-hover);"><?= formatRupiah($item['subtotal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align:right; padding-top:16px; font-weight:700; color:var(--text-color); border-top:2px solid var(--border-color);">Total Pembayaran</td>
                        <td style="text-align:right; padding-top:16px; font-size:1.1rem; font-weight:800; color:var(--primary-hover); border-top:2px solid var(--border-color);"><?= formatRupiah($order['total_harga']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Help Banner -->
        <div style="background: linear-gradient(135deg, #FCF8F2, #FFF0E8); border: 1px solid var(--border-color); border-radius: 16px; padding: 22px 28px; display:flex; align-items:center; gap:18px;">
            <i class="fa fa-headset" style="font-size:1.8rem; color:var(--primary-color);"></i>
            <div>
                <strong style="color:var(--text-color); font-size:0.95rem;">Ada pertanyaan tentang pesanan Anda?</strong>
                <p style="margin:3px 0 0; font-size:0.85rem; color:var(--text-muted);">Hubungi tim kami melalui halaman <a href="kontak.php" style="color:var(--primary-color); font-weight:600;">Kontak</a> dan sebutkan nomor pesanan <strong>#<?= $order['id_pesanan'] ?></strong>.</p>
            </div>
        </div>

    </div><!-- .track-wrapper -->
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
