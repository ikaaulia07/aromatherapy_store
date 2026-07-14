<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = getDBConnection();
$pdo->exec("UPDATE pesanan SET snap_token = NULL WHERE status = 'Pending'");
echo "All tokens cleared. Please go back to checkout page.";
