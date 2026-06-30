<?php
require_once __DIR__ . '/config/database.php';

try {
    // 1. Alter pesanan table
    $stmt = $pdo->query("SHOW COLUMNS FROM pesanan LIKE 'snap_token'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("ALTER TABLE pesanan ADD COLUMN snap_token VARCHAR(255) NULL");
        echo "<h3 style='color: green;'>Sukses: Kolom 'snap_token' berhasil ditambahkan ke tabel 'pesanan'.</h3>";
    } else {
        echo "<h3 style='color: blue;'>Info: Kolom 'snap_token' sudah ada di tabel 'pesanan'.</h3>";
    }

    // 2. Alter pembayaran table to store JSON payload in bukti_pembayaran
    $pdo->exec("ALTER TABLE pembayaran MODIFY COLUMN bukti_pembayaran TEXT NULL");
    echo "<h3 style='color: green;'>Sukses: Kolom 'bukti_pembayaran' berhasil diubah menjadi TEXT.</h3>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>Gagal: " . $e->getMessage() . "</h3>";
}
?>
