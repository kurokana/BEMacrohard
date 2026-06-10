<?php
// Set response header to JSON if requested via web browser
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}

require_once __DIR__ . '/db.php';

try {
    // If run via web, output simple plain text or collect info
    $logs = [];
    $logs[] = "=== Database Seeder ===";

    // Drop existing tables to enforce schema updates
    $logs[] = "Dropping existing tables to apply updated schema...";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("DROP TABLE IF EXISTS orders;");
    $pdo->exec("DROP TABLE IF EXISTS users;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 1. Create Users Table
    $logs[] = "Creating 'users' table if not exists...";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'user',
            token VARCHAR(64) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. Create Orders Table
    $logs[] = "Creating 'orders' table if not exists...";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer VARCHAR(100) NOT NULL,
            product VARCHAR(255) NOT NULL,
            qty INT NOT NULL,
            total DECIMAL(10, 2) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            snap_token VARCHAR(255) NULL,
            midtrans_order_id VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 3. Truncate Tables to start fresh
    $logs[] = "Truncating existing tables to avoid duplicate key errors...";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE users;");
    $pdo->exec("TRUNCATE TABLE orders;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 4. Seed Users
    $logs[] = "Seeding users...";
    $users = [
        [
            'username' => 'admin',
            'email' => 'admin@macrohard.com',
            'password' => password_hash('admin123', PASSWORD_BCRYPT),
            'role' => 'admin'
        ],
        [
            'username' => 'john_doe',
            'email' => 'john.doe@macrohard.com',
            'password' => password_hash('user123', PASSWORD_BCRYPT),
            'role' => 'user'
        ],
        [
            'username' => 'jane_doe',
            'email' => 'jane.doe@macrohard.com',
            'password' => password_hash('user123', PASSWORD_BCRYPT),
            'role' => 'user'
        ]
    ];

    $userStmt = $pdo->prepare("
        INSERT INTO users (username, email, password, role)
        VALUES (:username, :email, :password, :role)
    ");

    foreach ($users as $u) {
        $userStmt->execute([
            ':username' => $u['username'],
            ':email' => $u['email'],
            ':password' => $u['password'],
            ':role' => $u['role']
        ]);
        $logs[] = " - Seeded user: {$u['username']} (Password: " . ($u['username'] === 'admin' ? 'admin123' : 'user123') . ")";
    }

    // 5. Seed Orders
    $logs[] = "Seeding orders...";
    $orders = [
        [
            'customer' => 'John Doe',
            'product' => 'Macrohard Book Pro',
            'qty' => 1,
            'total' => 1250.00,
            'status' => 'selesai'
        ],
        [
            'customer' => 'Jane Doe',
            'product' => 'Macrohard Mouse Wireless',
            'qty' => 2,
            'total' => 150.00,
            'status' => 'pending'
        ],
        [
            'customer' => 'Alice Smith',
            'product' => 'Macrohard Keyboard Mechanical',
            'qty' => 1,
            'total' => 299.00,
            'status' => 'selesai'
        ],
        [
            'customer' => 'Bob Johnson',
            'product' => 'Office Suite 365 Personal',
            'qty' => 3,
            'total' => 210.00,
            'status' => 'batal'
        ]
    ];

    $orderStmt = $pdo->prepare("
        INSERT INTO orders (customer, product, qty, total, status)
        VALUES (:customer, :product, :qty, :total, :status)
    ");

    foreach ($orders as $o) {
        $orderStmt->execute([
            ':customer' => $o['customer'],
            ':product' => $o['product'],
            ':qty' => $o['qty'],
            ':total' => $o['total'],
            ':status' => $o['status']
        ]);
        $logs[] = " - Seeded order for: {$o['customer']} ({$o['product']} x {$o['qty']})";
    }

    $logs[] = "Seeding completed successfully!";

    if (php_sapi_name() === 'cli') {
        foreach ($logs as $log) {
            echo $log . "\n";
        }
    } else {
        echo json_encode([
            'success' => true,
            'logs' => $logs
        ], JSON_PRETTY_PRINT);
    }

} catch (PDOException $e) {
    if (php_sapi_name() === 'cli') {
        echo "ERROR: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_PRETTY_PRINT);
    }
}
