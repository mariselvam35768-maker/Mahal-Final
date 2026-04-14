<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/db.php';
try {
    echo "--- CHECKING ROOMS ---\n";
    $stmt = $pdo->query("SELECT * FROM rooms");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Total Rooms in DB: " . count($rooms) . "\n";
    foreach($rooms as $r) {
        echo "- Room ID: {$r['id']}, Name: {$r['name']}, Category: " . ($r['category_id'] ?? 'NULL') . "\n";
    }
    
    echo "\n--- CHECKING CATEGORIES ---\n";
    $stmt = $pdo->query("SELECT * FROM room_categories");
    $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Total Categories: " . count($cats) . "\n";
    foreach($cats as $c) {
        echo "- ID: {$c['id']}, Name: {$c['name']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
