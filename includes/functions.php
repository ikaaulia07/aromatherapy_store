<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection Helper
function getDBConnection() {
    static $pdo_conn = null;
    if ($pdo_conn !== null) {
        return $pdo_conn;
    }

    global $pdo;
    if (isset($pdo) && $pdo instanceof PDO) {
        $pdo_conn = $pdo;
        return $pdo_conn;
    }

    $dbPath = __DIR__ . '/../config/database.php';
    if (file_exists($dbPath)) {
        require $dbPath;
        if (isset($pdo) && $pdo instanceof PDO) {
            $pdo_conn = $pdo;
            return $pdo_conn;
        }
    }
    return null;
}

// Redirect Helper
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Sanitize user inputs
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Format number to Rupiah currency
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
}

// Require Login Guard
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Silakan login terlebih dahulu.'];
        redirect(getAppUrl() . '/auth/login.php');
    }
}

// Require Admin Guard
function requireAdmin() {
    if (!isAdmin()) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Akses ditolak. Halaman ini hanya untuk Admin.'];
        redirect(getAppUrl() . '/index.php');
    }
}

// Get the base URL dynamically
function getAppUrl() {
    $root = '/aromatherapy_store';
    return $root;
}

// Toast or Alert Message helper
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return "<div class='alert alert-{$msg['type']}'>{$msg['text']}</div>";
    }
    return '';
}

// Set Toast Alert
function setFlashMessage($type, $text) {
    $_SESSION['flash_message'] = ['type' => $type, 'text' => $text];
}

// Get Cart Item Count for active user
function getCartCount() {
    if (!isLoggedIn()) return 0;
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT SUM(jumlah) AS count FROM keranjang WHERE id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $res = $stmt->fetch();
        return $res['count'] ? (int)$res['count'] : 0;
    }
    return 0;
}

// Log database and application errors securely
function logError($exception) {
    $logPath = __DIR__ . '/../error.log';
    $message = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine() . "\n";
    @error_log($message, 3, $logPath);
}
?>
