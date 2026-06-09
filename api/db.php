<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'macrohard_db');
define('DB_USER', 'macrohard_user');
define('DB_PASS', 'PasswordKuat123!');
define('DB_CHARSET', 'utf8mb4');

try {
  $pdo = new PDO(
    'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
    DB_USER, DB_PASS,
    [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]
  );
} catch (PDOException $e) {
  http_response_code(503);
  die(json_encode(['error' => 'DB error: '.$e->getMessage()]));
}
