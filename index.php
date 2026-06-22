<?php
$pageTitle = 'Home - Lilin Aromaterapi & Essential Oil Premium';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Get featured products
$pdo = getDBConnection();
$products = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.id_kategori = k.id_kategori ORDER BY p.id_produk DESC LIMIT 4");
        $products = $stmt->fetchAll();
    } catch (\PDOException $e) {
        // Fallback if table doesn't exist yet
    }
}
?>

<!-- Hero Section -->
<section class="hero-section" style="background: linear-gradient(135deg, #FFF0F2 0%, #FFE0E5 100%); padding: 60px 0; position: relative; overflow: hidden;">
    <div class="container hero-container">
        <div style="z-index: 2; text-align: left;">
            <span class="hero-tag" style="background-color: #fff; color: var(--primary-color); padding: 8px 20px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(255,90,121,0.1); margin-bottom: 25px; display: inline-block;">100% Handcrafted Organic Wax</span>
            <h1 style="font-size: 3.8rem; color: var(--text-color); margin-bottom: 20px; font-family: var(--font-heading); line-height: 1.1;">Temukan Ketenangan Jiwa Melalui Keharuman Alami</h1>
            <p style="font-size: 1.15rem; color: var(--text-muted); max-width: 600px; margin-bottom: 35px; line-height: 1.7;">Koleksi lilin aromaterapi dan essential oil premium kami dirancang khusus untuk menciptakan atmosfer rileks dan menenangkan di rumah Anda.</p>
            <div style="display: flex; gap: 15px;">
                <a href="<?= $appUrl ?>/pages/produk.php" class="btn btn-primary">Belanja Sekarang</a>
                <a href="<?= $appUrl ?>/pages/tentang.php" class="btn btn-secondary">Tentang Kami</a>
            </div>
        </div>
        <div class="hero-image-wrapper">
            <div class="hero-image-card">
                <img src="<?= $appUrl ?>/assets/images/banner/lavender_hero.jpg" alt="Premium Lavender Candle" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0;">
            </div>
            <!-- Decorative shadow glow in background -->
            <div style="position: absolute; width: 80%; height: 80%; background: var(--primary-color); filter: blur(80px); opacity: 0.12; z-index: -1; top: 10%; left: 10%; border-radius: 50%;"></div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section style="padding: 80px 0; background-color: #fff;">
    <div class="container">
        <div class="title-container text-center">
            <h2 class="section-title">Kategori Pilihan</h2>
            <p class="subtitle">Pilih produk terbaik untuk melengkapi relaksasi Anda</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin-top: 40px;">
            <!-- Cat 1 -->
            <a href="<?= $appUrl ?>/pages/produk.php?kategori=1" style="background: var(--secondary-color); border-radius: 20px; padding: 40px 20px; text-align: center; display: block; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                <div style="width: 70px; height: 70px; border-radius: 50%; background-color: #fff; color: var(--primary-color); display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(226,149,149,0.1);">
                    <i class="fa fa-droplet"></i>
                </div>
                <h3 style="font-family: var(--font-heading); font-size: 1.4rem; color: var(--text-color); margin-bottom: 10px;">Essential Oil</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Minyak konsentrat ekstrak tumbuhan alami.</p>
            </a>
            <!-- Cat 2 -->
            <a href="<?= $appUrl ?>/pages/produk.php?kategori=2" style="background: var(--secondary-color); border-radius: 20px; padding: 40px 20px; text-align: center; display: block; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                <div style="width: 70px; height: 70px; border-radius: 50%; background-color: #fff; color: var(--primary-color); display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(226,149,149,0.1);">
                    <i class="fa fa-wind"></i>
                </div>
                <h3 style="font-family: var(--font-heading); font-size: 1.4rem; color: var(--text-color); margin-bottom: 10px;">Diffuser</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Penyebar keharuman secara merata di ruangan.</p>
            </a>
            <!-- Cat 3 -->
            <a href="<?= $appUrl ?>/pages/produk.php?kategori=3" style="background: var(--secondary-color); border-radius: 20px; padding: 40px 20px; text-align: center; display: block; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                <div style="width: 70px; height: 70px; border-radius: 50%; background-color: #fff; color: var(--primary-color); display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(226,149,149,0.1);">
                    <i class="fa fa-fire"></i>
                </div>
                <h3 style="font-family: var(--font-heading); font-size: 1.4rem; color: var(--text-color); margin-bottom: 10px;">Aromatherapy Candle</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Lilin aroma terapi penenang jiwa alami.</p>
            </a>
            <!-- Cat 4 -->
            <a href="<?= $appUrl ?>/pages/produk.php?kategori=4" style="background: var(--secondary-color); border-radius: 20px; padding: 40px 20px; text-align: center; display: block; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                <div style="width: 70px; height: 70px; border-radius: 50%; background-color: #fff; color: var(--primary-color); display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(226,149,149,0.1);">
                    <i class="fa fa-compress"></i>
                </div>
                <h3 style="font-family: var(--font-heading); font-size: 1.4rem; color: var(--text-color); margin-bottom: 10px;">Reed Diffuser</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Wewangian ruangan dengan media stick rotan.</p>
            </a>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section style="padding: 80px 0; background-color: var(--admin-bg);">
    <div class="container">
        <div class="title-container text-center">
            <h2 class="section-title">Produk Terbaru Kami</h2>
            <p class="subtitle">Koleksi terlaris yang paling diminati oleh pelanggan kami</p>
        </div>
        
        <?= getFlashMessage(); ?>
        
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
                                <a href="<?= $appUrl ?>/pages/detail_produk.php?id=<?= $p['id_produk'] ?>" class="product-btn" title="Lihat Detail">
                                    Selengkapnya
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center" style="margin-top: 50px;">
                <a href="<?= $appUrl ?>/pages/produk.php" class="btn btn-primary">Lihat Semua Produk</a>
            </div>
        <?php else: ?>
            <div style="background-color:#fff; padding: 50px; border-radius: 20px; text-align: center; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                <i class="fa fa-info-circle" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 20px;"></i>
                <p style="color: var(--text-muted); font-size: 1.1rem;">Belum ada produk yang tersedia. Admin akan segera menambahkan koleksi lilin aroma terapi kami.</p>
                <?php if (isAdmin()): ?>
                    <a href="<?= $appUrl ?>/admin/produk/tambah.php" class="btn btn-primary" style="margin-top: 20px;">Tambah Produk Sekarang</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- About Callout Section -->
<section style="padding: 80px 0; background-color: #fff;">
    <div class="container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;">
        <div>
            <span style="color: var(--primary-color); font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1.5px;">Premium Self Care</span>
            <h2 style="font-family: var(--font-heading); font-size: 2.8rem; line-height: 1.2; margin: 15px 0 25px; color: var(--text-color);">Ciptakan Ruang Tenang Di Tengah Kesibukan Anda</h2>
            <p style="color: var(--text-muted); margin-bottom: 20px;">Aromaterapi bukan sekadar wewangian biasa, melainkan media terapi alami untuk meredakan stres, merilekskan otot, dan mengembalikan energi positif Anda setelah hari yang panjang.</p>
            <p style="color: var(--text-muted); margin-bottom: 30px;">Kami hanya menggunakan 100% natural soy wax dan minyak esensial organik untuk menjamin pembakaran lilin yang bersih dan sehat untuk keluarga Anda.</p>
            <a href="<?= $appUrl ?>/pages/tentang.php" class="btn btn-outline">Pelajari Komitmen Kami</a>
        </div>
        <div style="position: relative;">
            <!-- Soft pastel placeholder with generated styling -->
            <div style="width: 100%; height: 450px; border-radius: 24px; background: linear-gradient(135deg, #F3E5F5 0%, #FFE0B2 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; box-shadow: var(--hover-shadow); text-align: center;">
                <i class="fa fa-spa" style="font-size: 5rem; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h3 style="font-family: var(--font-heading); color: var(--text-color); font-size: 1.8rem; margin-bottom: 15px;">Relax & Breathe</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 300px;">"Keharuman lilin Lavender & Chamomile dari Aromatherapy Store membuat tidur malam saya menjadi sangat nyenyak."</p>
                <div style="margin-top: 20px; font-weight: 600; color: var(--primary-hover); font-size: 0.9rem;">- Sarah M. (Pelanggan)</div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
