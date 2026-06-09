<?php
require_once(__DIR__ . '/../db.php');
require_once __DIR__ . '/../middleware/auth.php'; // cek token

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $orders = $pdo->query(
    'SELECT id, customer, product, qty, total, status, created_at
     FROM orders ORDER BY created_at DESC LIMIT 20'
  )->fetchAll();

  echo json_encode(['success' => true, 'data' => $orders]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $body = json_decode(file_get_contents('php://input'), true);

  $stmt = $pdo->prepare(
    'INSERT INTO orders (customer, product, qty, total, status)
     VALUES (:customer, :product, :qty, :total, :status)'
  );
  $stmt->execute([
    ':customer' => $body['customer'],
    ':product'  => $body['product'],
    ':qty'      => $body['qty'],
    ':total'    => $body['total'],
    ':status'   => $body['status'] ?? 'pending',
  ]);

  echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
  exit;
}
