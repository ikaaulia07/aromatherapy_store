<?php
$pageTitle = 'Simulasi Pembayaran (Dev)';
require_once __DIR__ . '/includes/header.php';
// We don't include navbar.php here since it's a dev tool, but we can if we want to.
// Let's include it so it feels integrated.
if (file_exists(__DIR__ . '/includes/navbar.php')) {
    require_once __DIR__ . '/includes/navbar.php';
}

require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();
$message = '';
$status = '';
$pesanan = null;

if (isset($_GET['id_pesanan']) && !empty($_GET['id_pesanan'])) {
    $id_pesanan = (int)$_GET['id_pesanan'];

    try {
        // Cek pesanan
        $stmtCheck = $pdo->prepare("SELECT * FROM pesanan WHERE id_pesanan = ?");
        $stmtCheck->execute([$id_pesanan]);
        $pesanan = $stmtCheck->fetch();

        if ($pesanan) {
            $pdo->beginTransaction();

            // 1. Update status pesanan jadi Diproses
            $stmtOrder = $pdo->prepare("UPDATE pesanan SET status = 'Diproses' WHERE id_pesanan = ?");
            $stmtOrder->execute([$id_pesanan]);

            // 2. Buat simulasi log JSON Midtrans
            $fakeMidtransJson = json_encode([
                "transaction_time" => date('Y-m-d H:i:s'),
                "transaction_status" => "settlement",
                "transaction_id" => "simulasi-lokal-" . time(),
                "payment_type" => "bank_transfer",
                "order_id" => (string)$id_pesanan,
                "gross_amount" => $pesanan['total_harga']
            ]);

            // 3. Update atau Insert ke tabel pembayaran
            $stmtCheckPay = $pdo->prepare("SELECT id_pembayaran FROM pembayaran WHERE id_pesanan = ?");
            $stmtCheckPay->execute([$id_pesanan]);
            if ($stmtCheckPay->fetch()) {
                $stmtUpdatePay = $pdo->prepare("UPDATE pembayaran SET metode_pembayaran = 'midtrans_json', bukti_pembayaran = ?, tanggal_bayar = NOW(), status_verifikasi = 'Diterima' WHERE id_pesanan = ?");
                $stmtUpdatePay->execute([$fakeMidtransJson, $id_pesanan]);
            } else {
                $stmtInsertPay = $pdo->prepare("INSERT INTO pembayaran (id_pesanan, metode_pembayaran, bukti_pembayaran, tanggal_bayar, status_verifikasi) VALUES (?, 'midtrans_json', ?, NOW(), 'Diterima')");
                $stmtInsertPay->execute([$id_pesanan, $fakeMidtransJson]);
            }

            $pdo->commit();
            
            $status = 'success';
            $message = "Simulasi webhook Midtrans berhasil! Pesanan #" . $id_pesanan . " kini telah lunas dan sedang diproses.";
        } else {
            $status = 'error';
            $message = "Pesanan dengan ID #" . $id_pesanan . " tidak ditemukan di database.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $status = 'error';
        $message = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
}
?>

<div class="container" style="margin-top: 60px; margin-bottom: 100px; max-width: 600px;">
    
    <div style="background-color: #fff; border-radius: 20px; overflow: hidden; box-shadow: var(--card-shadow); border: 1px solid var(--border-color);">
        
        <!-- Header Section -->
        <div style="background: linear-gradient(135deg, var(--primary-color), var(--primary-hover)); padding: 40px 30px; text-align: center; color: #fff;">
            <div style="background: rgba(255,255,255,0.2); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fa fa-bolt" style="font-size: 2.5rem;"></i>
            </div>
            <h2 style="font-family: var(--font-heading); margin-bottom: 10px; font-size: 1.8rem; color: #fff;">Developer Simulator</h2>
            <p style="opacity: 0.9; margin: 0; font-size: 0.95rem;">Bypass sistem webhook Midtrans khusus untuk testing di Localhost.</p>
        </div>

        <!-- Body Section -->
        <div style="padding: 40px 30px;">
            
            <?php if ($status === 'success'): ?>
                <!-- Success State -->
                <div style="text-align: center; padding: 20px 0;">
                    <i class="fa fa-circle-check" style="color: #4CAF50; font-size: 4rem; margin-bottom: 20px;"></i>
                    <h3 style="color: #2E7D32; font-family: var(--font-heading); margin-bottom: 15px;">Pembayaran Lunas!</h3>
                    <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 30px;">
                        <?= htmlspecialchars($message) ?>
                    </p>
                    
                    <a href="pages/pesanan.php" class="btn btn-primary" style="padding: 12px 30px; font-size: 1rem; width: 100%;">
                        <i class="fa fa-list-check"></i> Cek Pesanan Saya
                    </a>
                    
                    <a href="tes-bayar.php" class="btn btn-outline" style="padding: 12px 30px; font-size: 1rem; width: 100%; margin-top: 15px;">
                        Simulasikan Pesanan Lain
                    </a>
                </div>
            
            <?php else: ?>
                <!-- Input Form State -->
                <?php if ($status === 'error'): ?>
                    <div style="background-color: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-size: 0.9rem;">
                        <i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="GET" action="tes-bayar.php">
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label class="form-label" style="font-weight: 600; color: var(--text-color);">Masukkan ID Pesanan</label>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;">ID pesanan yang statusnya masih 'Pending' akan diubah paksa menjadi 'Diproses' seolah-olah sudah dibayar lunas melalui Midtrans.</p>
                        
                        <div style="position: relative;">
                            <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-weight: bold;">#</span>
                            <input type="number" name="id_pesanan" class="form-control" placeholder="Contoh: 10" required 
                                   style="padding-left: 35px; height: 55px; font-size: 1.1rem; border-width: 2px;">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; height: 55px; font-size: 1.1rem; border-color: var(--primary-hover);">
                        <i class="fa fa-rocket"></i> Simulasikan Lunas
                    </button>
                </form>
            <?php endif; ?>
            
        </div>
    </div>
    
</div>

<?php
if (file_exists(__DIR__ . '/includes/footer.php')) {
    require_once __DIR__ . '/includes/footer.php';
}
?>
