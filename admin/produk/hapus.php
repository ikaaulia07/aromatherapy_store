<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$pdo = getDBConnection();
$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_produk > 0 && $pdo) {
    try {
        // Fetch product to remove its image file
        $stmt = $pdo->prepare("SELECT gambar FROM produk WHERE id_produk = ?");
        $stmt->execute([$id_produk]);
        $prod = $stmt->fetch();
        
        if ($prod && !empty($prod['gambar'])) {
            $imagePath = __DIR__ . '/../../uploads/produk/' . $prod['gambar'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM produk WHERE id_produk = ?");
        $stmt->execute([$id_produk]);
        
        setFlashMessage('success', 'Produk berhasil dihapus.');
    } catch (\PDOException $e) {
        setFlashMessage('danger', 'Gagal menghapus produk. Pastikan tidak ada transaksi aktif yang merujuk ke produk ini.');
    }
} else {
    setFlashMessage('danger', 'ID produk tidak valid.');
}

redirect('index.php');
?>
