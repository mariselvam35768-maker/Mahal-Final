<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE rooms ADD COLUMN total_rooms INT DEFAULT 1 AFTER capacity");
    echo "Column 'total_rooms' added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
