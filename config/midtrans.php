<?php
// Midtrans Configuration

// Cek apakah ada file rahasia lokal (tidak di-push ke GitHub)
if (file_exists(__DIR__ . '/midtrans-secret.php')) {
    require_once __DIR__ . '/midtrans-secret.php';
} else {
    // Kunci dummy agar aman saat di-push ke GitHub
    define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-YOUR-KEY-HERE');
    define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-YOUR-KEY-HERE');
}

// true untuk Production, false untuk Sandbox (Testing)
define('MIDTRANS_IS_PRODUCTION', false); 

// Endpoint API Midtrans
if (MIDTRANS_IS_PRODUCTION) {
    define('MIDTRANS_SNAP_API_URL', 'https://app.midtrans.com/snap/v1/transactions');
} else {
    define('MIDTRANS_SNAP_API_URL', 'https://app.sandbox.midtrans.com/snap/v1/transactions');
}
?>
