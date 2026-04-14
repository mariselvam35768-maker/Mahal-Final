<?php
require_once 'includes/db.php';

try {
    echo "Running Full Room System Repair...\n";
    $pdo->exec("
        ALTER TABLE rooms 
        ADD COLUMN IF NOT EXISTS morning_slot_price DECIMAL(10, 2) DEFAULT 0 AFTER price_per_day,
        ADD COLUMN IF NOT EXISTS evening_slot_price DECIMAL(10, 2) DEFAULT 0 AFTER morning_slot_price,
        ADD COLUMN IF NOT EXISTS advance_amount DECIMAL(10, 2) DEFAULT 0 AFTER evening_slot_price;
    ");
    echo "Columns added successfully.\n";
} catch (Exception $e) {
    echo "Error adding columns: " . $e->getMessage() . "\n";
    // If ADD COLUMN IF NOT EXISTS is not supported by their version:
    try {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN morning_slot_price DECIMAL(10, 2) DEFAULT 0 AFTER price_per_day");
        $pdo->exec("ALTER TABLE rooms ADD COLUMN evening_slot_price DECIMAL(10, 2) DEFAULT 0 AFTER morning_slot_price");
        $pdo->exec("ALTER TABLE rooms ADD COLUMN advance_amount DECIMAL(10, 2) DEFAULT 0 AFTER evening_slot_price");
        echo "Alternative addition successful.\n";
    } catch (Exception $e2) {
        echo "Alternative Error: " . $e2->getMessage() . "\n";
    }
}
