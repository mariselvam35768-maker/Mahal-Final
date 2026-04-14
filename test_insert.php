<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'c:\xampp\htdocs\SLR Mahal\marriage-hall\includes\db.php';

try {
    $booking_id = 'TEST-BK-123';
    // assume user 1 and hall 1 exist
    $user_id = 1;
    $hall_id = 1;
    $event_name = 'Test';
    $event_date = '2026-12-12';
    $slot_id = null; // Full day
    $is_full_day = 1;
    $advance_amount = 5000;

    $insert = $pdo->prepare("
        INSERT INTO bookings 
            (booking_id, user_id, hall_id, event_name, event_date, slot_id, is_full_day, advance_amount, status, payment_status, created_at) 
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', NOW())
    ");

    $insert->execute([
        $booking_id,
        $user_id,
        $hall_id,
        $event_name,
        $event_date,
        $slot_id,
        $is_full_day,
        $advance_amount
    ]);
    
    echo "SUCCESS";
} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage();
}
