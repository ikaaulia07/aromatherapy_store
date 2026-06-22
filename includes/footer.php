<?php
require_once __DIR__ . '/functions.php';
$appUrl = getAppUrl();
?>
<footer class="footer">
    <div class="footer-container container">
        <div class="footer-info">
            <h3 class="footer-logo">Aromatherapy <span class="brand-gold">Store</span></h3>
            <p>Hadirkan ketenangan, kesegaran, dan kenyamanan di setiap sudut rumah Anda melalui koleksi lilin aromaterapi dan essential oil premium buatan tangan kami.</p>
            <div class="social-icons">
                <a href="https://www.instagram.com/ikha__a.a?igsh=MXhsaGhscmR6dGU2aA==" target="_blank"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="https://wa.me/6282341468870" target="_blank"><i class="fab fa-whatsapp"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>
        
        <div class="footer-links">
            <h4>Navigasi</h4>
            <ul>
                <li><a href="<?= $appUrl ?>/index.php">Home</a></li>
                <li><a href="<?= $appUrl ?>/pages/produk.php">Produk</a></li>
                <li><a href="<?= $appUrl ?>/pages/tentang.php">Tentang Kami</a></li>
                <li><a href="<?= $appUrl ?>/pages/kontak.php">Kontak</a></li>
            </ul>
        </div>
        
        <div class="footer-contact">
            <h4>Hubungi Kami</h4>
            <p><i class="fa fa-map-marker-alt"></i> Mataram Kekalik Jaya</p>
            <p><i class="fa fa-phone"></i> 082341468870</p>
            <p><i class="fa fa-envelope"></i> info@aromatherapystore.com</p>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container text-center">
            <p>&copy; <?= date('Y') ?> Aromatherapy Store. All rights reserved. Crafted with love &hearts;</p>
        </div>
    </div>
</footer>

<script src="<?= $appUrl ?>/assets/js/script.js"></script>
</body>
</html>
