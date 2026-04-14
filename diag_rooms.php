<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM rooms");
    echo "Room Count: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM room_categories");
    echo "Category Count: " . $stmt->fetchColumn() . "\n";
    
    $rooms = $pdo->query("SELECT * FROM rooms")->fetchAll(PDO::FETCH_ASSOC);
    echo "First Room: " . (empty($rooms) ? 'NONE' : $rooms[0]['name']) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
