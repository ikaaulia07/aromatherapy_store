<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        setFlashMessage('danger', 'Semua kolom wajib diisi.');
        redirect('login.php');
    }

    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] === 'admin') {
                $_SESSION['admin_id'] = $user['id_user'];
                $_SESSION['admin_name'] = $user['nama_lengkap'];
                $_SESSION['admin_role'] = $user['role'];
                $_SESSION['admin_email'] = $user['email'];

                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['user_name'] = $user['nama_lengkap'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];

                setFlashMessage('success', 'Selamat datang kembali, ' . $user['nama_lengkap'] . ' (Admin)!');
                redirect(getAppUrl() . '/admin/dashboard.php');
            } else {
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['user_name'] = $user['nama_lengkap'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];

                setFlashMessage('success', 'Selamat datang kembali, ' . $user['nama_lengkap'] . '!');
                redirect(getAppUrl() . '/index.php');
            }
        } else {
            setFlashMessage('danger', 'Email atau password salah.');
            redirect('login.php');
        }
    } else {
        setFlashMessage('danger', 'Koneksi database gagal.');
        redirect('login.php');
    }
} else {
    redirect('login.php');
}
?>
