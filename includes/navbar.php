<?php
require_once __DIR__ . '/functions.php';
$appUrl = getAppUrl();
$cartCount = getCartCount();
?>
<nav class="navbar">
    <div class="navbar-container container">
        <a href="<?= $appUrl ?>/index.php" class="navbar-brand">
            <span class="brand-rose">Aromatherapy</span> <span class="brand-gold">Store</span>
        </a>
        
        <button class="navbar-toggle" id="js-navbar-toggle" aria-label="Toggle Navigation">
            <i class="fa fa-bars"></i>
        </button>
        
        <ul class="navbar-menu" id="js-navbar-menu">
            <li><a href="<?= $appUrl ?>/index.php" class="nav-link">Home</a></li>
            <li><a href="<?= $appUrl ?>/pages/produk.php" class="nav-link">Produk</a></li>
            <li><a href="<?= $appUrl ?>/pages/tentang.php" class="nav-link">Tentang</a></li>
            <li><a href="<?= $appUrl ?>/pages/kontak.php" class="nav-link">Kontak</a></li>
            
            <?php if (isLoggedIn()): ?>
                <li class="cart-nav">
                    <a href="<?= $appUrl ?>/pages/keranjang.php" class="nav-link cart-icon-container">
                        <i class="fa fa-shopping-bag"></i>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                    <li><a href="<?= $appUrl ?>/admin/dashboard.php" class="nav-link admin-btn"><i class="fa fa-gauge"></i> Admin Panel</a></li>
                <?php endif; ?>
                <li class="dropdown">
                    <a href="#" class="nav-link dropdown-toggle" id="navbarDropdown">
                        <i class="fa fa-user"></i> <?= explode(' ', $_SESSION['user_name'])[0] ?> <i class="fa fa-chevron-down"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="<?= $appUrl ?>/pages/pesanan.php"><i class="fa fa-shopping-bag"></i> Pesanan Saya</a></li>
                        <li><a href="<?= $appUrl ?>/auth/login.php"><i class="fa fa-user-friends"></i> Masuk Akun Lain</a></li>
                        <li><a href="<?= $appUrl ?>/auth/logout.php?role=user"><i class="fa fa-sign-out-alt"></i> Keluar</a></li>
                    </ul>
                </li>
            <?php else: ?>
                <li><a href="<?= $appUrl ?>/auth/login.php" class="nav-link login-btn">Masuk</a></li>
                <li><a href="<?= $appUrl ?>/auth/register.php" class="nav-link register-btn">Daftar</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
