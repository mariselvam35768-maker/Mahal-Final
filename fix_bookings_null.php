<?php
require_once 'includes/db.php';
try {
    echo "Starting migration...\n";
    
    // Disable FK checks just in case
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Modify columns to be nullable
    $pdo->exec("ALTER TABLE bookings MODIFY hall_id INT NULL");
    echo "hall_id made nullable.\n";
    
    $pdo->exec("ALTER TABLE bookings MODIFY room_id INT NULL");
    echo "room_id made nullable.\n";

    $pdo->exec("ALTER TABLE bookings MODIFY slot_id INT NULL");
    echo "slot_id made nullable.\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Migration completed successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
