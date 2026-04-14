<?php
require_once 'includes/db.php';
try {
    $cats = $pdo->query("SELECT id, name FROM room_categories")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    foreach($cats as $name => $id) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE category_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO rooms (category_id, name, location, capacity, total_rooms, price_per_day, advance_amount, description, facilities, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
            $stmt->execute([$id, "Sample $name", "TBD", 2, 1, 1000, 300, "Sample room for $name category.", "Basic Amenities"]);
            echo "Added sample for $name\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
