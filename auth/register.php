<?php
$pageTitle = 'Daftar Akun Baru';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect($appUrl . '/index.php');
}
?>

<div class="container">
    <div class="auth-wrapper" style="max-width: 500px;">
        <div class="auth-header">
            <h2>Daftar Akun Baru</h2>
            <p class="subtitle">Mulai rasakan kenyamanan terapi aroma di rumah Anda</p>
        </div>
        
        <?= getFlashMessage(); ?>
        
        <form action="proses_register.php" method="POST">
            <div class="form-group">
                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" placeholder="Nama lengkap Anda" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="nama@email.com" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <div class="form-group">
                <label for="telepon" class="form-label">Nomor Telepon / WA</label>
                <input type="text" name="telepon" id="telepon" class="form-control" placeholder="Contoh: 0812XXXXXXXX" required>
            </div>
            
            <div class="form-group">
                <label for="alamat" class="form-label">Alamat Pengiriman Lengkap</label>
                <textarea name="alamat" id="alamat" class="form-control" rows="3" placeholder="Tuliskan alamat lengkap pengiriman paket Anda..." required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block" style="width: 100%; margin-top: 10px;">Daftar</button>
        </form>
        
        <div class="auth-footer">
            Sudah punya akun? <a href="login.php">Masuk Di Sini</a>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
