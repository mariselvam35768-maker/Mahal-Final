<?php
require_once 'includes/db.php';

try {
    // 1. Ensure rooms table exists with all columns
    $all_cols = [
        'category_id' => "INT AFTER id",
        'morning_slot_price' => "DECIMAL(10, 2) DEFAULT 0 AFTER price_per_day",
        'evening_slot_price' => "DECIMAL(10, 2) DEFAULT 0 AFTER morning_slot_price",
        'advance_amount' => "DECIMAL(10, 2) DEFAULT 0 AFTER evening_slot_price",
        'total_rooms' => "INT DEFAULT 1 AFTER capacity"
    ];

    foreach ($all_cols as $col => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM rooms LIKE '$col'");
        if (!$stmt->fetch()) {
            echo "Adding column '$col'...\n";
            $pdo->exec("ALTER TABLE rooms ADD COLUMN $col $definition");
        }
    }

    echo "Database schema for rooms reached perfection.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
