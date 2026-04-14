<?php
require_once __DIR__ . '/includes/auth_functions.php';

try {
    // Check if rooms table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rooms (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            location VARCHAR(255) NOT NULL,
            capacity INT NOT NULL,
            price_per_day DECIMAL(10, 2) NOT NULL,
            morning_slot_price DECIMAL(10, 2) DEFAULT 0,
            evening_slot_price DECIMAL(10, 2) DEFAULT 0,
            advance_amount DECIMAL(10, 2) DEFAULT 0,
            description TEXT,
            facilities TEXT,
            main_image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    echo "Rooms table created successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
