<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://macrohard.informatika.site');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200); exit;
}

// Simple router berdasarkan query string ?endpoint=xxx
$endpoint = $_GET['endpoint'] ?? '';

switch ($endpoint) {
  case 'login':
    require 'endpoints/login.php';
    break;
  case 'orders':
    require 'endpoints/orders.php';
    break;
  case 'users':
    require 'endpoints/users.php';
    break;
  default:
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}
