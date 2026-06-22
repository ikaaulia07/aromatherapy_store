<?php
$pageTitle = 'Masuk Akun';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<div class="container">
    <div class="auth-wrapper">
        <div class="auth-header">
            <h2>Selamat Datang Kembali</h2>
            <p class="subtitle">Masuk untuk mulai merilekskan pikiran Anda</p>
        </div>
        
        <?= getFlashMessage(); ?>
        
        <?php if (isLoggedIn() && !isAdmin()): ?>
            <div class="alert alert-warning" style="border-left: 5px solid var(--accent-gold); background-color: #FFF9F0; color: #B25E00; margin-bottom: 20px; font-size: 0.9rem;">
                <i class="fa fa-info-circle"></i> Anda masuk sebagai pelanggan (<strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>). Masuk dengan akun Admin di bawah untuk mengakses panel kontrol.
            </div>
        <?php endif; ?>
        
        <form action="proses_login.php" method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="nama@email.com" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block" style="width: 100%; margin-top: 10px;">Masuk</button>
        </form>
        
        <div class="auth-footer">
            Belum punya akun? <a href="register.php">Daftar Sekarang</a>
        </div>
    </div>
</div>

<canvas id="aroma-canvas"></canvas>

<style>
/* Local Login Page Animation Styles */
#aroma-canvas {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    pointer-events: none;
}

.form-control {
    transition: all 0.3s ease;
}

.form-control:focus {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 90, 121, 0.12);
}

.btn-primary {
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // --- 1. AROMA MIST FLOATING PARTICLES ---
    const canvas = document.getElementById("aroma-canvas");
    if (canvas) {
        const ctx = canvas.getContext("2d");
        let width = (canvas.width = window.innerWidth);
        let height = (canvas.height = window.innerHeight);

        window.addEventListener("resize", () => {
            width = (canvas.width = window.innerWidth);
            height = (canvas.height = window.innerHeight);
        });

        class AromaMist {
            constructor() {
                this.reset();
                this.y = Math.random() * height; // Distribute initial state
            }

            reset() {
                this.x = Math.random() * width;
                this.y = height + Math.random() * 100;
                this.radius = Math.random() * 20 + 8;
                this.speedY = Math.random() * 0.4 + 0.15;
                this.speedX = Math.random() * 0.3 - 0.15;
                
                // Aromatherapy premium pastel colors (Rose, Lavender, Peach Gold)
                const colors = [
                    "rgba(255, 90, 121, 0.05)",  // Soft Rose
                    "rgba(168, 85, 247, 0.05)",  // Soft Lavender
                    "rgba(245, 158, 11, 0.04)",  // Soft Amber Gold
                    "rgba(255, 182, 193, 0.05)"  // Blossom Pink
                ];
                this.color = colors[Math.floor(Math.random() * colors.length)];
                this.angle = Math.random() * Math.PI * 2;
                this.angleSpeed = Math.random() * 0.01 + 0.005;
            }

            update() {
                this.y -= this.speedY;
                this.angle += this.angleSpeed;
                this.x += this.speedX + Math.sin(this.angle) * 0.15;

                if (this.y < -this.radius || this.x < -this.radius || this.x > width + this.radius) {
                    this.reset();
                }
            }

            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.fill();

                // Outer soft aroma glow
                const grad = ctx.createRadialGradient(
                    this.x, this.y, this.radius * 0.1,
                    this.x, this.y, this.radius
                );
                grad.addColorStop(0, this.color);
                grad.addColorStop(1, "rgba(255, 255, 255, 0)");
                ctx.fillStyle = grad;
                ctx.fill();
            }
        }

        const mists = [];
        const totalMists = 25; // Balanced performance and aesthetics
        for (let i = 0; i < totalMists; i++) {
            mists.push(new AromaMist());
        }

        function drawLoop() {
            ctx.clearRect(0, 0, width, height);
            mists.forEach((mist) => {
                mist.update();
                mist.draw();
            });
            requestAnimationFrame(drawLoop);
        }
        drawLoop();
    }

    // --- 2. FORM INTERACTION ---
    const form = document.querySelector("form");
    if (form) {
        form.addEventListener("submit", () => {
            const submitBtn = form.querySelector("button[type='submit']");
            if (submitBtn) {
                submitBtn.classList.add("btn-loading");
            }
        });
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
