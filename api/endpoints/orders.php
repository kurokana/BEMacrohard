<?php
require_once(__DIR__ . '/../db.php');
require_once __DIR__ . '/../middleware/auth.php'; // cek token
require_once __DIR__ . '/../midtrans_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $orders = $pdo->query(
    'SELECT id, customer, product, qty, total, status, snap_token, midtrans_order_id, created_at
     FROM orders ORDER BY created_at DESC LIMIT 20'
  )->fetchAll();

  echo json_encode(['success' => true, 'data' => $orders]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $body = json_decode(file_get_contents('php://input'), true);

  // Scenario A: Request snap token for existing pending order
  if (isset($body['order_id'])) {
    $db_id = intval($body['order_id']);
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$db_id]);
    $order = $stmt->fetch();

    if (!$order) {
      http_response_code(404);
      echo json_encode(['error' => 'Pesanan tidak ditemukan']);
      exit;
    }

    if ($order['status'] !== 'pending') {
      http_response_code(400);
      echo json_encode(['error' => 'Pesanan sudah dibayar atau dibatalkan']);
      exit;
    }

    // Reuse existing valid snap token if present
    if (!empty($order['snap_token'])) {
      echo json_encode([
        'success' => true,
        'id' => $db_id,
        'snap_token' => $order['snap_token'],
        'midtrans_order_id' => $order['midtrans_order_id']
      ]);
      exit;
    }

    // Otherwise, generate a new one
    $midtrans_order_id = 'MACROHARD-ORD-' . $db_id . '-' . time();
    $gross_amount = (int)$order['total'];
  } else {
    // Scenario B: Create a brand new order
    $customer = trim($body['customer'] ?? '');
    $product = trim($body['product'] ?? '');
    $qty = intval($body['qty'] ?? 1);
    $total = floatval($body['total'] ?? 0);

    if (!$customer || !$product || !$total) {
      http_response_code(400);
      echo json_encode(['error' => 'Data pesanan tidak lengkap']);
      exit;
    }

    $stmt = $pdo->prepare(
      'INSERT INTO orders (customer, product, qty, total, status)
       VALUES (:customer, :product, :qty, :total, :status)'
    );
    $stmt->execute([
      ':customer' => $customer,
      ':product'  => $product,
      ':qty'      => $qty,
      ':total'    => $total,
      ':status'   => 'pending',
    ]);
    
    $db_id = $pdo->lastInsertId();
    $midtrans_order_id = 'MACROHARD-ORD-' . $db_id . '-' . time();
    $gross_amount = (int)$total;
  }

  // Request Snap Token from Midtrans
  $midtrans_payload = [
    'transaction_details' => [
      'order_id'     => $midtrans_order_id,
      'gross_amount' => $gross_amount,
    ],
    'customer_details' => [
      'first_name' => $customer ?? ($order['customer'] ?? 'Pelanggan'),
      'email'      => $body['email'] ?? 'customer@macrohard.id',
    ],
    'credit_card' => [
      'secure' => true,
    ],
  ];

  $ch = curl_init(MIDTRANS_SNAP_URL);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($midtrans_payload),
    CURLOPT_HTTPHEADER     => [
      'Accept: application/json',
      'Content-Type: application/json',
      'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':')
    ],
  ]);

  $midtrans_res = curl_exec($ch);
  $midtrans_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $snap_token = null;
  if ($midtrans_http === 201) {
    $res_data = json_decode($midtrans_res, true);
    $snap_token = $res_data['token'] ?? null;
  }

  if ($snap_token) {
    // Save token to DB
    $stmt = $pdo->prepare('UPDATE orders SET snap_token = ?, midtrans_order_id = ? WHERE id = ?');
    $stmt->execute([$snap_token, $midtrans_order_id, $db_id]);

    echo json_encode([
      'success' => true,
      'id' => $db_id,
      'snap_token' => $snap_token,
      'midtrans_order_id' => $midtrans_order_id
    ]);
  } else {
    // Return order ID but flag Midtrans API error
    echo json_encode([
      'success' => true,
      'id' => $db_id,
      'error_midtrans' => 'Gagal membuat token pembayaran: ' . $midtrans_res
    ]);
  }
  exit;
}
