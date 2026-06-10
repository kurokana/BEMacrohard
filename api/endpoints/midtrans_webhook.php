<?php
/* ========================================
   ENDPOINT: MIDTRANS NOTIFICATION WEBHOOK
======================================== */

require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw_payload = file_get_contents('php://input');
$body = json_decode($raw_payload, true);

if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

$order_id = $body['order_id'] ?? '';
$status_code = $body['status_code'] ?? '';
$gross_amount = $body['gross_amount'] ?? '';
$input_signature = $body['signature_key'] ?? '';
$transaction_status = $body['transaction_status'] ?? '';
$fraud_status = $body['fraud_status'] ?? '';

// Verify Signature Key
$server_key = MIDTRANS_SERVER_KEY;
$expected_signature = hash('sha512', $order_id . $status_code . $gross_amount . $server_key);

if ($input_signature !== $expected_signature) {
    http_response_code(403);
    echo json_encode([
        'error' => 'Signature mismatch',
        'debug' => [
            'received' => $input_signature,
            'expected' => $expected_signature
        ]
    ]);
    exit;
}

// Extract database order ID
// Expected format: MACROHARD-ORD-<db_id>-<timestamp>
$dbOrderId = null;
if (preg_match('/^MACROHARD-ORD-(\d+)-/', $order_id, $matches)) {
    $dbOrderId = intval($matches[1]);
} else {
    // Fallback to integer conversion
    $dbOrderId = intval($order_id);
}

// Map Midtrans status to our status: pending, selesai, batal
$status = 'pending';

if ($transaction_status === 'capture') {
    if ($fraud_status === 'challenge') {
        $status = 'pending';
    } else if ($fraud_status === 'accept') {
        $status = 'selesai';
    }
} else if ($transaction_status === 'settlement') {
    $status = 'selesai';
} else if (in_array($transaction_status, ['cancel', 'deny', 'expire'])) {
    $status = 'batal';
} else if ($transaction_status === 'pending') {
    $status = 'pending';
}

// Update Database
$stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
$stmt->execute([$status, $dbOrderId]);

echo json_encode([
    'success' => true,
    'order_id' => $dbOrderId,
    'status_updated_to' => $status
]);
exit;
