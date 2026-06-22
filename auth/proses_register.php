<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($_POST['nama_lengkap']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $telepon = sanitize($_POST['telepon']);
    $alamat = sanitize($_POST['alamat']);

    if (empty($nama) || empty($email) || empty($password) || empty($telepon) || empty($alamat)) {
        setFlashMessage('danger', 'Semua kolom wajib diisi.');
        redirect('register.php');
    }

    $pdo = getDBConnection();
    if ($pdo) {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            setFlashMessage('danger', 'Alamat email sudah terdaftar.');
            redirect('register.php');
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, email, password, telepon, alamat, role) VALUES (?, ?, ?, ?, ?, 'user')");
        if ($stmt->execute([$nama, $email, $hashedPassword, $telepon, $alamat])) {
            setFlashMessage('success', 'Registrasi berhasil! Silakan masuk dengan akun Anda.');
            redirect('login.php');
        } else {
            setFlashMessage('danger', 'Gagal mendaftarkan akun. Silakan coba lagi.');
            redirect('register.php');
        }
    } else {
        setFlashMessage('danger', 'Koneksi database gagal.');
        redirect('register.php');
    }
} else {
    redirect('register.php');
}
?>
