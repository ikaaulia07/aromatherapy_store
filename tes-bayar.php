<?php
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();

if (isset($_GET['id_pesanan'])) {
    $id_pesanan = (int)$_GET['id_pesanan'];

    try {
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
            "gross_amount" => "100000.00"
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
        
        echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
        echo "<h1 style='color: #2E7D32;'>✅ Berhasil!</h1>";
        echo "<p>Simulasi webhook Midtrans untuk Pesanan <b>#$id_pesanan</b> sukses dijalankan.</p>";
        echo "<p>Pesanan sekarang sudah berstatus <b>Diproses (Lunas)</b>.</p>";
        echo "<a href='pages/pesanan.php' style='display:inline-block; padding:10px 20px; background:#4CAF50; color:#fff; text-decoration:none; border-radius:5px;'>Kembali ke Daftar Pesanan</a>";
        echo "</div>";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "<div style='font-family:sans-serif; padding: 20px;'>";
    echo "<h3>Simulasi Pembayaran Midtrans (Localhost)</h3>";
    echo "<p>Masukkan ID Pesanan yang ingin disimulasikan lunas:</p>";
    echo "<form method='GET'>";
    echo "<input type='number' name='id_pesanan' placeholder='Contoh: 10' required style='padding:8px; font-size:16px;'> ";
    echo "<button type='submit' style='padding:8px 15px; font-size:16px; background:#007BFF; color:#fff; border:none; cursor:pointer;'>Simulasikan Lunas</button>";
    echo "</form>";
    echo "</div>";
}
?>
