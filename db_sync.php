<?php
require_once 'includes/db.php';
try {
    // Add advance_amount if missing
    try {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN advance_amount DECIMAL(10,2) DEFAULT 0.00 AFTER guest_count");
        echo "Added advance_amount column.\n";
    } catch (PDOException $e) {
        echo "advance_amount column might already exist.\n";
    }

    // Add status if missing
    try {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN status VARCHAR(20) DEFAULT 'pending' AFTER advance_amount");
        echo "Added status column.\n";
    } catch (PDOException $e) {
        echo "status column might already exist.\n";
    }

    // Double check is_full_day
    try {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN is_full_day TINYINT(1) DEFAULT 0 AFTER slot_id");
        echo "Added is_full_day column.\n";
    } catch (PDOException $e) {
        echo "is_full_day column might already exist.\n";
    }

    echo "Database sync complete.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
