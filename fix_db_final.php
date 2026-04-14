<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=hall_allocation", 'root', "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM rooms LIKE 'total_rooms'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN total_rooms INT DEFAULT 1 AFTER capacity");
        $msg = "Added 'total_rooms' successfully.";
    } else {
        $msg = "Column 'total_rooms' already exists.";
    }
    file_put_contents('db_fix_status.txt', $msg);
    echo $msg;
} catch (Exception $e) {
    file_put_contents('db_fix_status.txt', "ERROR: " . $e->getMessage());
    echo "ERROR: " . $e->getMessage();
}
