<?php
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();

try {
    $pdo->beginTransaction();

    // 1. Pindahkan produk dari Aromatherapy Candle lama (id=3) ke id=4 (yang akan jadi Aromatherapy Candles baru)
    $pdo->exec("UPDATE produk SET id_kategori = 4 WHERE id_kategori = 3");
    
    // 2. Hapus kategori Aromatherapy Candle lama (id=3)
    $pdo->exec("DELETE FROM kategori WHERE id_kategori = 3");
    
    // 3. Ubah kategori 4 (Reed Diffuser lama) menjadi Aromatherapy Candles
    $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori = 'Aromatherapy Candles' WHERE id_kategori = 4");
    $stmt->execute();
    
    // 4. Ubah kategori 2 (Diffuser lama) menjadi Reed Diffuser
    $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori = 'Reed Diffuser' WHERE id_kategori = 2");
    $stmt->execute();

    $pdo->commit();
    
    echo "<div style='font-family:sans-serif; text-align:center; padding: 50px;'>";
    echo "<h1 style='color: #2E7D32;'>✅ Update Kategori Berhasil!</h1>";
    echo "<p>Kategori pada database berhasil diperbarui sesuai permintaan Anda:</p>";
    echo "<ul style='text-align:left; display:inline-block;'>";
    echo "<li><b>Diffuser</b> diubah menjadi <b>Reed Diffuser</b>.</li>";
    echo "<li><b>Reed Diffuser</b> diubah menjadi <b>Aromatherapy Candles</b>.</li>";
    echo "<li>Kategori Aromatherapy Candle lama dihapus.</li>";
    echo "</ul>";
    echo "<br><br><a href='index.php' style='display:inline-block; padding:10px 20px; background:#007BFF; color:#fff; text-decoration:none; border-radius:5px;'>Kembali ke Beranda</a>";
    echo "</div>";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
?>
