<?php
require_once __DIR__ . '/../includes/functions.php';

$role = isset($_GET['role']) ? $_GET['role'] : '';

if ($role === 'admin') {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
    unset($_SESSION['admin_role']);
    unset($_SESSION['admin_email']);
    setFlashMessage('success', 'Anda telah keluar dari panel Admin.');
    redirect(getAppUrl() . '/index.php');
} elseif ($role === 'user') {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_role']);
    unset($_SESSION['user_email']);
    setFlashMessage('success', 'Anda telah keluar dari akun pelanggan.');
    redirect(getAppUrl() . '/auth/login.php');
} else {
    session_unset();
    session_destroy();
    session_start();
    setFlashMessage('success', 'Anda telah berhasil keluar.');
    redirect(getAppUrl() . '/auth/login.php');
}
?>
