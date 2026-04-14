<?php
function logError($msg) {
    file_put_contents(__DIR__ . '/test_insert_error.log', $msg . PHP_EOL, FILE_APPEND);
}

try {
    $host = 'localhost';
    $db   = 'hall_allocation';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    logError("Connected to DB");

    // test insert
    $insert = $pdo->prepare("
        INSERT INTO bookings 
            (booking_id, user_id, hall_id, event_name, event_date, slot_id, is_full_day, advance_amount, status, payment_status, created_at) 
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', NOW())
    ");
    
    // Using NULL for slot_id to simulate Full Day
    $result = $insert->execute([
        'TEST-BK-456', 
        1, 
        1, 
        'Test Event', 
        '2026-10-10', 
        null, 
        1, 
        5000
    ]);
    
    logError("Insert executed: " . ($result ? "Success" : "Failed"));

} catch (PDOException $e) {
    logError("PDOException: " . $e->getMessage());
} catch (Exception $e) {
    logError("Exception: " . $e->getMessage());
}
