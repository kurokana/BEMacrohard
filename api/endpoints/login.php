<?php
require_once(__DIR__ . '/../db.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$body     = json_decode(file_get_contents('php://input'), true);
$username = trim($body['username'] ?? '');
$password = trim($body['password'] ?? '');

if (!$username || !$password) {
  http_response_code(400);
  echo json_encode(['error' => 'Username dan password wajib diisi']);
  exit;
}

$stmt = $pdo->prepare(
  'SELECT id, username, email, password, role FROM users
   WHERE username = :u OR email = :u2 LIMIT 1'
);
$stmt->execute([':u' => $username, ':u2' => $username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Username atau password salah']);
  exit;
}

// Generate simple token (untuk production pakai JWT)
$token = bin2hex(random_bytes(32));

// Simpan token ke DB
$pdo->prepare('UPDATE users SET token = ? WHERE id = ?')
    ->execute([$token, $user['id']]);

echo json_encode([
  'success' => true,
  'token'   => $token,
  'user'    => [
    'id'       => $user['id'],
    'username' => $user['username'],
    'email'    => $user['email'],
    'role'     => $user['role'],
  ]
]);
