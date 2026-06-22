<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$pdo = getDBConnection();
$id_kategori = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_kategori > 0 && $pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id_kategori = ?");
        $stmt->execute([$id_kategori]);
        setFlashMessage('success', 'Kategori berhasil dihapus.');
    } catch (\PDOException $e) {
        setFlashMessage('danger', 'Gagal menghapus kategori. Pastikan tidak ada produk yang terkait ke kategori ini.');
    }
} else {
    setFlashMessage('danger', 'ID kategori tidak valid.');
}

redirect('index.php');
?>
