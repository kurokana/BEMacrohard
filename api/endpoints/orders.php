<?php
require_once(__DIR__ . '/../db.php');
require_once __DIR__ . '/../middleware/auth.php'; // cek token
require_once __DIR__ . '/../utils/midtrans.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $orders = $pdo->query(
    'SELECT id, customer, product, qty, total, status, snap_token, redirect_url, created_at
     FROM orders ORDER BY created_at DESC LIMIT 20'
  )->fetchAll();

  echo json_encode(['success' => true, 'data' => $orders]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $body = json_decode(file_get_contents('php://input'), true);

  // Set default status to 'pending'
  $status = 'pending';

  $stmt = $pdo->prepare(
    'INSERT INTO orders (customer, product, qty, total, status)
     VALUES (:customer, :product, :qty, :total, :status)'
  );
  $stmt->execute([
    ':customer' => $body['customer'],
    ':product'  => $body['product'],
    ':qty'      => $body['qty'],
    ':total'    => $body['total'],
    ':status'   => $status,
  ]);

  $orderId = $pdo->lastInsertId();

  // Call Midtrans Snap API
  $snapToken = null;
  $redirectUrl = null;
  $errorMsg = null;

  try {
      $midtransOrderId = 'MH-' . $orderId;
      
      $customerDetails = [
          'first_name' => $body['customer'] ?? $authUser['username'],
          'email'      => $authUser['email'] ?? 'customer@example.com'
      ];
      
      $itemDetails = [
          [
              'id'       => 'prod-' . substr(md5($body['product']), 0, 8),
              'price'    => (int) ($body['total'] / $body['qty']),
              'quantity' => (int) $body['qty'],
              'name'     => substr($body['product'], 0, 50),
          ]
      ];

      $snapData = Midtrans::getSnapToken($midtransOrderId, $body['total'], $customerDetails, $itemDetails);
      $snapToken = $snapData['token'] ?? null;
      $redirectUrl = $snapData['redirect_url'] ?? null;

      if ($snapToken && $redirectUrl) {
          $updateStmt = $pdo->prepare('UPDATE orders SET snap_token = ?, redirect_url = ? WHERE id = ?');
          $updateStmt->execute([$snapToken, $redirectUrl, $orderId]);
      }
  } catch (Exception $e) {
      $errorMsg = $e->getMessage();
  }

  $response = [
      'success' => true,
      'id'      => $orderId
  ];

  if ($snapToken && $redirectUrl) {
      $response['snap_token']   = $snapToken;
      $response['redirect_url'] = $redirectUrl;
  } else {
      $response['midtrans_error'] = $errorMsg ?: 'Gagal membuat snap token.';
  }

  echo json_encode($response);
  exit;
}
