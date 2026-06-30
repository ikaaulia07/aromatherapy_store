<?php
require_once __DIR__ . '/../includes/functions.php';

// Load Midtrans credentials
$configPath = __DIR__ . '/../config/midtrans.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    http_response_code(500);
    echo "Configuration Error";
    exit;
}

// Get the raw JSON body
$jsonStr = file_get_contents('php://input');
$notification = json_decode($jsonStr, true);

if (!$notification) {
    http_response_code(400);
    echo "Bad Request";
    exit;
}

// Extract variables
$order_id = isset($notification['order_id']) ? (int)$notification['order_id'] : 0;
$status_code = isset($notification['status_code']) ? $notification['status_code'] : '';
$gross_amount = isset($notification['gross_amount']) ? $notification['gross_amount'] : '';
$signature_key = isset($notification['signature_key']) ? $notification['signature_key'] : '';
$transaction_status = isset($notification['transaction_status']) ? $notification['transaction_status'] : '';
$payment_type = isset($notification['payment_type']) ? $notification['payment_type'] : '';
$transaction_id = isset($notification['transaction_id']) ? $notification['transaction_id'] : '';

// Verify Signature Key
$payload_signature = $order_id . $status_code . $gross_amount . MIDTRANS_SERVER_KEY;
$expected_signature = hash('sha512', $payload_signature);

if ($signature_key !== $expected_signature) {
    http_response_code(403);
    echo "Invalid Signature";
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo "Database Connection Failure";
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if the order exists
    $stmtOrder = $pdo->prepare("SELECT * FROM pesanan WHERE id_pesanan = ?");
    $stmtOrder->execute([$order_id]);
    $order = $stmtOrder->fetch();

    if ($order) {
        if ($transaction_status === 'settlement' || ($transaction_status === 'capture' && isset($notification['fraud_status']) && $notification['fraud_status'] === 'accept')) {
            // 1. Update order status to 'Diproses'
            $stmtUpdateOrder = $pdo->prepare("UPDATE pesanan SET status = 'Diproses' WHERE id_pesanan = ?");
            $stmtUpdateOrder->execute([$order_id]);

            // 2. Insert/Update payment log
            // Check if payment log already exists
            $stmtCheckPay = $pdo->prepare("SELECT id_pembayaran FROM pembayaran WHERE id_pesanan = ?");
            $stmtCheckPay->execute([$order_id]);
            $payExists = $stmtCheckPay->fetch();

            if ($payExists) {
                $stmtUpdatePay = $pdo->prepare("UPDATE pembayaran SET metode_pembayaran = 'midtrans_json', bukti_pembayaran = ?, tanggal_bayar = NOW(), status_verifikasi = 'Diterima' WHERE id_pesanan = ?");
                $stmtUpdatePay->execute([$jsonStr, $order_id]);
            } else {
                $stmtInsertPay = $pdo->prepare("INSERT INTO pembayaran (id_pesanan, metode_pembayaran, bukti_pembayaran, tanggal_bayar, status_verifikasi) VALUES (?, 'midtrans_json', ?, NOW(), 'Diterima')");
                $stmtInsertPay->execute([$order_id, $jsonStr]);
            }
        } 
        elseif ($transaction_status === 'deny' || $transaction_status === 'expire' || $transaction_status === 'cancel') {
            // Restore product stocks if order was previously pending or active
            if ($order['status'] !== 'Dibatalkan') {
                // 1. Update order status to 'Dibatalkan'
                $stmtUpdateOrder = $pdo->prepare("UPDATE pesanan SET status = 'Dibatalkan' WHERE id_pesanan = ?");
                $stmtUpdateOrder->execute([$order_id]);

                // 2. Fetch order items to restore stock
                $stmtItems = $pdo->prepare("SELECT id_produk, jumlah FROM detail_pesanan WHERE id_pesanan = ?");
                $stmtItems->execute([$order_id]);
                $items = $stmtItems->fetchAll();

                $stmtRestoreStock = $pdo->prepare("UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
                foreach ($items as $item) {
                    $stmtRestoreStock->execute([$item['jumlah'], $item['id_produk']]);
                }

                // 3. Log to pembayaran table
                $stmtCheckPay = $pdo->prepare("SELECT id_pembayaran FROM pembayaran WHERE id_pesanan = ?");
                $stmtCheckPay->execute([$order_id]);
                $payExists = $stmtCheckPay->fetch();

                if ($payExists) {
                    $stmtUpdatePay = $pdo->prepare("UPDATE pembayaran SET metode_pembayaran = 'midtrans_json', bukti_pembayaran = ?, tanggal_bayar = NOW(), status_verifikasi = 'Ditolak' WHERE id_pesanan = ?");
                    $stmtUpdatePay->execute([$jsonStr, $order_id]);
                } else {
                    $stmtInsertPay = $pdo->prepare("INSERT INTO pembayaran (id_pesanan, metode_pembayaran, bukti_pembayaran, tanggal_bayar, status_verifikasi) VALUES (?, 'midtrans_json', ?, NOW(), 'Ditolak')");
                    $stmtInsertPay->execute([$order_id, $jsonStr]);
                }
            }
        }
    }

    $pdo->commit();
    echo "OK";
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logError($e);
    http_response_code(500);
    echo "Internal Server Error";
}
?>
