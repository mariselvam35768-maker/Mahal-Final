<?php
require_once 'includes/db.php';

try {
    // Get categories to link rooms
    $cats = $pdo->query("SELECT id, name FROM room_categories")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $vip_cat_id = array_search('VIP Suite', $cats) ?: 1;
    $normal_cat_id = array_search('Standard Room', $cats) ?: 1;

    $rooms = [
        [$vip_cat_id, 'Luxury VIP Suite 101', 'Floor 1', 2, 5, 2500, 1200, 1500, 500, 'Premium room with all facilities.', 'AC,TV,WiFi,Bath'],
        [$normal_cat_id, 'Standard Room 201', 'Floor 2', 2, 10, 1200, 600, 800, 300, 'Comfortable budget room.', 'WiFi,Bath'],
        [$normal_cat_id, 'Single Economy Room 301', 'Floor 3', 1, 15, 800, 400, 500, 200, 'Basic single room.', 'Fan,WiFi']
    ];

    $stmt = $pdo->prepare("INSERT INTO rooms (category_id, name, location, capacity, total_rooms, price_per_day, morning_slot_price, evening_slot_price, advance_amount, description, facilities, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");
    
    foreach ($rooms as $r) {
        $stmt->execute($r);
    }
    echo "Sample rooms added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
