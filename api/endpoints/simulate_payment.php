<?php
/* ========================================
   ENDPOINT: SIMULATE MIDTRANS WEBHOOK (SANDBOX ONLY)
======================================== */

require_once __DIR__ . '/../db.php';

// Only allow in Sandbox mode for security!
if (MIDTRANS_IS_PRODUCTION) {
    http_response_code(403);
    echo json_encode(['error' => 'Simulator is disabled in production']);
    exit;
}

$orderId = intval($_GET['id'] ?? 0);
$action = $_GET['status'] ?? 'settlement'; // settlement, expire, pending, cancel, deny

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

// Fetch order
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

// Ensure it has a midtrans_order_id
$midtrans_order_id = $order['midtrans_order_id'];
if (!$midtrans_order_id) {
    // Generate a temporary one if none exists (for seeded orders)
    $midtrans_order_id = 'MACROHARD-ORD-' . $order['id'] . '-' . time();
    $pdo->prepare('UPDATE orders SET midtrans_order_id = ? WHERE id = ?')
        ->execute([$midtrans_order_id, $order['id']]);
}

// Format amount to match Midtrans format (typically two decimal places)
$gross_amount = number_format($order['total'], 2, '.', '');
$status_code = '200';
if ($action === 'expire' || $action === 'deny' || $action === 'cancel') {
    $status_code = '202';
}

$server_key = MIDTRANS_SERVER_KEY;
$signature_key = hash('sha512', $midtrans_order_id . $status_code . $gross_amount . $server_key);

$payload = [
    'order_id' => $midtrans_order_id,
    'status_code' => $status_code,
    'gross_amount' => $gross_amount,
    'signature_key' => $signature_key,
    'transaction_status' => $action,
    'fraud_status' => 'accept',
    'transaction_time' => date('Y-m-d H:i:s'),
    'payment_type' => 'credit_card'
];

// Call the webhook endpoint locally inside the container
$ch = curl_init('http://localhost/api/index.php?endpoint=midtrans_webhook');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo json_encode([
    'success' => $http_code === 200,
    'http_code' => $http_code,
    'webhook_response' => json_decode($response, true) ?: $response
]);
exit;
