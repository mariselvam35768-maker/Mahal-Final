<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE bookings MODIFY slot_id INT NULL");
    echo "slot_id fixed<br>";
} catch (Exception $e) { echo "slot_id: " . $e->getMessage() . "<br>"; }

try {
    $pdo->exec("ALTER TABLE bookings MODIFY guest_count INT DEFAULT 0");
    echo "guest_count fixed<br>";
} catch (Exception $e) { echo "guest_count: " . $e->getMessage() . "<br>"; }

try {
    $pdo->exec("ALTER TABLE bookings DROP FOREIGN KEY bookings_ibfk_3");
    echo "FK dropped<br>";
} catch (Exception $e) { echo "FK: " . $e->getMessage() . "<br>"; }

echo "Done!";
?>
