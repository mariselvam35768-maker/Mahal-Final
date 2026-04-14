<?php
require_once 'includes/db.php';

$hall_id = 1; // Assuming a hall ID
$room_id = 0;
$date = date('Y-m-d', strtotime('+5 days'));
$is_full_day = 1;

echo "<h3>Diagnostic Check</h3>";
echo "Date: $date<br>";

// 1. Check if available (should be true if first time)
$avail1 = isSlotAvailable($pdo, $hall_id, $date, null, $is_full_day, $room_id);
echo "Initial availability: " . ($avail1 ? 'Available' : 'NOT Available') . "<br>";

// 2. Insert a dummy booking
$pdo->exec("INSERT INTO bookings (booking_id, user_id, hall_id, room_id, event_name, event_date, is_full_day, status) VALUES ('TEST-BK', 1, $hall_id, NULL, 'Test Event', '$date', 1, 'pending')");
echo "Inserted test booking for $date.<br>";

// 3. Check again (should be false now)
$avail2 = isSlotAvailable($pdo, $hall_id, $date, null, $is_full_day, $room_id);
echo "Availability after booking: " . ($avail2 ? 'Available' : 'NOT Available') . "<br>";

if ($avail2) {
    echo "<b style='color:red;'>STILL AVAILABLE? SOMETHING IS WRONG!</b><br>";
    // Let's see the count query manually
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE hall_id = ? AND event_date = ? AND LOWER(status) != 'cancelled'");
    $stmt->execute([$hall_id, $date]);
    $count = $stmt->fetchColumn();
    echo "Manual count check: $count<br>";
} else {
    echo "<b style='color:green;'>SYSTEM PROPERLY BLOCKED THE SECOND BOOKING.</b><br>";
}

// Cleanup
$pdo->exec("DELETE FROM bookings WHERE booking_id = 'TEST-BK'");
