<?php
require_once __DIR__ . '/../db.php';

try {
    // Check if snap_token column exists in orders table
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'snap_token'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        // We use root configuration temporarily or current pdo to run the alter table
        $pdo->exec("ALTER TABLE orders 
            ADD COLUMN snap_token VARCHAR(255) NULL AFTER status,
            ADD COLUMN redirect_url VARCHAR(512) NULL AFTER snap_token;");
        echo "Successfully added snap_token and redirect_url columns to orders table.\n";
    } else {
        echo "Columns snap_token and redirect_url already exist in orders table.\n";
    }
} catch (Exception $e) {
    echo "Error running migration: " . $e->getMessage() . "\n";
}
