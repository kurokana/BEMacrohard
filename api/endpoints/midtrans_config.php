<?php
/* ========================================
   ENDPOINT: MIDTRANS CLIENT CONFIG
======================================== */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

echo json_encode([
    'success' => true,
    'client_key' => MIDTRANS_CLIENT_KEY,
    'is_production' => MIDTRANS_IS_PRODUCTION
]);
exit;
