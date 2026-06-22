<?php
$pageTitle = 'Tentang Kami';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container" style="margin-top: 50px; margin-bottom: 80px;">
    <div class="title-container text-center">
        <h2 class="section-title">Tentang Aromatherapy Store</h2>
        <p class="subtitle">Kisah kami dalam menghadirkan keharuman alami untuk ketenangan rumah Anda</p>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; margin-top: 50px;">
        <div style="position: relative;">
            <div style="width: 100%; height: 400px; border-radius: 24px; background: linear-gradient(135deg, #FFEBEB 0%, #F5E3F7 100%); display:flex; flex-direction:column; align-items:center; justify-content:center; box-shadow: var(--card-shadow); padding: 40px; text-align: center;">
                <i class="fa fa-spa" style="font-size: 5rem; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h3 style="font-family: var(--font-heading); color: var(--text-color); font-size: 1.8rem;">100% Organik & Alami</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 300px; margin-top: 10px;">Kami berkomitmen hanya menggunakan bahan nabati demi keamanan pernapasan Anda.</p>
            </div>
        </div>
        
        <div>
            <h3 style="font-family: var(--font-heading); font-size: 2rem; color: var(--text-color); margin-bottom: 20px;">Kisah Awal Kami</h3>
            <p style="color: var(--text-muted); margin-bottom: 15px; font-size: 0.95rem; line-height: 1.7;">Aromatherapy Store lahir dari kecintaan kami terhadap terapi relaksasi alami. Di tengah hiruk-pikuk kehidupan modern yang penuh tekanan, kami menyadari betapa pentingnya memiliki ruang tenang di dalam rumah untuk memulihkan energi tubuh dan jiwa.</p>
            <p style="color: var(--text-muted); margin-bottom: 15px; font-size: 0.95rem; line-height: 1.7;">Maka dari itu, kami mulai meracik lilin aromaterapi dan essential oil secara manual (handcrafted) menggunakan bahan-bahan alami pilihan yang aman bagi kesehatan pernapasan.</p>
            <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.7;">Setiap produk kami diproduksi secara terbatas untuk menjaga kualitas aroma terapi yang konsisten dan berkelas premium.</p>
        </div>
    </div>
    
    <!-- Values Row -->
    <div style="margin-top: 80px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">
        <!-- Value 1 -->
        <div style="background-color: #fff; padding: 35px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow); text-align: center;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background-color: var(--primary-light); color: var(--primary-color); display: inline-flex; align-items:center; justify-content:center; font-size: 1.5rem; margin-bottom: 20px;">
                <i class="fa fa-heart"></i>
            </div>
            <h4 style="font-family: var(--font-body); font-weight: 600; font-size: 1.15rem; color: var(--text-color); margin-bottom: 10px;">Dibuat dengan Kasih Sayang</h4>
            <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">Lilin kami dituang satu per satu dengan tangan (hand-poured) untuk memastikan kerapian dan kualitas detail produk terbaik.</p>
        </div>
        <!-- Value 2 -->
        <div style="background-color: #fff; padding: 35px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow); text-align: center;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background-color: var(--primary-light); color: var(--primary-color); display: inline-flex; align-items:center; justify-content:center; font-size: 1.5rem; margin-bottom: 20px;">
                <i class="fa fa-leaf"></i>
            </div>
            <h4 style="font-family: var(--font-body); font-weight: 600; font-size: 1.15rem; color: var(--text-color); margin-bottom: 10px;">100% Soy Wax Nabati</h4>
            <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">Menggunakan kedelai organik sebagai bahan dasar lilin sehingga ramah lingkungan dan bebas dari jelaga hitam beracun.</p>
        </div>
        <!-- Value 3 -->
        <div style="background-color: #fff; padding: 35px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow); text-align: center;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background-color: var(--primary-light); color: var(--primary-color); display: inline-flex; align-items:center; justify-content:center; font-size: 1.5rem; margin-bottom: 20px;">
                <i class="fa fa-gift"></i>
            </div>
            <h4 style="font-family: var(--font-body); font-weight: 600; font-size: 1.15rem; color: var(--text-color); margin-bottom: 10px;">Kemasan Cantik Premium</h4>
            <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">Setiap pesanan dikemas dengan kotak estetik berpita, sangat cocok dijadikan kado relaksasi bagi orang terkasih Anda.</p>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
