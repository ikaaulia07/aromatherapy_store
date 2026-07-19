<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

$configPath = __DIR__ . '/../../config/midtrans.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

$id_pesanan = isset($_GET['id_pesanan']) ? (int)$_GET['id_pesanan'] : 0;
if (!$id_pesanan) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID Pesanan tidak valid.']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
    exit;
}

// Cari snap_order_id yang disimpan di database
$stmtOrder = $pdo->prepare("SELECT * FROM pesanan WHERE id_pesanan = ?");
$stmtOrder->execute([$id_pesanan]);
$order = $stmtOrder->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
    exit;
}

$snapOrderId = !empty($order['snap_order_id']) ? $order['snap_order_id'] : null;
if (!$snapOrderId) {
    echo json_encode(['success' => false, 'message' => 'Pesanan ini tidak memiliki ID Midtrans. Pelanggan mungkin belum melakukan checkout ke Midtrans.']);
    exit;
}

// Query ke Midtrans Status API
$apiUrl = MIDTRANS_IS_PRODUCTION
    ? "https://api.midtrans.com/v2/{$snapOrderId}/status"
    : "https://api.sandbox.midtrans.com/v2/{$snapOrderId}/status";

$opts = [
    'http' => [
        'header'  => "Accept: application/json\r\nAuthorization: Basic " . base64_encode(MIDTRANS_SERVER_KEY . ':') . "\r\n",
        'method'  => 'GET',
        'ignore_errors' => true,
    ]
];
$context = stream_context_create($opts);
$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'Gagal menghubungi server Midtrans. Cek koneksi internet.']);
    exit;
}

$data = json_decode($response, true);
$transaction_status = $data['transaction_status'] ?? '';
$fraud_status       = $data['fraud_status'] ?? '';
$payment_type       = $data['payment_type'] ?? 'midtrans';

$isSuccess = ($transaction_status === 'settlement') ||
             ($transaction_status === 'capture' && $fraud_status === 'accept');
$isFailed  = in_array($transaction_status, ['deny', 'expire', 'cancel']);

try {
    $pdo->beginTransaction();

    if ($isSuccess && $order['status'] === 'Pending') {
        // Update pesanan ke Diproses
        $pdo->prepare("UPDATE pesanan SET status = 'Diproses' WHERE id_pesanan = ?")
            ->execute([$id_pesanan]);

        // Insert/Update tabel pembayaran
        $jsonStr = json_encode($data);
        $stmtCheck = $pdo->prepare("SELECT id_pembayaran FROM pembayaran WHERE id_pesanan = ?");
        $stmtCheck->execute([$id_pesanan]);
        $payExists = $stmtCheck->fetch();

        if ($payExists) {
            $pdo->prepare("UPDATE pembayaran SET metode_pembayaran = 'midtrans_json', bukti_pembayaran = ?, tanggal_bayar = NOW(), status_verifikasi = 'Diterima' WHERE id_pesanan = ?")
                ->execute([$jsonStr, $id_pesanan]);
        } else {
            $pdo->prepare("INSERT INTO pembayaran (id_pesanan, metode_pembayaran, bukti_pembayaran, tanggal_bayar, status_verifikasi) VALUES (?, 'midtrans_json', ?, NOW(), 'Diterima')")
                ->execute([$id_pesanan, $jsonStr]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => '✅ Pembayaran LUNAS! Status pesanan berhasil diperbarui menjadi "Diproses".', 'status' => 'settlement']);

    } elseif ($isFailed && $order['status'] === 'Pending') {
        $pdo->prepare("UPDATE pesanan SET status = 'Dibatalkan' WHERE id_pesanan = ?")
            ->execute([$id_pesanan]);

        // Kembalikan stok produk
        $stmtItems = $pdo->prepare("SELECT id_produk, jumlah FROM detail_pesanan WHERE id_pesanan = ?");
        $stmtItems->execute([$id_pesanan]);
        $items = $stmtItems->fetchAll();
        $stmtRestoreStock = $pdo->prepare("UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
        foreach ($items as $item) {
            $stmtRestoreStock->execute([$item['jumlah'], $item['id_produk']]);
        }
        $pdo->commit();
        echo json_encode(['success' => false, 'message' => '❌ Pembayaran GAGAL/KADALUARSA. Pesanan telah dibatalkan dan stok dikembalikan.', 'status' => $transaction_status]);

    } else {
        $pdo->commit();
        $statusLabel = $transaction_status ?: 'pending';
        echo json_encode(['success' => false, 'message' => "ℹ️ Status Midtrans: <b>{$statusLabel}</b>. Pembayaran belum diselesaikan oleh pelanggan.", 'status' => $statusLabel]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    logError($e);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan database: ' . $e->getMessage()]);
}
