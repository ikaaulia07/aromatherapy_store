<?php
$pageTitle = 'Semua Koleksi Produk';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$pdo = getDBConnection();
$categories = [];
$products = [];

$kategori_filter = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$search_filter = isset($_GET['search']) ? sanitize($_GET['search']) : '';

if ($pdo) {
    // Fetch categories
    try {
        $categories = $pdo->query("SELECT * FROM kategori")->fetchAll();
        
        // Build product query
        $sql = "SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.id_kategori = k.id_kategori WHERE 1=1";
        $params = [];
        
        if ($kategori_filter > 0) {
            $sql .= " AND p.id_kategori = ?";
            $params[] = $kategori_filter;
        }
        
        if (!empty($search_filter)) {
            $sql .= " AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
            $params[] = "%$search_filter%";
            $params[] = "%$search_filter%";
        }
        
        $sql .= " ORDER BY p.id_produk DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    } catch (\PDOException $e) {
        // Fail silent fallback
    }
}
?>

<div class="container" style="margin-top: 50px; margin-bottom: 80px;">
    <div class="title-container text-center">
        <h2 class="section-title">Koleksi Aromaterapi</h2>
        <p class="subtitle">Pilihlah keharuman alami untuk menyeimbangkan energi tubuh & jiwa Anda</p>
    </div>
    
    <!-- Filter Bar -->
    <div style="background-color: #fff; padding: 20px 30px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow); margin-bottom: 40px; display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between; align-items: center;">
        
        <!-- Category Tabs -->
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            <a href="produk.php" class="btn <?= $kategori_filter == 0 ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 8px 20px; font-size: 0.85rem;">Semua</a>
            <?php foreach ($categories as $cat): ?>
                <a href="produk.php?kategori=<?= $cat['id_kategori'] ?>&search=<?= urlencode($search_filter) ?>" class="btn <?= $kategori_filter == $cat['id_kategori'] ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 8px 20px; font-size: 0.85rem;">
                    <?= htmlspecialchars($cat['nama_kategori']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Search Input -->
        <form action="produk.php" method="GET" style="display: flex; gap: 10px; width: 100%; max-width: 350px;">
            <?php if ($kategori_filter > 0): ?>
                <input type="hidden" name="kategori" value="<?= $kategori_filter ?>">
            <?php endif; ?>
            <input type="text" name="search" class="form-control" placeholder="Cari nama lilin / essential oil..." value="<?= htmlspecialchars($search_filter) ?>" style="padding: 10px 15px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;"><i class="fa fa-search"></i></button>
        </form>
    </div>
    
    <!-- Products Listing Grid -->
    <?php if (!empty($products)): ?>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <div class="product-img-wrapper">
                        <?php if ($p['gambar']): ?>
                            <img src="<?= $appUrl ?>/uploads/produk/<?= $p['gambar'] ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>">
                        <?php else: ?>
                            <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background-color: var(--secondary-color); color: var(--text-muted); font-size: 0.9rem;">No Image</div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <span class="product-cat"><?= htmlspecialchars($p['nama_kategori'] ?? 'Aromaterapi') ?></span>
                        <h3 class="product-name"><?= htmlspecialchars($p['nama_produk']) ?></h3>
                        <p class="product-desc"><?= htmlspecialchars($p['deskripsi']) ?></p>
                        <div class="product-meta">
                            <span class="product-price"><?= formatRupiah($p['harga']) ?></span>
                            <a href="detail_produk.php?id=<?= $p['id_produk'] ?>" class="product-btn" title="Lihat Detail">
                                Selengkapnya
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="background-color:#fff; padding: 60px; border-radius: 20px; text-align: center; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
            <i class="fa fa-search-minus" style="font-size: 3.5rem; color: var(--primary-color); margin-bottom: 20px;"></i>
            <h3 style="font-family: var(--font-heading); color: var(--text-color); margin-bottom: 10px;">Produk Tidak Ditemukan</h3>
            <p style="color: var(--text-muted); font-size: 1rem;">Tidak ada lilin atau essential oil yang sesuai dengan pencarian Anda saat ini.</p>
            <a href="produk.php" class="btn btn-primary" style="margin-top: 20px;">Kembali ke Semua Produk</a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
