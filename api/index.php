<?php
header('Content-Type: application/json');

// Dynamic CORS configuration to support both production domain and local development
$httpOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
  'https://macrohard.informatika.site',
  'http://localhost',
  'http://127.0.0.1'
];
if (in_array($httpOrigin, $allowedOrigins) || preg_match('/^http:\/\/localhost(:\d+)?$/', $httpOrigin) || preg_match('/^http:\/\/127\.0\.0\.1(:\d+)?$/', $httpOrigin)) {
  header("Access-Control-Allow-Origin: $httpOrigin");
} else {
  header('Access-Control-Allow-Origin: https://macrohard.informatika.site');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200); exit;
}

// Load Midtrans Configuration
require_once __DIR__ . '/midtrans_config.php';

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
  case 'midtrans_config':
    require 'endpoints/midtrans_config.php';
    break;
  case 'midtrans_webhook':
    require 'endpoints/midtrans_webhook.php';
    break;
  case 'simulate_payment':
    require 'endpoints/simulate_payment.php';
    break;
  default:
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}
