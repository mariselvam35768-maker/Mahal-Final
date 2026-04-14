<?php
require_once 'c:\xampp\htdocs\SLR Mahal\marriage-hall\includes\db.php';
try {
    $pdo->exec("ALTER TABLE bookings MODIFY slot_id INT NULL");
    echo "Success: slot_id is now nullable.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
