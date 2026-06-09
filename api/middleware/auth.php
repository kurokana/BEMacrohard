<?php

header('Content-Type: application/json');

$headers = getallheaders();

$auth = $headers['Authorization'] ?? '';

/* ========================================
   VALIDATE BEARER TOKEN
======================================== */

if (
    !$auth ||
    substr($auth, 0, 7) !== 'Bearer '
) {

    http_response_code(401);

    echo json_encode([
        'error' => 'Token tidak ditemukan'
    ]);

    exit;
}

/* ========================================
   EXTRACT TOKEN
======================================== */

$token = substr($auth, 7);

/* ========================================
   VALIDATE TOKEN TO DATABASE
======================================== */

$stmt = $pdo->prepare(
    'SELECT id, username, role
     FROM users
     WHERE token = ?
     LIMIT 1'
);

$stmt->execute([$token]);

$authUser = $stmt->fetch();

/* ========================================
   INVALID TOKEN
======================================== */

if (!$authUser) {

    http_response_code(401);

    echo json_encode([
        'error' => 'Token tidak valid'
    ]);

    exit;
}
