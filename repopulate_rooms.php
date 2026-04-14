<?php
require_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS room_id INT AFTER hall_id");
    
    $cats = $pdo->query("SELECT id, name FROM room_categories")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Clear rooms if they are just samples (simple way: check if count is low)
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
    if ($cnt < 5) {
        $pdo->exec("DELETE FROM rooms WHERE 1");
    }
    
    if ($cnt < 5) {
        $sample_data = [
            'Bridal Room' => ['Luxury Bridal Suite', '1st Floor', 3500, 2],
            'Deluxe Room' => ['Deluxe Guest Room 102', '1st Floor', 2500, 5],
            'Economy Room' => ['Budget Stay 201', '2nd Floor', 1200, 10],
            'Guest Rooms' => ['Standard Guest Room 202', '2nd Floor', 1800, 8],
            'Standard Room' => ['Classic Room 301', '3rd Floor', 2000, 12],
            'Suite Rooms' => ['Royale Suite 401', '4th Floor', 4500, 3],
            'VIP Suite' => ['VIP Executive Suite', 'Ground Floor', 5000, 2]
        ];
        
        foreach($sample_data as $cat_name => $data) {
            $cid = $cats[$cat_name] ?? null;
            if ($cid) {
                $stmt = $pdo->prepare("INSERT INTO rooms (category_id, name, location, capacity, total_rooms, price_per_day, advance_amount, description, facilities, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())");
                $stmt->execute([$cid, $data[0], $data[1], 2, $data[3], $data[2], $data[2]*0.3, 'Stay comfortably.', 'AC,TV,WiFi']);
            }
        }
        echo "Successfully populated all 7 categories with sample rooms.\n";
    } else {
        echo "Rooms already exist. Skipping population.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
