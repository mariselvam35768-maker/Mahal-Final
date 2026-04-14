<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("SELECT * FROM bookings WHERE event_date >= CURDATE() ORDER BY event_date ASC");
    $rows = $stmt->fetchAll();
    echo "<h3>Current Bookings (from today onwards)</h3>";
    echo "<table border='1'><tr><th>ID</th><th>User</th><th>Hall</th><th>Room</th><th>Date</th><th>Status</th></tr>";
    foreach ($rows as $r) {
        echo "<tr><td>{$r['booking_id']}</td><td>{$r['user_id']}</td><td>{$r['hall_id']}</td><td>{$r['room_id']}</td><td>{$r['event_date']}</td><td>{$r['status']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>Room Inventory</h3>";
    $stmt2 = $pdo->query("SELECT id, name, total_rooms FROM rooms");
    $rooms = $stmt2->fetchAll();
    echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Total Rooms</th></tr>";
    foreach ($rooms as $rm) {
        echo "<tr><td>{$rm['id']}</td><td>{$rm['name']}</td><td>{$rm['total_rooms']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
