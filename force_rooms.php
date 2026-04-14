<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/db.php';

try {
    echo "--- FIXING ROOMS ---\n";
    
    // Ensure room_id column exists in bookings
    try {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS room_id INT AFTER hall_id");
        echo "Column 'room_id' added/checked in bookings table.\n";
    } catch (Exception $e) {
        echo "Note on bookings: " . $e->getMessage() . "\n";
    }
    
    // Check if rooms table is empty
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
    if ($cnt == 0) {
        echo "Populating sample rooms...\n";
        
        // Fetch valid category IDs
        $cats_stmt = $pdo->query("SELECT id, name FROM room_categories");
        $all_cats = $cats_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($all_cats)) {
            $cat_data = [];
            foreach($all_cats as $c) {
                $cat_data[$c['name']] = $c['id'];
            }
            
            // Bridal Rooms
            $cid = $cat_data['Bridal Room'] ?? $all_cats[0]['id'];
            $pdo->exec("INSERT INTO rooms (category_id, name, location, capacity, total_rooms, price_per_day, advance_amount, description, facilities, created_at) 
                        VALUES ($cid, 'Luxury Bridal Suite 101', 'First Floor', 2, 2, 3500, 1000, 'Premium bridal suite with all amenities.', 'AC, Attached Bath, Mirrored Stage', NOW())");
            
            // VIP Suite
            $cid = $cat_data['VIP Suite'] ?? $all_cats[0]['id'];
            $pdo->exec("INSERT INTO rooms (category_id, name, location, capacity, total_rooms, price_per_day, advance_amount, description, facilities, created_at) 
                        VALUES ($cid, 'Executive VIP Room', 'Ground Floor', 2, 4, 3000, 800, 'Standard stay.', 'AC, TV, WiFi', NOW())");
            
            // Guest Rooms
            $cid = $cat_data['Guest Rooms'] ?? $all_cats[0]['id'];
            $pdo->exec("INSERT INTO rooms (category_id, name, location, capacity, total_rooms, price_per_day, advance_amount, description, facilities, created_at) 
                        VALUES ($cid, 'Premium Guest Room 201', 'Second Floor', 3, 10, 1800, 500, 'Standard stay.', 'WiFi, Room Service', NOW())");

            echo "Sample rooms added successfully.\n";
        } else {
            echo "ERROR: No categories found in room_categories table. Please add categories first.\n";
        }
    } else {
        echo "Rooms already exist ($cnt table entries). No action taken.\n";
    }
    
} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}
echo "--- FIX COMPLETE ---\n";
