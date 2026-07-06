<?php
// Midtrans Configuration

// Set your Merchant Server Key
define('MIDTRANS_SERVER_KEY', 'Mid-server-tt4bqmQJKiVdC7r2lPQ21R8J');

// Set your Merchant Client Key
define('MIDTRANS_CLIENT_KEY', 'Mid-client-zFuaRu4W_Hlha1TY');

// true untuk Production, false untuk Sandbox (Testing)
define('MIDTRANS_IS_PRODUCTION', false); 

// Endpoint API Midtrans
if (MIDTRANS_IS_PRODUCTION) {
    define('MIDTRANS_SNAP_API_URL', 'https://app.midtrans.com/snap/v1/transactions');
} else {
    define('MIDTRANS_SNAP_API_URL', 'https://app.sandbox.midtrans.com/snap/v1/transactions');
}
?>
