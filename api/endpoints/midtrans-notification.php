<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../utils/midtrans.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$rawBody = file_get_contents('php://input');
$notification = json_decode($rawBody, true);

if (!$notification) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid Payload']);
    exit;
}

// Verifikasi Signature Key Midtrans untuk keamanan
if (!Midtrans::verifyNotification($notification)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid Signature Key']);
    exit;
}

$midtransOrderId = $notification['order_id'] ?? '';
$transactionStatus = $notification['transaction_status'] ?? '';
$fraudStatus = $notification['fraud_status'] ?? '';

// Bersihkan prefix order ID (dari MH-123 menjadi 123)
$orderId = str_replace('MH-', '', $midtransOrderId);

if (!is_numeric($orderId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid Order ID format']);
    exit;
}

// Cek apakah pesanan ada di database
$stmt = $pdo->prepare('SELECT id, status FROM orders WHERE id = ? LIMIT 1');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Pemetaan status Midtrans ke status lokal database
$status = 'pending';

switch ($transactionStatus) {
    case 'capture':
        if ($fraudStatus === 'challenge') {
            $status = 'challenge';
        } else if ($fraudStatus === 'accept') {
            $status = 'payment_confirmed';
        }
        break;
    case 'settlement':
        $status = 'payment_confirmed';
        break;
    case 'pending':
        $status = 'pending';
        break;
    case 'deny':
    case 'expire':
    case 'cancel':
        $status = 'cancelled';
        break;
    default:
        $status = 'pending';
        break;
}

// Perbarui status di database
$updateStmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
$updateStmt->execute([$status, $orderId]);

echo json_encode([
    'success' => true,
    'message' => 'Notification processed successfully',
    'local_order_id' => $orderId,
    'new_status' => $status
]);
exit;
