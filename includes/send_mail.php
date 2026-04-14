<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function getEmailStyle($primaryColor, $gradientStart, $gradientEnd) {
    return "
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f7f6; -webkit-font-smoothing: antialiased; }
        .container { max-width: 600px; margin: 20px auto; padding: 0; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header {
            background: linear-gradient(135deg, {$gradientStart} 0%, {$gradientEnd} 100%);
            color: white; padding: 30px 20px; text-align: center;
        }
        .header img { width: 60px; margin-bottom: 15px; display: inline-block; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .content { padding: 30px; }
        .content p { font-size: 16px; margin-bottom: 20px; color: #444; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0 20px; border: 1px solid #eee; border-radius: 8px; overflow: hidden; }
        table tr td { padding: 15px; border-bottom: 1px solid #f0f0f0; font-size: 15px; }
        table tr td:first-child { background: #fafafa; font-weight: 600; width: 35%; color: #555; }
        table tr:last-child td { border-bottom: none; }
        .status-badge {
            background: {$primaryColor}; color: #fff; padding: 6px 12px; border-radius: 20px;
            font-size: 13px; font-weight: bold; display: inline-block; letter-spacing: 0.5px; white-space: nowrap;
        }
        .footer { background: #f9fafb; padding: 20px; text-align: center; color: #888; font-size: 13px; border-top: 1px solid #eee; }
        .footer strong { color: #555; }
        @media screen and (max-width: 600px) {
            .container { margin: 0; border-radius: 0; box-shadow: none; width: 100%; }
            .header { padding: 25px 15px; }
            .header img { width: 45px; margin-bottom: 10px; }
            .header h1 { font-size: 20px; }
            .content { padding: 20px 15px; }
            table tr td { padding: 10px; font-size: 13px; }
            table tr td:first-child { width: 40%; }
            body { background-color: #ffffff; }
        }
    </style>";
}

function sendBookingConfirmationMail($to_email, $to_name, $booking) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
        $mail->Username = 'thirukumaran18102006@gmail.com'; $mail->Password = 'sqdi hluc nhsg sben';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = 587;
        $mail->setFrom('thirukumaran18102006@gmail.com', 'Sri Lakshmi Residency & Mahal');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Booking Confirmed - ' . $booking['booking_id'];
        $event_date = date('d M Y', strtotime($booking['event_date']));
        $slot_info  = $booking['is_full_day'] ? 'Full Day' : ($booking['slot_name'] ?? 'N/A');

        $style = getEmailStyle('#10b981', '#10b981', '#059669'); // Green

        $mail->Body = "
<!DOCTYPE html>
<html>
<head>{$style}</head>
<body>
    <div class='container'>
        <div class='header'>
            <img src='https://srilakshmiresidencymahal.saegroup.in/assets/images/wedding_illust.svg' alt='Confirmed'>
            <h1>Booking Confirmed</h1>
        </div>
        <div class='content'>
            <p>Dear <strong>{$to_name}</strong>,</p>
            <p>Your booking has been successfully confirmed. Here are your details:</p>
            <table>
                <tr><td>Booking ID</td><td><strong>{$booking['booking_id']}</strong></td></tr>
                <tr><td>Hall</td><td><strong>{$booking['hall_name']}</strong></td></tr>
                <tr><td>Event</td><td><strong>{$booking['event_name']}</strong></td></tr>
                <tr><td>Event Date</td><td><strong>{$event_date}</strong></td></tr>
                <tr><td>Time Slot</td><td><strong>{$slot_info}</strong></td></tr>

                <tr><td>Status</td><td><span class='status-badge'>✅ CONFIRMED</span></td></tr>
            </table>
            <p>Thank you for choosing us. We look forward to making your event memorable!</p>
        </div>
        <div class='footer'>
            <p>For any queries, please contact us.</p>
            <p><strong>Sri Lakshmi Residency & Mahal</strong></p>
            <p>&copy; " . date('Y') . " All rights reserved.</p>
        </div>
    </div>
</body>
</html>";

        $mail->AltBody = "Dear {$to_name}, Your booking {$booking['booking_id']} for {$booking['event_name']} on {$event_date} at {$booking['hall_name']} has been confirmed.";
        $mail->send(); return true;
    } catch (Exception $e) { return false; }
}

function sendBookingProcessingMail($to_email, $to_name, $booking) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
        $mail->Username = 'thirukumaran18102006@gmail.com'; $mail->Password = 'sqdi hluc nhsg sben';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = 587;
        $mail->setFrom('thirukumaran18102006@gmail.com', 'Sri Lakshmi Residency & Mahal');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Booking Processing - ' . $booking['booking_id'];
        $event_date = date('d M Y', strtotime($booking['event_date']));
        $slot_info  = $booking['is_full_day'] ? 'Full Day' : ($booking['slot_name'] ?? 'N/A');

        $style = getEmailStyle('#3b82f6', '#3b82f6', '#2563eb'); // Blue

        $mail->Body = "
<!DOCTYPE html>
<html>
<head>{$style}</head>
<body>
    <div class='container'>
        <div class='header'>
            <img src='https://srilakshmiresidencymahal.saegroup.in/assets/images/wedding_illust.svg' alt='Processing'>
            <h1>Booking Processing</h1>
        </div>
        <div class='content'>
            <p>Dear <strong>{$to_name}</strong>,</p>
            <p>Your booking is currently being processed. Here are your details:</p>
            <table>
                <tr><td>Booking ID</td><td><strong>{$booking['booking_id']}</strong></td></tr>
                <tr><td>Hall</td><td><strong>{$booking['hall_name']}</strong></td></tr>
                <tr><td>Event</td><td><strong>{$booking['event_name']}</strong></td></tr>
                <tr><td>Event Date</td><td><strong>{$event_date}</strong></td></tr>
                <tr><td>Time Slot</td><td><strong>{$slot_info}</strong></td></tr>

                <tr><td>Status</td><td><span class='status-badge'>🔄 PROCESSING</span></td></tr>
            </table>
            <p>Thank you for choosing us. We will update you once your booking is confirmed!</p>
        </div>
        <div class='footer'>
            <p>For any queries, please contact us.</p>
            <p><strong>Sri Lakshmi Residency & Mahal</strong></p>
            <p>&copy; " . date('Y') . " All rights reserved.</p>
        </div>
    </div>
</body>
</html>";

        $mail->AltBody = "Dear {$to_name}, Your booking {$booking['booking_id']} for {$booking['event_name']} on {$event_date} at {$booking['hall_name']} is currently processing.";
        $mail->send(); return true;
    } catch (Exception $e) { return false; }
}

function sendBookingPendingMail($to_email, $to_name, $booking) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
        $mail->Username = 'thirukumaran18102006@gmail.com'; $mail->Password = 'sqdi hluc nhsg sben';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = 587;
        $mail->setFrom('thirukumaran18102006@gmail.com', 'Sri Lakshmi Residency & Mahal');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Booking Pending - ' . $booking['booking_id'];
        $event_date = date('d M Y', strtotime($booking['event_date']));
        $slot_info  = $booking['is_full_day'] ? 'Full Day' : ($booking['slot_name'] ?? 'N/A');

        $style = getEmailStyle('#f59e0b', '#f59e0b', '#d97706'); // Orange

        $mail->Body = "
<!DOCTYPE html>
<html>
<head>{$style}</head>
<body>
    <div class='container'>
        <div class='header'>
            <img src='https://srilakshmiresidencymahal.saegroup.in/assets/images/wedding_illust.svg' alt='Pending'>
            <h1>Booking Pending</h1>
        </div>
        <div class='content'>
            <p>Dear <strong>{$to_name}</strong>,</p>
            <p>Your booking is currently in pending status. Here are your details:</p>
            <table>
                <tr><td>Booking ID</td><td><strong>{$booking['booking_id']}</strong></td></tr>
                <tr><td>Hall</td><td><strong>{$booking['hall_name']}</strong></td></tr>
                <tr><td>Event</td><td><strong>{$booking['event_name']}</strong></td></tr>
                <tr><td>Event Date</td><td><strong>{$event_date}</strong></td></tr>
                <tr><td>Time Slot</td><td><strong>{$slot_info}</strong></td></tr>

                <tr><td>Status</td><td><span class='status-badge'>⏳ PENDING</span></td></tr>
            </table>
            <p>We will review your booking and update you soon. Thank you!</p>
        </div>
        <div class='footer'>
            <p>For any queries, please contact us.</p>
            <p><strong>Sri Lakshmi Residency & Mahal</strong></p>
            <p>&copy; " . date('Y') . " All rights reserved.</p>
        </div>
    </div>
</body>
</html>";
        $mail->AltBody = "Dear {$to_name}, Your booking {$booking['booking_id']} is pending.";
        $mail->send(); return true;
    } catch (Exception $e) { return false; }
}

function sendBookingCancelledMail($to_email, $to_name, $booking) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
        $mail->Username = 'thirukumaran18102006@gmail.com'; $mail->Password = 'sqdi hluc nhsg sben';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = 587;
        $mail->setFrom('thirukumaran18102006@gmail.com', 'Sri Lakshmi Residency & Mahal');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Booking Cancelled - ' . $booking['booking_id'];
        $event_date = date('d M Y', strtotime($booking['event_date']));
        $slot_info  = $booking['is_full_day'] ? 'Full Day' : ($booking['slot_name'] ?? 'N/A');

        $style = getEmailStyle('#ef4444', '#ef4444', '#dc2626'); // Red

        $mail->Body = "
<!DOCTYPE html>
<html>
<head>{$style}</head>
<body>
    <div class='container'>
        <div class='header'>
            <img src='https://srilakshmiresidencymahal.saegroup.in/assets/images/wedding_illust.svg' alt='Cancelled'>
            <h1>Booking Cancelled</h1>
        </div>
        <div class='content'>
            <p>Dear <strong>{$to_name}</strong>,</p>
            <p>Your booking has been cancelled. Here are your details:</p>
            <table>
                <tr><td>Booking ID</td><td><strong>{$booking['booking_id']}</strong></td></tr>
                <tr><td>Hall</td><td><strong>{$booking['hall_name']}</strong></td></tr>
                <tr><td>Event</td><td><strong>{$booking['event_name']}</strong></td></tr>
                <tr><td>Event Date</td><td><strong>{$event_date}</strong></td></tr>
                <tr><td>Time Slot</td><td><strong>{$slot_info}</strong></td></tr>

                <tr><td>Status</td><td><span class='status-badge'>❌ CANCELLED</span></td></tr>
            </table>
            <p>If you have any questions or require a refund, please contact us.</p>
        </div>
        <div class='footer'>
            <p>For any queries, please contact us.</p>
            <p><strong>Sri Lakshmi Residency & Mahal</strong></p>
            <p>&copy; " . date('Y') . " All rights reserved.</p>
        </div>
    </div>
</body>
</html>";
        $mail->AltBody = "Dear {$to_name}, Your booking {$booking['booking_id']} has been cancelled.";
        $mail->send(); return true;
    } catch (Exception $e) { return false; }
}

function sendOTPMail($to_email, $to_name, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
        $mail->Username = 'thirukumaran18102006@gmail.com'; $mail->Password = 'sqdi hluc nhsg sben';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; $mail->Port = 587;
        $mail->setFrom('thirukumaran18102006@gmail.com', 'Sri Lakshmi Residency & Mahal');
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset OTP';

        $style = getEmailStyle('#e91e63', '#e91e63', '#ff4081'); // Pink
        
        $mail->Body = "
<!DOCTYPE html>
<html>
<head>
    {$style}
    <style>
        .otp { font-size: 32px; font-weight: bold; color: #e91e63; letter-spacing: 8px; text-align:center; padding: 25px; background: #fdfdfd; border: 1px dashed #e91e63; border-radius: 8px; margin: 30px 0;}
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Password Reset Request</h1>
        </div>
        <div class='content'>
            <p>Hello <strong>{$to_name}</strong>,</p>
            <p>We received a request to reset your password. Use the OTP below to proceed.</p>
            <div class='otp'>{$otp}</div>
            <p>This OTP is valid for 10 minutes. If you did not request a password reset, please ignore this email and your password will remain unchanged.</p>
        </div>
        <div class='footer'>
            <p><strong>Sri Lakshmi Residency & Mahal</strong></p>
            <p>&copy; " . date('Y') . " All rights reserved.</p>
        </div>
    </div>
</body>
</html>";
        $mail->AltBody = "Hello {$to_name},\nYour Password Reset OTP is: {$otp}\nIt is valid for 10 minutes.";
        $mail->send(); return true;
    } catch (Exception $e) { return false; }
}
?>
