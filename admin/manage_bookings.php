<?php
require_once '../includes/auth_functions.php';
require_once '../includes/send_mail.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

$msg = '';
$error = '';

// ===== AUTO-MIGRATION: Ensure special_requests column exists =====
if (isset($pdo) && $pdo) {
    try {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS special_requests TEXT AFTER status");
        $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS room_id INT AFTER hall_id");
    } catch (Exception $e) {}
}

// ===== HANDLE ADD BOOKING (ADMIN CREATING FOR GUEST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_booking'])) {
    $guest_name  = trim($_POST['guest_name']);
    $guest_email = trim($_POST['guest_email']);
    $guest_phone = trim($_POST['guest_phone']);
    $item_id     = (int)$_POST['item_id'];
    $item_type   = $_POST['item_type']; // 'hall' or 'room'
    $event_name  = trim($_POST['event_name']);
    $event_date  = $_POST['event_date'];
    $specialty   = trim($_POST['specialty'] ?? '');
    
    try {
        // 1. Check/Create User
        $user_id = 0;
        $u_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $u_stmt->execute([$guest_email]);
        $user_id = $u_stmt->fetchColumn();
        
        if (!$user_id) {
            $temp_pass = password_hash('welcome123', PASSWORD_DEFAULT);
            $create_stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
            $create_stmt->execute([$guest_name, $guest_email, $guest_phone, $temp_pass]);
            $user_id = $pdo->lastInsertId();
            $msg = "New guest account created (password: welcome123). ";
        }
        
        // 2. Check Availability (Simplified for admin)
        // 3. Insert Booking
        $hall_id = ($item_type === 'hall') ? $item_id : NULL;
        $room_id = ($item_type === 'room') ? $item_id : NULL;
        $booking_id = 'BK-' . strtoupper(substr(uniqid(), -8));
        $slot_id = ($item_type === 'hall') ? 1 : 0; // Defaults
        $is_full_day = ($item_type === 'hall') ? 1 : 0;
        
        $ins = $pdo->prepare("
            INSERT INTO bookings (booking_id, user_id, hall_id, room_id, event_name, event_date, slot_id, is_full_day, guest_count, status, payment_status, special_requests)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'paid', ?)
        ");
        
        if ($ins->execute([$booking_id, $user_id, $hall_id, $room_id, $event_name, $event_date, $slot_id, $is_full_day, 0, $specialty])) {
            $msg .= "Booking created successfully!";
        } else {
            $error = "Failed to create booking.";
        }
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
}

// ===== HANDLE EDIT BOOKING =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking'])) {
    $id = (int)$_POST['id'];
    $event_name = trim($_POST['event_name']);
    $event_date = $_POST['event_date'];
    $p_status = $_POST['payment_status'];

    try {
        // Fetch old payment status
        $old = $pdo->prepare("SELECT payment_status FROM bookings WHERE id = ?");
        $old->execute([$id]);
        $old_payment = $old->fetchColumn();

        // If payment changed to paid, auto-confirm the booking
        $new_status_sql = ($p_status === 'paid' && $old_payment !== 'paid') ? ", status = 'confirmed'" : "";

        $stmt = $pdo->prepare("UPDATE bookings SET event_name = ?, event_date = ?, payment_status = ? {$new_status_sql} WHERE id = ?");
        if ($stmt->execute([$event_name, $event_date, $p_status, $id])) {
            $msg = "Booking updated successfully!";

            // Send confirmation mail if payment just became paid
            if ($p_status === 'paid' && $old_payment !== 'paid') {
                $bk = $pdo->prepare("
                    SELECT b.*, h.name AS hall_name, r.name AS room_name, u.name AS user_name, u.email AS user_email, s.name AS slot_name
                    FROM bookings b
                    LEFT JOIN halls h ON b.hall_id = h.id
                    LEFT JOIN rooms r ON b.room_id = r.id
                    JOIN users u ON b.user_id = u.id
                    LEFT JOIN slots s ON b.slot_id = s.id
                    WHERE b.id = ?
                ");
                $bk->execute([$id]);
                $booking = $bk->fetch();
                if ($booking) {
                    $sent = sendBookingConfirmationMail($booking['user_email'], $booking['user_name'], $booking);
                    $msg .= $sent ? ' Confirmation email sent to ' . htmlspecialchars($booking['user_email']) . '.' : ' (Email sending failed.)';
                }
            }
        } else {
            $error = "Failed to update booking.";
        }
    } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
}

// ===== HANDLE STATUS ACTIONS =====
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $bk_id  = (int)$_GET['id'];

    $allowed = ['confirm' => 'confirmed', 'cancel' => 'cancelled', 'process' => 'processing', 'pending' => 'pending'];
    if (array_key_exists($action, $allowed)) {
        try {
            $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?")->execute([$allowed[$action], $bk_id]);
            $msg = 'Booking status updated to ' . ucfirst($allowed[$action]) . '.';

            // Send relevant status mail
            $bk = $pdo->prepare("
                SELECT b.*, h.name AS hall_name, r.name AS room_name, u.name AS user_name, u.email AS user_email, s.name AS slot_name
                FROM bookings b
                LEFT JOIN halls h ON b.hall_id = h.id
                LEFT JOIN rooms r ON b.room_id = r.id
                JOIN users u ON b.user_id = u.id
                LEFT JOIN slots s ON b.slot_id = s.id
                WHERE b.id = ?
            ");
            $bk->execute([$bk_id]);
            $booking = $bk->fetch();
            
            if ($booking) {
                $sent = false;
                if ($action === 'confirm') {
                    $sent = sendBookingConfirmationMail($booking['user_email'], $booking['user_name'], $booking);
                } elseif ($action === 'process') {
                    $sent = sendBookingProcessingMail($booking['user_email'], $booking['user_name'], $booking);
                } elseif ($action === 'cancel') {
                    $sent = sendBookingCancelledMail($booking['user_email'], $booking['user_name'], $booking);
                } elseif ($action === 'pending') {
                    $sent = sendBookingPendingMail($booking['user_email'], $booking['user_name'], $booking);
                }
                $msg .= $sent ? ' Notification email sent to ' . htmlspecialchars($booking['user_email']) . '.' : ' (Email sending failed.)';
            }
        } catch (Exception $e) { $error = 'Failed to update booking.'; }
    }
}

// ===== FILTERS =====
$filter_status = $_GET['status'] ?? '';
$filter_hall   = $_GET['hall_id'] ?? '';
$filter_search = trim($_GET['search'] ?? '');
$filter_payment = $_GET['payment'] ?? '';

$query = "
    SELECT b.*, 
           h.name AS hall_name, h.location AS hall_location,
           r.name AS room_name, r.location AS room_location,
           u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
           s.name AS slot_name, s.start_time, s.end_time
    FROM bookings b
    LEFT JOIN halls h ON b.hall_id = h.id
    LEFT JOIN rooms r ON b.room_id = r.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN slots s ON b.slot_id = s.id
    WHERE 1=1
";
$params = [];

if ($filter_status) {
    $query .= " AND b.status = ?";
    $params[] = $filter_status;
}
if ($filter_hall) {
    $query .= " AND b.hall_id = ?";
    $params[] = (int)$filter_hall;
}
if ($filter_payment) {
    $query .= " AND b.payment_status = ?";
    $params[] = $filter_payment;
}
if ($filter_search) {
    $query .= " AND (b.booking_id LIKE ? OR u.name LIKE ? OR h.name LIKE ? OR r.name LIKE ? OR b.event_name LIKE ?)";
    $params = array_merge($params, ["%$filter_search%", "%$filter_search%", "%$filter_search%", "%$filter_search%", "%$filter_search%"]);
}

$query .= " ORDER BY b.created_at DESC";

$bookings = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {}

// Stats
$stats = ['total'=>0, 'pending'=>0, 'processing'=>0, 'confirmed'=>0, 'cancelled'=>0];
try {
    $s = $pdo->query("SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status")->fetchAll();
    foreach ($s as $row) {
        $stats[$row['status']] = $row['cnt'];
    }
    $stats['total'] = array_sum([$stats['pending'] ?? 0, $stats['processing'] ?? 0, $stats['confirmed'] ?? 0, $stats['cancelled'] ?? 0]);
} catch (Exception $e) {}

// Halls & Rooms for selection
$all_halls = [];
$all_rooms = [];
try {
    $all_halls = $pdo->query("SELECT id, name FROM halls ORDER BY name ASC")->fetchAll();
    $all_rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings | Sri Lakshmi Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); }
        .filter-bar { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr auto; gap: 0.75rem; }
        .status-select { 
            border: 1px solid transparent; 
            appearance: none; 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 0.8em;
            padding-right: 1.8rem !important;
            transition: all 0.2s;
        }
        .status-select:focus { border-color: var(--primary) !important; outline: none; box-shadow: 0 0 0 3px var(--primary-light); }
        .status-select option { background: white; color: var(--dark); } /* Reset options so they are readable */
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include '_sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Manage Bookings</h2>
                <span style="font-size:0.78rem; color:var(--gray); margin-top:0.2rem;"><?php echo count($bookings); ?> bookings found</span>
            </div>
            <button class="btn btn-primary btn-sm" onclick="openAddModal()"><i class="fas fa-plus"></i> Add New Booking</button>
        </div>

        <div class="admin-content">
            <?php if ($msg): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
                <?php foreach ([
                    ['Total', $stats['total'], '#e91e63', '#fce7f3', 'fas fa-list'],
                    ['Pending', $stats['pending'] ?? 0, '#f59e0b', '#fef3c7', 'fas fa-hourglass-half'],
                    ['Processing', $stats['processing'] ?? 0, '#3b82f6', '#dbeafe', 'fas fa-sync fa-spin'],
                    ['Confirmed', $stats['confirmed'] ?? 0, '#10b981', '#d1fae5', 'fas fa-check-circle'],
                    ['Cancelled', $stats['cancelled'] ?? 0, '#ef4444', '#fee2e2', 'fas fa-times-circle'],
                ] as [$lbl, $val, $col, $bg, $ic]): ?>
                    <div class="admin-stat-card">
                        <div class="stat-icon" style="background:<?php echo $bg; ?>;color:<?php echo $col; ?>;width:44px;height:44px;font-size:1.1rem;">
                            <i class="<?php echo $ic; ?>"></i>
                        </div>
                        <div>
                            <div style="font-size:0.7rem;color:var(--gray);text-transform:uppercase;"><?php echo $lbl; ?></div>
                            <div style="font-size:1.2rem;font-weight:800;font-family:'Poppins',sans-serif;"><?php echo $val; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Filters -->
            <div class="search-section" style="margin-bottom:1.5rem;padding:1.25rem;">
                <form method="GET">
                    <div class="filter-bar">
                        <div class="input-icon-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="form-control" placeholder="Search by ID, user, hall, event..." value="<?php echo htmlspecialchars($filter_search); ?>">
                        </div>
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $filter_status==='pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $filter_status==='processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="confirmed" <?php echo $filter_status==='confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $filter_status==='cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <select name="hall_id" class="form-control">
                            <option value="">All Halls</option>
                            <?php foreach ($all_halls as $h): ?>
                                <option value="<?php echo $h['id']; ?>" <?php echo $filter_hall == $h['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($h['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="payment" class="form-control">
                            <option value="">All Payment</option>
                            <option value="paid" <?php echo $filter_payment === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="unpaid" <?php echo $filter_payment === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                        </select>
                        <div style="display:flex;gap:0.5rem;">
                            <button type="submit" class="btn btn-primary" style="height:46px;padding:0 1rem;"><i class="fas fa-filter"></i></button>
                            <a href="manage_bookings.php" class="btn btn-outline" style="height:46px;padding:0 1rem;"><i class="fas fa-times"></i></a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Quick Status Tabs -->
            <div style="display:flex;gap:0.4rem;margin-bottom:1.25rem;flex-wrap:wrap;">
                <?php foreach ([['','All'],['pending','Pending'],['processing','Processing'],['confirmed','Confirmed'],['cancelled','Cancelled']] as [$v,$l]): ?>
                    <a href="manage_bookings.php?status=<?php echo $v; ?>" style="padding:0.4rem 1rem;border-radius:var(--radius-full);font-size:0.78rem;font-weight:600;border:1px solid var(--border);color:var(--gray);transition:var(--transition);<?php echo $filter_status===$v ? 'background:var(--primary);color:white;border-color:var(--primary);' : ''; ?>"><?php echo $l; ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Bookings Table -->
            <div class="admin-table-card">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Hall</th>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Slot</th>
                                 <th>Status</th>
                                 <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr><td colspan="9" style="text-align:center;color:var(--gray-light);padding:4rem;">No bookings match your filter.</td></tr>
                            <?php else: foreach ($bookings as $b): ?>
                                <tr>
                                    <td>
                                        <span style="font-weight:700;font-size:0.75rem;color:var(--primary);"><?php echo $b['booking_id']; ?></span>
                                        <div style="font-size:0.68rem;color:var(--gray-light);"><?php echo date('d M y', strtotime($b['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;font-size:0.875rem;"><?php echo htmlspecialchars($b['user_name']); ?></div>
                                        <div style="font-size:0.72rem;color:var(--gray);"><?php echo htmlspecialchars($b['user_phone']); ?></div>
                                        <div style="font-size:0.68rem;color:var(--gray-light);"><?php echo htmlspecialchars($b['user_email']); ?></div>
                                    </td>
                                    <td style="font-size:0.875rem;">
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($b['hall_name'] ?? $b['room_name']); ?></div>
                                        <div style="font-size:0.72rem;color:var(--gray);"><?php echo htmlspecialchars($b['hall_location'] ?? $b['room_location']); ?></div>
                                        <div style="font-size:0.65rem;color:var(--primary);font-weight:700;"><?php echo $b['room_id'] ? 'ROOM' : 'HALL'; ?></div>
                                    </td>
                                    <td style="font-size:0.875rem;">
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($b['event_name']); ?></div>
                                        <?php if($b['special_requests']): ?>
                                            <div style="font-size:0.68rem; color:var(--primary); font-style:italic;">Note: <?php echo htmlspecialchars($b['special_requests']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:0.875rem;white-space:nowrap;">
                                        <?php echo date('d M Y', strtotime($b['event_date'])); ?>
                                        <br><span style="font-size:0.7rem;color:var(--gray);"><?php echo date('D', strtotime($b['event_date'])); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $b['is_full_day'] ? 'primary' : 'info'; ?>" style="font-size:0.68rem; white-space:nowrap;">
                                            <?php if ($b['is_full_day']): ?>
                                                Full Day (9:00am - 11:00pm)
                                            <?php elseif ($b['slot_name']): ?>
                                                <?php echo htmlspecialchars($b['slot_name']); ?>
                                            <?php else: echo '-'; endif; ?>
                                        </span>
                                    </td>


                                    <td>
                                        <?php 
                                        $status_styles = [
                                            'pending'    => ['color' => '#B08D57', 'bg' => '#F9F4E1', 'label' => 'Pending'],
                                            'processing' => ['color' => '#6B4F3F', 'bg' => '#E5D5CB', 'label' => 'Processing'],
                                            'confirmed'  => ['color' => '#2D6A4F', 'bg' => '#E2EFE7', 'label' => 'Confirmed'],
                                            'cancelled'  => ['color' => '#9B2226', 'bg' => '#F5E6E6', 'label' => 'Cancelled']
                                        ];
                                        $cur_style = $status_styles[$b['status']] ?? $status_styles['pending'];
                                        ?>
                                        <select class="form-control status-select" data-id="<?php echo $b['id']; ?>" 
                                                style="font-size:0.7rem; padding:0.35rem 0.6rem; height:auto; width:auto; min-width:115px; font-weight:700; border-radius:30px; cursor:pointer; 
                                                       background-color:<?php echo $cur_style['bg']; ?>; color:<?php echo $cur_style['color']; ?>; border:1px solid <?php echo $cur_style['color']; ?>30;">
                                            <?php foreach($status_styles as $val => $info): ?>
                                                <option value="<?php echo $val; ?>" <?php echo $b['status'] === $val ? 'selected' : ''; ?>>
                                                    <?php echo $info['label']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                     <td>
                                         <?php $pc = $b['payment_status'] === 'paid' ? 'success' : 'danger'; ?>
                                         <span class="badge badge-<?php echo $pc; ?>" style="font-size: 0.65rem;">
                                             <i class="fas <?php echo $b['payment_status'] === 'paid' ? 'fa-check' : 'fa-clock'; ?>"></i>
                                             <?php echo ucfirst($b['payment_status']); ?>
                                         </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:0.35rem;justify-content:center;">
                                            <button class="btn btn-primary btn-sm" title="Edit Details" 
                                                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($b)); ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
        const id = this.getAttribute('data-id');
        const status = this.value;
        const currentParams = new URLSearchParams(window.location.search);
        
        // Map selective values back to action names used in the existing handler
        const actionMap = {
            'pending': 'pending',
            'processing': 'process',
            'confirmed': 'confirm',
            'cancelled': 'cancel'
        };
        
        const action = actionMap[status];
        if (action) {
            if (confirm(`Change booking status to ${status.charAt(0).toUpperCase() + status.slice(1)}?`)) {
                currentParams.set('action', action);
                currentParams.set('id', id);
                window.location.search = currentParams.toString();
            } else {
                // Reset to previous value if cancelled
                window.location.reload();
            }
        }
    });
});
</script>

<!-- Edit Booking Modal -->
<div id="editModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; overflow-y:auto;">
    <div style="background:white; max-width:500px; margin:2rem auto; border-radius:12px; position:relative; padding:0;">
        <div style="padding:1.25rem 1.5rem; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.1rem;">Edit Booking <span id="modal-bk-id" style="color:var(--primary);"></span></h3>
            <button onclick="closeEditModal()" style="background:none; border:none; color:var(--gray); cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" style="padding:1.5rem;">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group" style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:0.4rem;">Event Name</label>
                <input type="text" name="event_name" id="edit-event" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:0.4rem;">Event Date</label>
                <input type="date" name="event_date" id="edit-date" class="form-control" required>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:0.4rem;">Payment Status</label>
                <select name="payment_status" id="edit-payment" class="form-control">
                    <option value="unpaid">Unpaid</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button" onclick="closeEditModal()" class="btn btn-outline" style="padding:0.6rem 1.25rem;">Cancel</button>
                <button type="submit" name="update_booking" class="btn btn-primary" style="padding:0.6rem 1.25rem;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Booking Modal -->
<div id="addModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; overflow-y:auto;">
    <div style="background:white; max-width:600px; margin:2rem auto; border-radius:12px; position:relative; overflow:hidden;">
        <div style="background:var(--gradient-primary); padding:1.25rem 1.5rem; color:white; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.1rem; color:white;"><i class="fas fa-calendar-plus"></i> Add New Booking</h3>
            <button onclick="closeAddModal()" style="background:none; border:none; color:white; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" style="padding:1.5rem;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
                <div class="form-group">
                    <label style="display:block; font-size:0.8rem; font-weight:700; margin-bottom:0.4rem; color:var(--gray);">GUEST NAME</label>
                    <input type="text" name="guest_name" class="form-control" placeholder="Full Name" required>
                </div>
                <div class="form-group">
                    <label style="display:block; font-size:0.8rem; font-weight:700; margin-bottom:0.4rem; color:var(--gray);">GUEST PHONE</label>
                    <input type="tel" name="guest_phone" class="form-control" placeholder="10-digit number" required>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label style="display:block; font-size:0.8rem; font-weight:700; margin-bottom:0.4rem; color:var(--gray);">GUEST EMAIL</label>
                    <input type="email" name="guest_email" class="form-control" placeholder="email@address.com" required>
                </div>
            </div>

            <div style="border-top:1px dashed var(--border); padding-top:1.5rem; margin-bottom:1.5rem;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <div class="form-group">
                        <label style="display:block; font-size:0.8rem; font-weight:700; margin-bottom:0.4rem; color:var(--gray);">BOOKING TYPE</label>
                        <select name="item_type" id="add-item-type" class="form-control" onchange="toggleItems()">
                            <option value="hall">Marriage Hall</option>
                            <option value="room">Guest Room</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display:block; font-size:0.8rem; font-weight:700; margin-bottom:0.4rem; color:var(--gray);">SELECT UNIT</label>
                        <select name="item_id" id="add-item-select" class="form-control" required></select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                    <div class="form-group">
                        <label style="display:block; font-size:0.8rem; font-weight:700; margin-bottom:0.4rem; color:var(--gray);">EVENT NAME</label>
                        <input type="text" name="event_name" class="form-control" placeholder="e.g., Wedding" required>
                    </div>
                    <div class="form-group">
                        <label style="display:block; font-size:0.8rem; font-weight:700; margin-bottom:0.4rem; color:var(--gray);">EVENT DATE</label>
                        <input type="date" name="event_date" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:block; font-size:0.8rem; font-weight:700; margin-bottom:0.4rem; color:var(--gray);">SPECIALTY / REQUESTS</label>
                    <textarea name="specialty" class="form-control" rows="3" placeholder="Additional requirements or specialties..."></textarea>
                </div>
            </div>

            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button" onclick="closeAddModal()" class="btn btn-outline" style="padding:0.6rem 1.25rem;">Cancel</button>
                <button type="submit" name="add_booking" class="btn btn-primary" style="padding:0.6rem 1.25rem; background:var(--gradient-primary); border:none;">Create Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
const halls = <?php echo json_encode($all_halls); ?>;
const rooms = <?php echo json_encode($all_rooms); ?>;

function openAddModal() {
    document.getElementById('addModal').style.display = 'block';
    toggleItems();
    document.body.style.overflow = 'hidden';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function toggleItems() {
    const type = document.getElementById('add-item-type').value;
    const select = document.getElementById('add-item-select');
    const source = type === 'hall' ? halls : rooms;
    
    select.innerHTML = '';
    source.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.innerText = item.name;
        select.appendChild(opt);
    });
}

function openEditModal(booking) {
    document.getElementById('edit-id').value = booking.id;
    document.getElementById('modal-bk-id').innerText = booking.booking_id;
    document.getElementById('edit-event').value = booking.event_name;
    document.getElementById('edit-date').value = booking.event_date;
    document.getElementById('edit-payment').value = booking.payment_status;
    document.getElementById('editModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}
</script>
</body>
</html>


