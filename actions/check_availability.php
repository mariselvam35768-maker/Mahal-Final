<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$room_id = (int)($_GET['room_id'] ?? 0);
$hall_id = (int)($_GET['hall_id'] ?? 0);
$date    = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode(['error' => 'Date is required']);
    exit();
}

try {
    if ($room_id > 0) {
        // Fetch total allowed for this room type
        $stmt = $pdo->prepare("SELECT total_rooms FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $total_rooms = (int)$stmt->fetchColumn();

        // Count non-cancelled bookings for this date and room type
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND event_date = ? AND LOWER(status) != 'cancelled'");
        $stmt->execute([$room_id, $date]);
        $booked_count = (int)$stmt->fetchColumn();

        $available = max(0, $total_rooms - $booked_count);
        
        echo json_encode([
            'available' => $available,
            'total' => $total_rooms,
            'booked' => $booked_count,
            'message' => $available > 0 ? "$available Rooms Available" : "Sold Out"
        ]);
    } elseif ($hall_id > 0) {
        // For halls, check full day and slots
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE hall_id = ? AND event_date = ? AND LOWER(status) != 'cancelled'");
        $stmt->execute([$hall_id, $date]);
        $booked = (int)$stmt->fetchColumn();
        
        $available = $booked == 0 ? 1 : 0;
        echo json_encode([
            'available' => $available,
            'message' => $available > 0 ? "Hall Available" : "Already Booked"
        ]);
    } else {
        echo json_encode(['error' => 'Invalid ID']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
