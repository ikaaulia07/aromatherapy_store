<?php
$pageTitle = 'Hubungi Kami';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container" style="margin-top: 50px; margin-bottom: 80px;">
    <div class="title-container text-center">
        <h2 class="section-title">Hubungi Kami</h2>
        <p class="subtitle">Kami siap membantu menjawab semua pertanyaan Anda mengenai koleksi aromaterapi kami</p>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 50px; align-items: start; margin-top: 40px;">
        
        <!-- Contact Information -->
        <div style="background-color: #fff; padding: 40px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
            <h3 style="font-family: var(--font-heading); font-size: 1.6rem; color: var(--text-color); margin-bottom: 25px;">Informasi Kontak</h3>
            
            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 25px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background-color: var(--primary-light); color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                    <i class="fa fa-map-marker-alt"></i>
                </div>
                <div>
                    <h4 style="font-family: var(--font-body); font-weight: 600; font-size: 0.95rem; color: var(--text-color); margin-bottom: 5px;">Alamat Toko</h4>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Mataram Kekalik Jaya</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 25px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background-color: var(--primary-light); color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                    <i class="fa fa-phone-alt"></i>
                </div>
                <div>
                    <h4 style="font-family: var(--font-body); font-weight: 600; font-size: 0.95rem; color: var(--text-color); margin-bottom: 5px;">Nomor Telepon / WhatsApp</h4>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">082341468870</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 25px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background-color: var(--primary-light); color: var(--primary-color); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                    <i class="fa fa-envelope"></i>
                </div>
                <div>
                    <h4 style="font-family: var(--font-body); font-weight: 600; font-size: 0.95rem; color: var(--text-color); margin-bottom: 5px;">E-mail Resmi</h4>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">support@aromatherapystore.com</p>
                </div>
            </div>
            
            <hr style="border:0; border-top:1px solid var(--border-color); margin: 30px 0;">
            
            <h4 style="font-family: var(--font-body); font-weight: 600; font-size: 0.95rem; color: var(--text-color); margin-bottom: 15px;">Jam Operasional Toko</h4>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 8px;"><strong>Senin - Jumat:</strong> 09.00 - 18.00 WIB</p>
            <p style="color: var(--text-muted); font-size: 0.9rem;"><strong>Sabtu - Minggu:</strong> 10.00 - 15.00 WIB</p>
        </div>
        
        <!-- Contact Form -->
        <div style="background-color: #fff; padding: 40px; border-radius: 20px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow);">
            <h3 style="font-family: var(--font-heading); font-size: 1.6rem; color: var(--text-color); margin-bottom: 25px;">Kirim Pesan</h3>
            
            <form action="#" method="POST" onsubmit="alert('Pesan Anda telah dikirim! Kami akan menghubungi Anda kembali sesegera mungkin.'); return false;">
                <div class="form-group">
                    <label for="name" class="form-label">Nama Lengkap</label>
                    <input type="text" id="name" class="form-control" placeholder="Nama Anda" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Alamat Email</label>
                    <input type="email" id="email" class="form-control" placeholder="nama@email.com" required>
                </div>
                
                <div class="form-group">
                    <label for="subject" class="form-label">Subjek Pesan</label>
                    <input type="text" id="subject" class="form-control" placeholder="Subjek / Hal" required>
                </div>
                
                <div class="form-group">
                    <label for="message" class="form-label">Isi Pesan</label>
                    <textarea id="message" class="form-control" rows="5" placeholder="Tuliskan pesan Anda di sini..." required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width:100%; height:48px; margin-top:10px;">Kirim Pesan</button>
            </form>
        </div>
        
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
