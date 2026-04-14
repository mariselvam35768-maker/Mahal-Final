<?php
require_once '../includes/auth_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (empty($booking_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
        exit();
    }

    try {
        // Verify booking belongs to user and is eligible for payment
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE booking_id = ? AND user_id = ? AND status IN ('confirmed', 'processing')");
        $stmt->execute([$booking_id, $user_id]);
        $booking = $stmt->fetch();

        if ($booking) {
            // Update payment status and auto-confirm the booking
            $update = $pdo->prepare("UPDATE bookings SET payment_status = 'paid', status = 'confirmed' WHERE booking_id = ?");
            if ($update->execute([$booking_id])) {
                require_once '../includes/send_mail.php';
                $bk = $pdo->prepare("
                    SELECT b.*, h.name AS hall_name, u.name AS user_name, u.email AS user_email, s.name AS slot_name
                    FROM bookings b
                    JOIN halls h ON b.hall_id = h.id
                    JOIN users u ON b.user_id = u.id
                    LEFT JOIN slots s ON b.slot_id = s.id
                    WHERE b.booking_id = ?
                ");
                $bk->execute([$booking_id]);
                $bk_data = $bk->fetch();
                if ($bk_data) {
                    sendBookingConfirmationMail($bk_data['user_email'], $bk_data['user_name'], $bk_data);
                }
                echo json_encode(['success' => true, 'message' => 'Payment successful and booking confirmed!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Booking not found or not eligible for payment']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
