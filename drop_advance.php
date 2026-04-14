<?php
require_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE rooms DROP COLUMN advance_amount");
    echo "Dropped from rooms\n";
} catch (Exception $e) { echo "Error rooms: " . $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE bookings DROP COLUMN advance_amount");
    echo "Dropped from bookings\n";
} catch (Exception $e) { echo "Error bookings: " . $e->getMessage() . "\n"; }

try {
    $pdo->exec("ALTER TABLE halls DROP COLUMN advance_amount");
    echo "Dropped from halls\n";
} catch (Exception $e) { echo "Error halls: " . $e->getMessage() . "\n"; }
?>
