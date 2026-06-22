<?php
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();
$product = null;

$id_produk = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_produk > 0 && $pdo) {
    // Process add to cart if POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireLogin();
        
        $jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 1;
        $id_user = $_SESSION['user_id'];
        
        if ($jumlah < 1) $jumlah = 1;
        
        try {
            // Check stock
            $stmt = $pdo->prepare("SELECT stok FROM produk WHERE id_produk = ?");
            $stmt->execute([$id_produk]);
            $p_stock = $stmt->fetch();
            
            if ($p_stock && $p_stock['stok'] >= $jumlah) {
                // Check if already in cart
                $stmt = $pdo->prepare("SELECT id_keranjang, jumlah FROM keranjang WHERE id_user = ? AND id_produk = ?");
                $stmt->execute([$id_user, $id_produk]);
                $cartItem = $stmt->fetch();
                
                if ($cartItem) {
                    $new_qty = $cartItem['jumlah'] + $jumlah;
                    $stmt = $pdo->prepare("UPDATE keranjang SET jumlah = ? WHERE id_keranjang = ?");
                    $stmt->execute([$new_qty, $cartItem['id_keranjang']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO keranjang (id_user, id_produk, jumlah) VALUES (?, ?, ?)");
                    $stmt->execute([$id_user, $id_produk, $jumlah]);
                }
                
                setFlashMessage('success', 'Produk berhasil ditambahkan ke keranjang belanja.');
                redirect('keranjang.php');
            } else {
                setFlashMessage('danger', 'Stok produk tidak mencukupi.');
            }
        } catch (\PDOException $e) {
            setFlashMessage('danger', 'Terjadi kesalahan sistem.');
        }
    }

    // Fetch product details
    try {
        $stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.id_kategori = k.id_kategori WHERE p.id_produk = ?");
        $stmt->execute([$id_produk]);
        $product = $stmt->fetch();
    } catch (\PDOException $e) {
        // Fallback
    }
}

if (!$product) {
    setFlashMessage('danger', 'Produk tidak ditemukan.');
    redirect('produk.php');
}

$pageTitle = $product['nama_produk'] . ' - Aromatherapy Store';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container" style="margin-top: 50px; margin-bottom: 80px;">
    <?= getFlashMessage(); ?>
    
    <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 50px; align-items: start; background-color:#fff; padding: 40px; border-radius: 24px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
        
        <!-- Image Wrapper -->
        <div style="background-color: var(--secondary-color); border-radius: 16px; overflow: hidden; padding-top: 115%; position: relative;">
            <?php if ($product['gambar']): ?>
                <img src="<?= $appUrl ?>/uploads/produk/<?= $product['gambar'] ?>" alt="<?= htmlspecialchars($product['nama_produk']) ?>" style="position: absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; border-radius:0;">
            <?php else: ?>
                <div style="position: absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; color: var(--text-muted); font-size: 1.2rem;">No Image</div>
            <?php endif; ?>
        </div>
        
        <!-- Content Details -->
        <div>
            <span style="color: var(--primary-color); text-transform: uppercase; font-size: 0.85rem; font-weight: 600; letter-spacing: 1px;"><?= htmlspecialchars($product['nama_kategori'] ?? 'Aromaterapi') ?></span>
            <h1 style="font-family: var(--font-heading); font-size: 2.5rem; color: var(--text-color); margin: 10px 0 15px;"><?= htmlspecialchars($product['nama_produk']) ?></h1>
            <p style="font-size: 1.8rem; font-weight: 700; color: var(--primary-hover); margin-bottom: 25px;"><?= formatRupiah($product['harga']) ?></p>
            
            <hr style="border: 0; border-top: 1px solid var(--border-color); margin-bottom: 25px;">
            
            <h4 style="font-family: var(--font-body); font-weight: 600; margin-bottom: 10px;">Deskripsi Produk</h4>
            <p style="color: var(--text-muted); margin-bottom: 30px; font-size: 0.95rem; line-height: 1.7;"><?= nl2br(htmlspecialchars($product['deskripsi'])) ?></p>
            
            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 25px;">
                <span style="font-weight: 600; font-size: 0.95rem;">Status Stok:</span>
                <?php if ($product['stok'] > 0): ?>
                    <span style="background-color: var(--card-green); color: var(--text-green); padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">Tersedia (<?= $product['stok'] ?> pcs)</span>
                <?php else: ?>
                    <span style="background-color: #F8D7DA; color: #721C24; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">Stok Habis</span>
                <?php endif; ?>
            </div>
            
            <?php if ($product['stok'] > 0): ?>
                <form action="detail_produk.php?id=<?= $product['id_produk'] ?>" method="POST" style="display: flex; gap: 15px; align-items: center;">
                    <div style="display: flex; align-items: center; border: 1px solid var(--border-color); border-radius: 30px; background-color: #FAF8F6; overflow: hidden; height: 48px; width: 120px;">
                        <button type="button" onclick="adjustQty(-1)" style="flex: 1; border:none; background:none; font-size: 1.1rem; cursor:pointer; height: 100%; color: var(--text-muted);">-</button>
                        <input type="number" name="jumlah" id="jumlah" value="1" min="1" max="<?= $product['stok'] ?>" readonly style="width: 40px; text-align: center; border:none; background:none; font-weight: 600; font-size: 1rem; outline:none; -moz-appearance: textfield; pointer-events: none;">
                        <button type="button" onclick="adjustQty(1)" style="flex: 1; border:none; background:none; font-size: 1.1rem; cursor:pointer; height: 100%; color: var(--text-muted);">+</button>
                    </div>
                    <button type="submit" class="btn btn-primary" style="height: 48px; flex-grow: 1; display:flex; align-items:center; justify-content:center; gap: 10px;">
                        <i class="fa fa-shopping-cart"></i> Tambah ke Keranjang
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function adjustQty(amount) {
    const qtyInput = document.getElementById('jumlah');
    let val = parseInt(qtyInput.value) || 1;
    val += amount;
    const maxVal = parseInt(qtyInput.getAttribute('max')) || 99;
    if (val < 1) val = 1;
    if (val > maxVal) val = maxVal;
    qtyInput.value = val;
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
