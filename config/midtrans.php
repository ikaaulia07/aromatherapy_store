<?php
// Midtrans Configuration

// Set your Merchant Server Key
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-PLACEHOLDER'); // Ganti dengan Server Key Anda

// Set your Merchant Client Key
define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-PLACEHOLDER'); // Ganti dengan Client Key Anda

// true untuk Production, false untuk Sandbox (Testing)
define('MIDTRANS_IS_PRODUCTION', false); 

// Endpoint API Midtrans
if (MIDTRANS_IS_PRODUCTION) {
    define('MIDTRANS_SNAP_API_URL', 'https://app.midtrans.com/snap/v1/transactions');
} else {
    define('MIDTRANS_SNAP_API_URL', 'https://app.sandbox.midtrans.com/snap/v1/transactions');
}
?>
