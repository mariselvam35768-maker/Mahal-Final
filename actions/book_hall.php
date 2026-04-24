<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<?php
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/PHPMailer/Exception.php';
require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../halls.php');
    exit();
}

$user_id        = $_SESSION['user_id'];
$hall_id        = (int)($_POST['hall_id'] ?? 0);
$room_id        = (int)($_POST['room_id'] ?? 0);
$event_name     = trim($_POST['event_name'] ?? '');
$event_date     = trim($_POST['event_date'] ?? '');
$is_full_day    = isset($_POST['is_full_day']) && $_POST['is_full_day'] == '1' ? 1 : 0;
$slot_id        = (!$is_full_day && !empty($_POST['slot_id'])) ? (int)$_POST['slot_id'] : null;


// ===== VALIDATION =====
$errors = [];

if (!$hall_id && !$room_id) { $errors[] = 'Invalid venue or room.'; }
if (empty($event_name)) { $errors[] = 'Event name is required.'; }
if (empty($event_date)) {
    $errors[] = 'Event date is required.';
} elseif (strtotime($event_date) <= strtotime('today')) {
    $errors[] = 'Event date must be in the future.';
}
if (!$is_full_day && !$slot_id) {
    $errors[] = 'Please select a time slot.';
}

if (!empty($errors)) {
    $err_str = urlencode(implode(' ', $errors));
    $redir = $room_id > 0 ? "room_id=$room_id" : "id=$hall_id";
    header("Location: ../halls.php?$redir&error=" . $err_str);
    exit();
}

// ===== CHECK ITEM EXISTS =====
try {
    if ($room_id > 0) {
        $check = $pdo->prepare("SELECT id FROM rooms WHERE id = ?");
        $check->execute([$room_id]);
    } else {
        $check = $pdo->prepare("SELECT id FROM halls WHERE id = ?");
        $check->execute([$hall_id]);
    }
    if (!$check->fetch()) {
        header('Location: ../halls.php?error=invalid_item');
        exit();
    }
} catch (\Exception $e) {
    header('Location: ../halls.php?error=db_error');
    exit();
}

// ===== CHECK SLOT AVAILABILITY =====
$is_available = isSlotAvailable($pdo, $hall_id, $event_date, $slot_id, $is_full_day, $room_id);

// Debug Log for double-booking investigation
error_log("Booking Check - User: $user_id, Hall: $hall_id, Room: $room_id, Date: $event_date, IsFullDay: $is_full_day, Slot: $slot_id, Avail: " . ($is_available ? 'YES' : 'NO'));

if (!$is_available) {
    $redir = $room_id > 0 ? "room_id=$room_id" : "id=$hall_id";
    header("Location: ../halls.php?$redir&error=double_booking");
    exit();
}

// ===== INSERT BOOKING =====
try {
    $booking_id = 'BK-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    $dummy_slot = $pdo->query("SELECT id FROM slots LIMIT 1")->fetchColumn();

    $insert = $pdo->prepare("
        INSERT INTO bookings 
            (booking_id, user_id, hall_id, room_id, event_name, event_date, slot_id, is_full_day, status, payment_status, created_at) 
        VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', NOW())
    ");
    
    $insert_hall_id = $hall_id > 0 ? $hall_id : null;
    $insert_room_id = $room_id > 0 ? $room_id : null;
    
    // For full day bookings, we prefer NULL for slot_id if the DB allows it.
    // If it fails, it might be due to a NOT NULL constraint, so we fallback to dummy_slot only if needed.
    $insert_slot_id = $is_full_day ? null : $slot_id;
    if ($is_full_day && $dummy_slot) {
        // We have a dummy slot, we can use it just in case slot_id is NOT NULL
        $insert_slot_id = $dummy_slot;
    }
    
    $insert->execute([$booking_id, $user_id, $insert_hall_id, $insert_room_id, $event_name, $event_date, $insert_slot_id, $is_full_day]);


    // ===== SEND ADMIN EMAIL NOTIFICATION (SMTP with PHPMailer) =====
    try {
        // Get item info
        if ($room_id > 0) {
            $item_info = $pdo->prepare("SELECT name FROM rooms WHERE id = ?");
            $item_info->execute([$room_id]);
            $item_name = $item_info->fetchColumn();
            $item_label = "Room";
        } else {
            $item_info = $pdo->prepare("SELECT name FROM halls WHERE id = ?");
            $item_info->execute([$hall_id]);
            $item_name = $item_info->fetchColumn();
            $item_label = "Hall";
        }

        // Get user info
        $user_info = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
        $user_info->execute([$user_id]);
        $db_user = $user_info->fetch();
        
        // Use POSTed details if provided, otherwise fallback to DB
        $user = [
            'name'  => trim($_POST['booker_name']  ?? $db_user['name']),
            'email' => trim($_POST['booker_email'] ?? $db_user['email']),
            'phone' => trim($_POST['booker_phone'] ?? $db_user['phone'])
        ];

        // Get slot info
        $slot_stmt = $pdo->prepare("SELECT name FROM slots WHERE id = ?");
        $slot_stmt->execute([$slot_id]);
        $slot_label = $is_full_day ? 'Full Day (9:00am - 11:00pm)' : ($slot_stmt->fetchColumn() ?: 'N/A');

        $date_fmt = date('d M Y', strtotime($event_date));

        // Create PHPMailer instance
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'thirukumaran18102006@gmail.com';               // SMTP username
        $mail->Password   = 'sqdi hluc nhsg sben';                  // SMTP password (use app password for Gmail)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;       // Enable TLS encryption
        $mail->Port       = 587;                                   // TCP port to connect to

        // Recipients
        $mail->setFrom('thirukumaran18102006@gmail.com');
        $mail->addAddress('thirukumaran18102006@gmail.com');                  // Add a recipient
        $mail->addReplyTo('thirukumaran18102006@gmail.com');

        // Content
        $mail->isHTML(true);
        $mail->Subject = "New $item_label Booking: $booking_id";
        
        // HTML body
        $mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            color: #333; 
            line-height: 1.6; 
            margin: 0; 
            padding: 0; 
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .header {
            background: linear-gradient(135deg, #e91e63 0%, #ff4081 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        .header h1 { 
            margin: 0; 
        }
        .header img { 
            width: 80px; 
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table tr td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        table tr td:first-child {
            background: #f3f4f6;
            font-weight: bold;
            width: 40%;
        }
        table tr td:last-child {
            background: white;
        }
        .status-badge {
            background: #fbbf24;
            color: #000;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #7c3aed;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        @media screen and (max-width: 600px) {
            .header {
                flex-direction: column;
            }
            table tr td:first-child {
                width: 35%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="https://srilakshmiresidencymahal.saegroup.in/assets/images/wedding_illust.svg" alt="Booking">
            <h1 style="margin-left: 45px;">New Booking Request</h1>
        </div>
        <div class="content">
            <table>
                <tr>
                    <td>Booking ID</td>
                    <td><strong>' . $booking_id . '</strong></td>
                </tr>
                <tr>
                    <td>' . $item_label . ' Name</td>
                    <td><strong>' . $item_name . '</strong></td>
                </tr>
                <tr>
                    <td>Event Name</td>
                    <td><strong>' . $event_name . '</strong></td>
                </tr>
                <tr>
                    <td>Event Date</td>
                    <td><strong>' . $date_fmt . '</strong></td>
                </tr>
                <tr>
                    <td>Time Slot</td>
                    <td><strong>' . $slot_label . '</strong></td>
                </tr>
                <tr>
                    <td>Current Status</td>
                    <td><span class="status-badge">⏳ PENDING</span></td>
                </tr>
            </table>
            
            <h3 style="margin-top: 30px;">Customer Information</h3>
            <table>
                <tr>
                    <td>Name</td>
                    <td><strong>' . $user['name'] . '</strong></td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td><strong>' . $user['email'] . '</strong></td>
                </tr>
                <tr>
                    <td>Phone</td>
                    <td><strong>' . $user['phone'] . '</strong></td>
                </tr>
            </table>
        </div>
        <div class="footer">
            <p>This is an automated notification. Please do not reply to this email.</p>
            <p>&copy; ' . date('Y') . ' Sri Lakshmi Residency Mahal. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
';
        
        // Plain text alternative for non-HTML mail clients
        $mail->AltBody = "New $item_label Booking: $booking_id\n\n" .
                        "$item_label: $item_name\n" .
                        "Event: $event_name\n" .
                        "Date: $date_fmt\n" .
                        "Slot: $slot_label\n" .
                        "Booked By: {$user['name']}\n" .
                        "Email: {$user['email']}\n" .
                        "Phone: {$user['phone']}\n" .
                        "Status: PENDING\n\n" .
                        "Login to admin panel to review this booking.";

        $mail->send();
        
    } catch (\Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
    }
    // =================================================

    header('Location: ../my_bookings.php?success=1');
    exit();

} catch (\PDOException $e) {
    error_log('Booking error: ' . $e->getMessage());
    $redir = $room_id > 0 ? "room_id=$room_id" : "id=$hall_id";
    die('<div style="font-family:sans-serif; padding: 2rem; border:2px solid red; background:#ffebeb; color:red; max-width:600px; margin: 2rem auto; border-radius:10px;">
        <h2>Database Error</h2>
        <p>There was a critical error saving your booking. Please send this exactly to the developer:</p>
        <pre style="background:white; padding:1rem; border:1px solid #ccc; white-space:pre-wrap;">' . htmlspecialchars($e->getMessage()) . '</pre>
        <br><a href="../halls.php?' . $redir . '" style="padding: 10px 15px; background: red; color: white; text-decoration: none; border-radius: 5px;">Go Back</a>
    </div>');
}
?>
