<?php
$pageTitle = 'Keranjang Belanja';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

requireLogin();

$pdo = getDBConnection();
$id_user = $_SESSION['user_id'];
$cartItems = [];

if ($pdo) {
    // 1. Process Item Deletion (GET)
    if (isset($_GET['action']) && $_GET['action'] === 'hapus') {
        $id_keranjang = (int)$_GET['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM keranjang WHERE id_keranjang = ? AND id_user = ?");
            $stmt->execute([$id_keranjang, $id_user]);
            setFlashMessage('success', 'Produk berhasil dihapus dari keranjang.');
            redirect('keranjang.php');
        } catch (\PDOException $e) {
            setFlashMessage('danger', 'Gagal menghapus produk.');
        }
    }

    // 2. Process Quantity Update (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
        $id_keranjang = (int)$_POST['id_keranjang'];
        $qty = (int)$_POST['jumlah'];
        
        if ($qty < 1) $qty = 1;
        
        try {
            // Check product stock before updating
            $stmt = $pdo->prepare("SELECT p.stok FROM keranjang k JOIN produk p ON k.id_produk = p.id_produk WHERE k.id_keranjang = ? AND k.id_user = ?");
            $stmt->execute([$id_keranjang, $id_user]);
            $p_stok = $stmt->fetch();
            
            if ($p_stok && $p_stok['stok'] >= $qty) {
                $stmt = $pdo->prepare("UPDATE keranjang SET jumlah = ? WHERE id_keranjang = ? AND id_user = ?");
                $stmt->execute([$qty, $id_keranjang, $id_user]);
                setFlashMessage('success', 'Jumlah belanja berhasil diperbarui.');
            } else {
                setFlashMessage('danger', 'Gagal memperbarui. Stok produk tidak mencukupi.');
            }
        } catch (\PDOException $e) {
            setFlashMessage('danger', 'Terjadi kesalahan sistem.');
        }
        redirect('keranjang.php');
    }

    // 3. Fetch Cart Items
    try {
        $stmt = $pdo->prepare("SELECT k.*, p.nama_produk, p.harga, p.gambar, p.stok FROM keranjang k JOIN produk p ON k.id_produk = p.id_produk WHERE k.id_user = ?");
        $stmt->execute([$id_user]);
        $cartItems = $stmt->fetchAll();
    } catch (\PDOException $e) {
        // Fallback
    }
}

$totalHarga = 0;
?>

<div class="container" style="margin-top: 50px; margin-bottom: 80px;">
    <div class="title-container text-center">
        <h2 class="section-title">Keranjang Belanja</h2>
        <p class="subtitle">Kelola lilin aromaterapi pilihan Anda sebelum checkout</p>
    </div>
    
    <?= getFlashMessage(); ?>
    
    <?php if (!empty($cartItems)): ?>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px; align-items: start;">
            
            <!-- Cart Items List -->
            <div>
                <div class="table-wrapper" style="margin: 0; border-radius: 16px;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): 
                                $subtotal = $item['harga'] * $item['jumlah'];
                                $totalHarga += $subtotal;
                            ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 15px;">
                                            <div style="width: 60px; height: 60px; border-radius: 8px; overflow: hidden; background-color: var(--secondary-color); flex-shrink: 0; position: relative;">
                                                <?php if ($item['gambar']): ?>
                                                    <img src="<?= $appUrl ?>/uploads/produk/<?= $item['gambar'] ?>" alt="<?= htmlspecialchars($item['nama_produk']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0;">
                                                <?php else: ?>
                                                    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:0.7rem; color:var(--text-muted);">No Img</div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h4 style="font-family: var(--font-body); font-weight: 600; font-size: 1rem; color: var(--text-color);"><?= htmlspecialchars($item['nama_produk']) ?></h4>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= formatRupiah($item['harga']) ?></td>
                                    <td>
                                        <!-- Update Form -->
                                        <form action="keranjang.php" method="POST" style="display: inline-flex; align-items: center; border: 1px solid var(--border-color); border-radius: 30px; background-color: #FAF8F6; overflow: hidden; height: 36px; max-width: 110px;">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id_keranjang" value="<?= $item['id_keranjang'] ?>">
                                            
                                            <button type="button" onclick="changeQty(this, -1)" style="width: 25px; border:none; background:none; font-weight:700; cursor:pointer; height: 100%; color: var(--text-muted);">-</button>
                                            <input type="number" name="jumlah" value="<?= $item['jumlah'] ?>" min="1" max="<?= $item['stok'] ?>" readonly style="width: 35px; text-align: center; border:none; background:none; font-weight: 600; font-size: 0.9rem; pointer-events: none;">
                                            <button type="button" onclick="changeQty(this, 1)" style="width: 25px; border:none; background:none; font-weight:700; cursor:pointer; height: 100%; color: var(--text-muted);">+</button>
                                        </form>
                                    </td>
                                    <td style="font-weight: 600; color: var(--primary-hover);"><?= formatRupiah($subtotal) ?></td>
                                    <td>
                                        <a href="keranjang.php?action=hapus&id=<?= $item['id_keranjang'] ?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; border-width: 1px;" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini dari keranjang?')">
                                            <i class="fa fa-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Order Summary Card -->
            <div style="background-color: #fff; padding: 30px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
                <h3 style="font-family: var(--font-heading); margin-bottom: 20px; color: var(--text-color); font-size: 1.4rem;">Ringkasan Belanja</h3>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem; color: var(--text-muted);">
                    <span>Total Item</span>
                    <span><?= array_sum(array_column($cartItems, 'jumlah')) ?> pcs</span>
                </div>
                
                <hr style="border: 0; border-top: 1px solid var(--border-color); margin-bottom: 15px;">
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 25px; font-size: 1.15rem; font-weight: 700; color: var(--text-color);">
                    <span>Total Tagihan</span>
                    <span style="color: var(--primary-hover);"><?= formatRupiah($totalHarga) ?></span>
                </div>
                
                <a href="checkout.php" class="btn btn-primary" style="width: 100%; display: block; text-align: center; height: 48px; display:flex; align-items:center; justify-content:center;">
                    Lanjut ke Checkout <i class="fa fa-arrow-right" style="margin-left: 10px;"></i>
                </a>
            </div>
            
        </div>
    <?php else: ?>
        <div style="background-color:#fff; padding: 60px; border-radius: 20px; text-align: center; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
            <i class="fa fa-shopping-bag" style="font-size: 3.5rem; color: var(--primary-color); margin-bottom: 20px; opacity:0.5;"></i>
            <h3 style="font-family: var(--font-heading); color: var(--text-color); margin-bottom: 10px;">Keranjang Masih Kosong</h3>
            <p style="color: var(--text-muted); font-size: 1rem;">Anda belum menambahkan lilin aromaterapi atau essential oil apa pun.</p>
            <a href="produk.php" class="btn btn-primary" style="margin-top: 20px;">Mulai Belanja</a>
        </div>
    <?php endif; ?>
</div>

<script>
function changeQty(btn, amount) {
    const input = btn.parentNode.querySelector('input[name="jumlah"]');
    const form = btn.closest('form');
    let val = parseInt(input.value) || 1;
    val += amount;
    const maxVal = parseInt(input.getAttribute('max')) || 99;
    
    if (val < 1) val = 1;
    if (val > maxVal) val = maxVal;
    
    input.value = val;
    form.submit(); // Automatically submit the form to update quantities
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
