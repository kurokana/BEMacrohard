<?php
header('Content-Type: application/json');

$http_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed_origins = [
    'https://macrohard.informatika.site',
    'http://100.106.230.82',
    'http://100.127.242.32'
];

if (in_array($http_origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $http_origin");
} else {
    header('Access-Control-Allow-Origin: https://macrohard.informatika.site');
}

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
  case 'midtrans-notification':
    require 'endpoints/midtrans-notification.php';
    break;
  default:
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}
