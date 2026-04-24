<?php
require_once 'includes/auth_functions.php';

$hall_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$error_msg = '';
$success_msg = '';

if (isset($_GET['error']) && $_GET['error'] === 'double_booking') {
    $error_msg = $room_id > 0 
        ? 'Sorry, this room is already fully booked for the selected date. Please try another date.' 
        : 'This hall has already been booked by another user for this date/slot. Please choose a different date.';
}

// Fetch all active slots
$slots = [];
try {
    $slots = $pdo->query("SELECT * FROM slots WHERE status='active' ORDER BY id ASC")->fetchAll();
} catch (Exception $e) {
}

// Month filter
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
// Validate format
if (!preg_match('/^\d{4}-\d{2}$/', $selected_month)) $selected_month = date('Y-m');
$month_start = $selected_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));

// ===== INLINE AJAX HANDLER - returns ONLY schedule HTML =====
if (isset($_GET['ajax']) && ($hall_id > 0 || $room_id > 0) && isset($pdo)) {
    ob_start();
    $booked_dates_aj = [];
    try {
        $where_clause = $hall_id > 0 ? "b.hall_id=?" : "b.room_id=?";
        $target_id = $hall_id > 0 ? $hall_id : $room_id;
        $s = $pdo->prepare("
            SELECT b.event_date, b.is_full_day, s.name AS slot_name, b.event_name, b.status, u.name AS user_name
            FROM bookings b JOIN users u ON b.user_id=u.id
            LEFT JOIN slots s ON b.slot_id=s.id
            WHERE $where_clause AND b.event_date>=? AND b.event_date<=? AND b.status != 'cancelled'
            ORDER BY b.event_date ASC
        ");
        $s->execute([$target_id, $month_start, $month_end]);
        $booked_dates_aj = $s->fetchAll();
    } catch (Exception $e) {
    }
    if (!empty($booked_dates_aj)):
        echo '<div class="table-responsive"><table class="occupancy-table"><thead><tr><th>Date</th><th>Event</th><th>Slot</th><th>Booked By</th><th>Status</th></tr></thead><tbody>';
        foreach ($booked_dates_aj as $bk):
            $slotLabel = $bk['is_full_day'] ? '<span class="badge badge-primary">Full Day</span>' : '<span class="badge badge-info">' . htmlspecialchars($bk['slot_name'] ?? '-') . '</span>';
            $statusLabel = '<span class="badge badge-' . ($bk['status'] === 'confirmed' ? 'success' : 'warning') . '">' . ucfirst($bk['status']) . '</span>';
            echo '<tr><td><strong>' . date('d M Y (D)', strtotime($bk['event_date'])) . '</strong></td><td>' . htmlspecialchars($bk['event_name'] ?? '-') . '</td><td>' . $slotLabel . '</td><td style="color:#64748b;">' . htmlspecialchars(substr($bk['user_name'], 0, 1) . '***') . '</td><td>' . $statusLabel . '</td></tr>';
        endforeach;
        echo '</tbody></table></div>';
    else:
        echo '<div style="text-align:center;padding:3rem;"><i class="fas fa-calendar-check" style="font-size:2.5rem;margin-bottom:1rem;color:#10b981;display:block;"></i><p style="font-size:1rem;font-weight:600;color:#10b981;">Fully Available</p><p style="font-size:0.875rem;color:#94a3b8;">No bookings this month. All slots are free!</p></div>';
    endif;
    echo ob_get_clean();
    exit();
}

// ===================================================
//  MODE 1: HALL/ROOM DETAIL + BOOKING FORM
// ===================================================
if ($hall_id > 0 || $room_id > 0) {
    $current_item = null;
    $is_room = ($room_id > 0);
    try {
        if ($is_room) {
            $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
            $stmt->execute([$room_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM halls WHERE id = ?");
            $stmt->execute([$hall_id]);
        }
        $current_item = $stmt->fetch();
    } catch (Exception $e) {
    }

    if (!$current_item) {
        header('Location: halls.php');
        exit();
    }


    // Confirmed bookings for this hall/room in selected month
    $booked_dates = [];
    try {
        $where_clause = $is_room ? "b.room_id = ?" : "b.hall_id = ?";
        $target_id = $is_room ? $room_id : $hall_id;
        $bstmt = $pdo->prepare("
            SELECT b.event_date, b.is_full_day, s.name AS slot_name, b.event_name, b.status, u.name AS user_name
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            LEFT JOIN slots s ON b.slot_id = s.id
            WHERE $where_clause AND b.event_date >= ? AND b.event_date <= ? AND b.status != 'cancelled'
            ORDER BY b.event_date ASC
        ");
        $bstmt->execute([$target_id, $month_start, $month_end]);
        $booked_dates = $bstmt->fetchAll();
    } catch (Exception $e) {
    }


    // User info prefill
    $user_name = $user_phone = $user_email = '';
    if (isLoggedIn()) {
        try {
            $u = $pdo->prepare("SELECT name, phone, email FROM users WHERE id = ?");
            $u->execute([$_SESSION['user_id']]);
            $info = $u->fetch();
            if ($info) {
                $user_name  = $info['name'];
                $user_phone = $info['phone'];
                $user_email = $info['email'];
            }
        } catch (Exception $e) {
        }
    }

    // Parse specialties/amenities from facilities field
    $facilities = [];
    if (!empty($current_item['facilities'])) {
        $facilities = array_map('trim', explode(',', $current_item['facilities']));
    }
}

// ===================================================
//  MODE 2: HALL GALLERY LISTING
// ===================================================
else {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $location_filter = isset($_GET['location']) ? trim($_GET['location']) : '';
    $capacity_filter = isset($_GET['capacity']) ? (int)$_GET['capacity'] : 0;

    $query = "SELECT * FROM halls WHERE 1=1";
    $params = [];

// Determine current view
$view = $_GET['view'] ?? 'grid';
$selected_cat = (int)($_GET['cat'] ?? 0);

if ($hall_id > 0 || $room_id > 0) {
    // Detail view - handled below
} else {
    // Listing View Logic
    $query = "SELECT * FROM halls WHERE 1=1";
    $params = [];
    if ($search) {
        $query .= " AND (name LIKE ? OR description LIKE ? OR location LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $all_halls = [];
    $all_rooms = [];
    if ($view !== 'room_types') {
        $halls_stmt = $pdo->prepare($query . " ORDER BY name ASC");
        $halls_stmt->execute($params);
        $all_halls = $halls_stmt->fetchAll();
    }

    // Fetch rooms GROUPED BY CATEGORY (One card per category with total count)
    $rooms_query = "
        SELECT 
            r.*,
            rc.name AS category_name, 
            rc.icon AS category_icon,
            rc.image AS category_image,
            r.main_image AS room_image,
            r.id AS representative_room_id,
            r.total_rooms AS total_inventory,
            (SELECT COUNT(*) FROM bookings b 
             WHERE b.room_id = r.id 
             AND b.status IN ('confirmed','pending') 
             AND b.event_date >= CURDATE()) AS current_day_booked
        FROM rooms r
        JOIN room_categories rc ON r.category_id = rc.id
        WHERE 1=1
    ";
    $room_params = [];
    if ($search) {
        $rooms_query .= " AND (r.name LIKE ? OR r.description LIKE ? OR rc.name LIKE ?)";
        $room_params = ["%$search%", "%$search%", "%$search%"];
    }
    if ($selected_cat > 0) {
        $rooms_query .= " AND rc.id = ?";
        $room_params[] = $selected_cat;
    }

    $rooms_query .= " ORDER BY 
        CASE rc.name
            WHEN 'VIP Suite Room' THEN 1
            WHEN 'New Super Deluxe Room' THEN 2
            WHEN 'Super Deluxe Room' THEN 3
            WHEN 'Deluxe Room' THEN 4
            WHEN 'Driver Room' THEN 5
            ELSE 6
        END ASC, r.name ASC";
    $rooms_stmt = $pdo->prepare($rooms_query);
    $rooms_stmt->execute($room_params);
    $all_rooms = $rooms_stmt->fetchAll();

    $all_room_categories = $pdo->query("
        SELECT * FROM room_categories 
        ORDER BY 
            CASE name
                WHEN 'VIP Suite Room' THEN 1
                WHEN 'New Super Deluxe Room' THEN 2
                WHEN 'Super Deluxe Room' THEN 3
                WHEN 'Deluxe Room' THEN 4
                WHEN 'Driver Room' THEN 5
                ELSE 6
            END ASC
    ")->fetchAll();
    $locations = $pdo->query("SELECT DISTINCT location FROM halls WHERE location != '' ORDER BY location ASC")->fetchAll(PDO::FETCH_COLUMN);
}

    // Calendar-view bookings (next 30 days across all halls)
    $today = date('Y-m-d');
    $next30 = date('Y-m-d', strtotime('+30 days'));
    $global_bookings = [];
    try {
        $g = $pdo->prepare("
            SELECT b.event_date, h.name AS hall_name, s.name AS slot_name, b.is_full_day, b.event_name
            FROM bookings b
            JOIN halls h ON b.hall_id = h.id
            LEFT JOIN slots s ON b.slot_id = s.id
            WHERE b.event_date >= ? AND b.event_date <= ? AND b.status = 'confirmed'
            ORDER BY b.event_date ASC, h.name ASC
        ");
        $g->execute([$today, $next30]);
        $global_bookings = $g->fetchAll();
    } catch (Exception $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($hall_id > 0 || $room_id > 0) ? htmlspecialchars($current_item['name']) . ' - Book Now' : 'Browse Halls & Rooms'; ?> | <?php echo $brand_name; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding-top: 185px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 3rem;
            align-items: start;
        }

        @media(max-width:1100px) {
            .detail-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        .amenity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .price-row.total {
            font-weight: 700;
            font-size: 1.1rem;
            border-top: 1px solid var(--border);
            padding-top: 1rem;
            margin-top: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .category-filter-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 3rem;
        }

        .category-filter-grid .btn {
            border-radius: var(--radius-sm);
        }
    </style>
</head>

<body>
    <!-- SHARED NAVBAR -->
    <?php include 'includes/navbar.php'; ?>

    <?php if ($hall_id > 0 || $room_id > 0): // ===== HALL/ROOM DETAIL VIEW ===== 
    ?>
        <div class="container" style="padding-top:2rem;padding-bottom:4rem;">
            <!-- Breadcrumb -->
            <div style="display:flex;align-items:center;gap:0.5rem;color:var(--gray);font-size:0.875rem;margin-bottom:2rem;">
                <a href="halls.php" style="color:var(--primary);">Halls</a>
                <i class="fas fa-chevron-right" style="font-size:0.65rem;"></i>
                <span><?php echo htmlspecialchars($current_item['name']); ?></span>
            </div>

            <?php if ($error_msg): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="detail-grid">
                <!-- LEFT: HALL INFO -->
                <div>
                    <!-- Hero Image -->
                    <div class="hall-detail-hero">
                        <?php if ($current_item['main_image']): ?>
                            <img src="assets/images/<?php echo $is_room ? 'rooms' : 'halls'; ?>/<?php echo htmlspecialchars($current_item['main_image']); ?>" alt="<?php echo htmlspecialchars($current_item['name']); ?>">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:var(--gradient-hero);display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-building" style="font-size:5rem;color:rgba(255,255,255,0.15);"></i>
                            </div>
                        <?php endif; ?>
                        <div class="hall-detail-overlay">
                            <div>
                                <h1 style="color:white;font-size:2.5rem;margin-bottom:0.5rem;"><?php echo htmlspecialchars($current_item['name']); ?></h1>
                                <div style="display:flex;gap:2rem;color:rgba(255,255,255,0.9);font-size:0.95rem;flex-wrap:wrap;">
                                    <span><i class="fas fa-map-marker-alt" style="color:var(--primary);"></i> <?php echo htmlspecialchars($current_item['location']); ?></span>
                                    <span><i class="fas fa-users" style="color:var(--primary);"></i> Up to <?php echo number_format($current_item['capacity']); ?> Guests</span>
                                    <?php if ($current_item['price_per_day'] > 0): ?>
                                        <span><i class="fas fa-tag" style="color:var(--primary);"></i> Rs. <?php echo number_format($current_item['price_per_day']); ?>/day</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="card" style="margin-top:2rem;padding:2.5rem;border-radius:var(--radius-md);">
                        <h3 style="margin-bottom:1.5rem;font-family:'Playfair Display', serif;">Property Overview</h3>
                        <p style="color:var(--gray);line-height:1.8;font-size:1.05rem;"><?php echo nl2br(htmlspecialchars($current_item['description'] ?? ($is_room ? 'Premium room available for comfortable stay during your events.' : 'Premium hall available for all types of events including weddings, receptions, birthday parties, corporate events, and more.'))); ?></p>
                    </div>

                    <!-- Amenities / Specialties -->
                    <?php if (!empty($facilities)): ?>
                        <div class="card" style="margin-top:1.5rem;padding:2.5rem;border-radius:var(--radius-md);">
                            <h3 style="margin-bottom:1.5rem;font-family:'Playfair Display', serif;">Facilities & Services</h3>
                            <div class="amenity-grid">
                                <?php
                                $amenity_icons = [
                                    'AC' => 'fas fa-snowflake',
                                    'Air Conditioning' => 'fas fa-snowflake',
                                    'Parking' => 'fas fa-parking',
                                    'Catering' => 'fas fa-utensils',
                                    'Stage' => 'fas fa-theater-masks',
                                    'Generator' => 'fas fa-bolt',
                                    'WiFi' => 'fas fa-wifi',
                                    'Music' => 'fas fa-music',
                                    'Decoration' => 'fas fa-wand-magic-sparkles',
                                    'CCTV' => 'fas fa-camera',
                                    'Kitchen' => 'fas fa-kitchen-set',
                                    'Restrooms' => 'fas fa-restroom',
                                    'Projector' => 'fas fa-film',
                                    'Lift' => 'fas fa-elevator',
                                ];
                                foreach ($facilities as $fac):
                                    $icon = 'fas fa-check-circle';
                                    foreach ($amenity_icons as $key => $ico) {
                                        if (stripos($fac, $key) !== false) {
                                            $icon = $ico;
                                            break;
                                        }
                                    }
                                ?>
                                    <div class="hall-amenity">
                                        <i class="<?php echo $icon; ?>"></i>
                                        <span><?php echo htmlspecialchars($fac); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>


                    <!-- Occupancy Schedule -->
                    <?php
                    $prev_m = date('Y-m', strtotime($selected_month . '-01 -1 month'));
                    $next_m = date('Y-m', strtotime($selected_month . '-01 +1 month'));
                    $month_label = date('F Y', strtotime($selected_month . '-01'));
                    ?>
                    <div class="card" id="booking-schedule" style="margin-top:1.5rem;padding:2.5rem;border-radius:var(--radius-md);scroll-margin-top:100px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;">
                            <h3 style="font-family:'Playfair Display', serif;"><i class="fas fa-calendar-alt" style="color:var(--primary);margin-right:0.5rem;"></i> Availability Calendar</h3>
                            <div style="display:flex;align-items:center;gap:1rem;">
                                <a href="?id=<?php echo $hall_id; ?>&month=<?php echo $prev_m; ?>#booking-schedule" class="btn-circle" style="width:40px;height:40px;border-radius:var(--radius-sm);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--primary);transition:var(--transition);">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <strong style="font-size:1.1rem;min-width:140px;text-align:center;"><?php echo $month_label; ?></strong>
                                <a href="?id=<?php echo $hall_id; ?>&month=<?php echo $next_m; ?>#booking-schedule" class="btn-circle" style="width:40px;height:40px;border-radius:var(--radius-sm);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--primary);transition:var(--transition);">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>

                    <?php if (!empty($booked_dates)): ?>
                        <div class="table-responsive">
                            <table class="occupancy-table">
                                <thead>
                                    <tr><th>Date</th><th>Event</th><th>Slot</th><th>Booked By</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($booked_dates as $bk): ?>
                                    <tr>
                                        <td><strong><?php echo date('d M Y (D)', strtotime($bk['event_date'])); ?></strong></td>
                                        <td><?php echo htmlspecialchars($bk['event_name'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $bk['is_full_day'] ? 'primary' : 'info'; ?>">
                                                <?php echo $bk['is_full_day'] ? 'Full Day' : htmlspecialchars($bk['slot_name'] ?? '-'); ?>
                                            </span>
                                        </td>
                                        <td style="color:var(--gray);"><?php echo htmlspecialchars(substr($bk['user_name'], 0, 1) . '***'); ?></td>
                                        <td><span class="badge badge-<?php echo $bk['status'] === 'confirmed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($bk['status']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center;padding:4rem 2rem;">
                            <div style="width:80px;height:80px;background:var(--primary-light);color:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2rem;">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h4 style="font-size:1.25rem;color:var(--primary);margin-bottom:0.5rem;">Fully Available</h4>
                            <p style="color:var(--gray);">Great news! All slots are currently free for this month.</p>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT: BOOKING FORM -->
                <div>
                    <div class="booking-form-card" style="position:sticky;top:110px;">
                        <div class="booking-form-header" style="padding:2rem; background: var(--primary-deep);">
                            <h3 style="color:white;margin-bottom:0.5rem;font-family:'Playfair Display', serif;">Reservation</h3>
                            <p style="color:rgba(255,255,255,0.6);font-size:0.85rem;margin:0;">Secure your date instantly</p>
                        </div>

                        <?php if (!isLoggedIn()): ?>
                            <div class="booking-form-body" style="text-align:center;padding:3rem 2rem;">
                                <div style="width:80px;height:80px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                                    <i class="fas fa-lock" style="font-size:2rem;color:var(--primary);"></i>
                                </div>
                                <h4 style="margin-bottom:1rem;">Authentication Required</h4>
                                <p style="color:var(--gray);font-size:0.95rem;margin-bottom:2rem;line-height:1.6;">Please sign in to your account to process this booking.</p>
                                <a href="login.php" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;">Login & Continue</a>
                            </div>
                        <?php else: ?>
                            <div class="booking-form-body" style="padding:2rem;">
                                <form id="bookingForm" action="actions/book_hall.php" method="POST">
                                    <input type="hidden" name="hall_id" value="<?php echo $hall_id; ?>">
                                    <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">

                                    <?php if (!$is_room): ?>
                                    <div class="form-group">
                                        <label>Event Category</label>
                                        <select name="event_name" class="form-control" required>
                                            <option value="" disabled selected>Select event type</option>
                                            <option value="Wedding">Wedding (Thirumanam)</option>
                                            <option value="Reception">Reception</option>
                                            <option value="Betrothal">Betrothal</option>
                                            <option value="Birthday Party">Birthday Party</option>
                                            <option value="Puberty Ceremony">Puberty Ceremony</option>
                                            <option value="Baby Shower">Baby Shower</option>
                                            <option value="Ear Piercing">Ear Piercing</option>
                                            <option value="Meeting / Seminar">Meeting / Seminar</option>
                                            <option value="Other">Other Event</option>
                                        </select>
                                    </div>
                                    <?php else: ?>
                                        <input type="hidden" name="event_name" value="Guest Stay">
                                    <?php endif; ?>

                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" name="booker_name" data-validate="name" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Contact Phone</label>
                                        <input type="tel" name="booker_phone" data-validate="phone" class="form-control" value="<?php echo htmlspecialchars($user_phone); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label>Event Date</label>
                                        <input type="date" name="event_date" id="event_date" class="form-control" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" onchange="checkLiveAvailability(this.value)">
                                        <div id="availabilityStatus" style="display:none; padding:0.75rem; border-radius:var(--radius-sm); font-size:0.85rem; font-weight:600; margin-top:0.5rem;">
                                            <i class="fas fa-info-circle"></i> <span>Checking...</span>
                                        </div>
                                    </div>

                                <div class="form-group">
                                    <label>Booking Duration</label>
                                    <div style="background:var(--primary-light); padding:1rem; border-radius:var(--radius-sm); border:1px solid var(--tan-soft); color:var(--primary); font-weight:700; display:flex; align-items:center; gap:0.75rem;">
                                        <i class="fas fa-calendar-check"></i> <?php echo $is_room ? 'Daily Stay Rate' : 'Full Day Venue Hire'; ?>
                                    </div>
                                    <input type="hidden" name="is_full_day" value="1">
                                    <input type="hidden" name="slot_id" value="">
                                </div>

                                <div class="price-row total">
                                    <span style="font-family:'Playfair Display', serif; font-weight:700; color:var(--dark);">Total Amount</span>
                                    <span id="hallRate" style="font-weight:700; color:var(--primary); font-size:1.25rem;">Rs. <?php echo number_format($current_item['price_per_day']); ?></span>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg" style="width:100%; justify-content:center; margin-top:2rem; border-radius: var(--radius-sm);">
                                    <i class="fas fa-check-circle"></i> Confirm Reservation
                                </button>

                                <p style="text-align:center;font-size:0.8rem;color:var(--gray);margin-top:1.25rem;line-height:1.5;">
                                    By clicking confirm, you agree to our terms and conditions for venue booking.
                                </p>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<?php else: // ===== HALL/ROOM GALLERY LISTING ===== ?>
    <!-- Page Header -->
    <div class="page-header">
        <div class="container reveal" style="position:relative;z-index:1;">
            <div class="section-label"><i class="fas fa-building"></i> Our Venues</div>
            <h1 style="color:black;font-size:2.5rem;margin-bottom:0.5rem;">Browse All <span style="color:var(--secondary);">Halls & Rooms</span></h1>
            <p style="color:black">Find Your Perfect Venue or Room from our Collection of Premium Halls & Rooms.</p>
        </div>
    </div>

        <!-- <div class="container" style="padding-top:2.5rem;padding-bottom:4rem;display:flex;flex-direction:column;gap:2.5rem;"> -->
        <!-- Search / Filter -->
        <!-- <div class="search-section reveal" style="margin-bottom:2.5rem;">
            <form method="GET">
                <div class="search-grid">
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.8rem;">Search Halls</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="form-control" placeholder="Hall name, location..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.8rem;">Location</label>
                        <select name="location" class="form-control">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $location_filter === $loc ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($loc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.8rem;">Min. Capacity</label>
                        <select name="capacity" class="form-control">
                            <option value="0">Any Capacity</option>
                            <?php foreach ([50, 100, 200, 300, 500, 1000] as $cap): ?>
                                <option value="<?php echo $cap; ?>" <?php echo $capacity_filter === $cap ? 'selected' : ''; ?>>
                                    <?php echo $cap; ?>+ guests
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:flex;gap:0.5rem;align-items:flex-end;">
                        <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;height:46px;">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <?php if ($search || $location_filter || $capacity_filter): ?>
                            <a href="halls.php" class="btn btn-outline" style="height:46px;justify-content:center;"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div> -->

        <!-- Hall Grid -->
        <!-- Listing Section -->
        <div class="container" style="padding-top:2.5rem;padding-bottom:4rem;display:flex;flex-direction:column;gap:2.5rem;">
            
            <?php if ($view === 'room_types'): ?>
                <!-- ROOM TYPES VIEW -->
                <div class="reveal">
                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:2rem;">
                        <a href="halls.php" class="btn btn-outline" style="border-radius:50%; width:40px; height:40px; padding:0; justify-content:center;">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h2 style="font-size:2.2rem; margin:0;">Browse Room Types</h2>
                    </div>

                    <!-- Category Filter Grid -->
                    <div class="category-filter-grid">
                        <a href="halls.php?view=room_types" class="btn <?php echo $selected_cat === 0 ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius:30px; justify-content:center;">
                            All Categories
                        </a>
                        <?php foreach($all_room_categories as $rcat): ?>
                            <a href="halls.php?view=room_types&cat=<?php echo $rcat['id']; ?>" class="btn <?php echo $selected_cat == $rcat['id'] ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius:30px; gap:0.5rem; justify-content:center;">
                                <i class="<?php echo $rcat['icon'] ?? 'fas fa-bed'; ?>"></i>
                                <?php echo htmlspecialchars($rcat['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($all_rooms)): ?>
                        <div class="halls-grid stagger-children">
                            <?php foreach ($all_rooms as $room): 
                                $avail_count = (int)$room['total_inventory'] - (int)$room['current_day_booked'];
                                if ($avail_count < 0) $avail_count = 0;
                            ?>
                                <div class="hall-card glass-card reveal">
                                    <div class="hall-card-img">
                                        <?php if ($room['category_image']): ?>
                                            <img src="assets/images/categories/<?php echo htmlspecialchars($room['category_image']); ?>" alt="<?php echo htmlspecialchars($room['category_name']); ?>">
                                        <?php elseif ($room['room_image']): ?>
                                            <img src="assets/images/rooms/<?php echo htmlspecialchars($room['room_image']); ?>" alt="<?php echo htmlspecialchars($room['category_name']); ?>">
                                        <?php else: ?>
                                            <div style="width:100%;height:100%;background:var(--gradient-hero);display:flex;align-items:center;justify-content:center;">
                                                <i class="<?php echo htmlspecialchars($room['category_icon'] ?? 'fas fa-bed'); ?>" style="font-size:3rem;color:rgba(255,255,255,0.25);"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="hall-price">Rs. <?php echo number_format($room['price_per_day']); ?></div>
                                        <div class="hall-badge">
                                            <span class="badge <?php echo $avail_count > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                                <i class="<?php echo $avail_count > 0 ? 'fas fa-check-circle' : 'fas fa-times-circle'; ?>" style="font-size:0.5rem;"></i> 
                                                <?php echo $avail_count > 0 ? $avail_count . ' Rooms Available' : 'Sold Out'; ?>
                                            </span>
                                        </div>
                                        <div style="position:absolute; top:1rem; left:1rem; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); color:white; padding:0.35rem 0.75rem; border-radius:10px; font-size:0.75rem; font-weight:700;">
                                            <i class="<?php echo htmlspecialchars($room['category_icon'] ?? 'fas fa-bed'); ?>" style="margin-right:0.3rem;"></i> 
                                            <?php echo htmlspecialchars($room['category_name']); ?>
                                        </div>
                                    </div>
                                    <div class="hall-card-body">
                                        <h3 class="hall-card-title"><?php echo htmlspecialchars($room['name']); ?></h3>
                                        <div class="hall-card-meta">
                                            <span><i class="fas fa-bed"></i> <?php echo $room['total_rooms']; ?> Units</span>
                                            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($room['category_name']); ?></span>
                                        </div>
                                        <p style="font-size:0.9rem; color:var(--gray); margin-bottom:1.5rem; line-height:1.6;"><?php echo htmlspecialchars($room['description'] ?: "Book our comfortable " . $room['name'] . " for your event stay."); ?></p>
                                        <a href="halls.php?room_id=<?php echo $room['representative_room_id']; ?>" class="btn btn-primary" style="width:100%;justify-content:center; border-radius: var(--radius-sm);">
                                            Check Availability <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:3rem 0; color:var(--gray);">
                            <i class="fas fa-bed" style="font-size:3rem; margin-bottom:1rem; opacity:0.3;"></i>
                            <p>No rooms found in this category.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <!-- MAIN GRID VIEW (Halls + One Rooms Card) -->
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2.5rem;flex-wrap:wrap;gap:1rem;" class="reveal">
                    <div>
                        <h2 style="font-size:2.2rem; margin-bottom:0.5rem; font-family:'Playfair Display', serif;">Available Venues & Stays</h2>
                        <p style="color:var(--gray);font-size:0.95rem;">Select a venue below to view details and check availability.</p>
                    </div>
                </div>

                <div class="halls-grid stagger-children">
                    <?php foreach ($all_halls as $hall): ?>
                        <div class="hall-card reveal">
                            <div class="hall-card-img">
                                <?php if ($hall['main_image']): ?>
                                    <img src="assets/images/halls/<?php echo htmlspecialchars($hall['main_image']); ?>" alt="<?php echo htmlspecialchars($hall['name']); ?>">
                                <?php else: ?>
                                    <div style="width:100%;height:100%;background:var(--primary-deep);display:flex;align-items:center;justify-content:center;">
                                        <i class="fas fa-building" style="font-size:3rem;color:rgba(255,255,255,0.1);"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="hall-price">Rs. <?php echo number_format($hall['price_per_day']); ?>/day</div>
                            </div>
                            <div class="hall-card-body">
                                <h3 class="hall-card-title"><?php echo htmlspecialchars($hall['name']); ?></h3>
                                <div class="hall-card-meta">
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hall['location']); ?></span>
                                    <span><i class="fas fa-users"></i> <?php echo number_format($hall['capacity']); ?> Guests</span>
                                </div>
                                <?php if ($hall['description']): ?>
                                    <p style="font-size:0.9rem;color:var(--gray);margin-bottom:1.5rem;line-height:1.6;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;"><?php echo htmlspecialchars($hall['description']); ?></p>
                                <?php endif; ?>
                                <a href="halls.php?id=<?php echo $hall['id']; ?>" class="btn btn-primary" style="width:100%;justify-content:center; border-radius: var(--radius-sm);">
                                    View Venue <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- SPECIAL ROOMS CARD -->
                    <?php if (!empty($all_rooms)): ?>
                        <div class="hall-card reveal highlight-card" style="border: 2px solid var(--primary-light);">
                            <div class="hall-card-img">
                                <?php 
                                $room_thumb = $all_rooms[0]['category_image'] ?? $all_rooms[0]['room_image'] ?? '';
                                $thumb_path = !empty($all_rooms[0]['category_image']) ? 'categories' : 'rooms';
                                ?>
                                <img src="assets/images/<?php echo $thumb_path; ?>/<?php echo htmlspecialchars($room_thumb); ?>" alt="Luxury Rooms">
                                <div class="hall-price">Premium Stay</div>
                            </div>
                            <div class="hall-card-body">
                                <h3 class="hall-card-title">Luxury Guest Rooms</h3>
                                <div class="hall-card-meta">
                                    <span><i class="fas fa-bed"></i> Various Categories</span>
                                    <span><i class="fas fa-snowflake"></i> Fully AC</span>
                                </div>
                                <p style="font-size:0.9rem; color:var(--gray); margin-bottom:1.5rem; line-height:1.6;">Comfortable accommodation for your guests. From Bridal Suites to Deluxe rooms.</p>
                                <a href="halls.php?view=room_types" class="btn btn-outline" style="width:100%; justify-content:center; border-radius: var(--radius-sm);">
                                    Explore Rooms <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($all_halls) && empty($all_rooms)): ?>
                    <div style="text-align:center;padding:5rem 2rem;">
                        <div style="width:100px;height:100px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 2rem;">
                            <i class="fas fa-search" style="font-size:2.5rem;color:var(--primary);"></i>
                        </div>
                        <h3 style="margin-bottom:0.5rem; font-family:'Playfair Display', serif;">No Venues Found</h3>
                        <p style="color:var(--gray);">We couldn't find any halls matching your current search.</p>
                        <a href="halls.php" class="btn btn-primary" style="margin-top:2rem; border-radius: var(--radius-sm);">Clear All Filters</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        <!-- Upcoming Occupancy Schedule -->
        <?php if (!empty($global_bookings)): ?>
        <div style="margin-top:3.5rem;" class="reveal">
            <h3 style="margin-bottom:1.5rem;display:flex;align-items:center;gap:0.75rem;">
                <i class="fas fa-calendar-check" style="color:var(--primary);"></i>
                Upcoming Confirmed Bookings (Next 30 Days)
            </h3>
            <div class="admin-table-card">
                <div style="overflow-x:auto;">
                    <table class="occupancy-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Hall Name</th>
                                <th>Event</th>
                                <th>Slot</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($global_bookings as $gb): ?>
                                <tr>
                                    <td><strong><?php echo date('d M Y (D)', strtotime($gb['event_date'])); ?></strong></td>
                                    <td><?php echo htmlspecialchars($gb['hall_name']); ?></td>
                                    <td style="color:var(--gray);"><?php echo htmlspecialchars($gb['event_name'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $gb['is_full_day'] ? 'primary' : 'info'; ?>">
                                            <?php echo $gb['is_full_day'] ? 'Full Day' : htmlspecialchars($gb['slot_name']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    </div>
<?php endif; ?>

        <?php include 'includes/footer.php'; ?>
        <?php include 'includes/modals.php'; ?>
        <?php include 'includes/chatbot.php'; ?>

        <script>
            // Reveal on scroll
            const reveal = () => {
                const reveals = document.querySelectorAll('.reveal');
                reveals.forEach(el => {
                    const windowHeight = window.innerHeight;
                    const elementTop = el.getBoundingClientRect().top;
                    const elementVisible = 100;
                    if (elementTop < windowHeight - elementVisible) {
                        el.classList.add('active');
                    }
                });
            };
            window.addEventListener('scroll', reveal);
            // Initial check
            reveal();

            // Navbar scroll
            window.addEventListener('scroll', () => {
                document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
            });

            <?php if ($hall_id > 0 || $room_id > 0): ?>
                // Slot selection logic
                const hallPricePerDay = <?php echo (float)$current_item['price_per_day']; ?>;
                const morningSlotPrice = <?php echo (float)($current_item['morning_slot_price'] ?? 0); ?>;
                const eveningSlotPrice = <?php echo (float)($current_item['evening_slot_price'] ?? 0); ?>;

                function handleSlotChange(select) {
                    if (!select) return;
                    const opt = select.options[select.selectedIndex];
                    const isFullDay = opt.dataset.fullday === '1';
                    const slotId = opt.dataset.slotid || '';
                    const slotName = (opt.dataset.slotname || '').toLowerCase();

                    document.getElementById('is_full_day').value = isFullDay ? '1' : '0';
                    document.getElementById('slot_id_input').value = slotId;

                    // Determine price based on slot type
                    let slotPrice = hallPricePerDay;
                    if (!isFullDay) {
                        if (slotName.includes('morning')) {
                            slotPrice = morningSlotPrice > 0 ? morningSlotPrice : hallPricePerDay * 0.55;
                        } else {
                            slotPrice = eveningSlotPrice > 0 ? eveningSlotPrice : hallPricePerDay * 0.55;
                        }
                    }
                    document.getElementById('hallRate').textContent = 'Rs. ' + slotPrice.toLocaleString('en-IN', {maximumFractionDigits:0});
                }

                // If it's a Hall, initialize immediately
                <?php if (!$is_room): ?>
                window.addEventListener('DOMContentLoaded', () => {
                });
                <?php endif; ?>

                document.getElementById('bookingForm').addEventListener('submit', function(e) {
                    <?php if ($is_room): ?>
                    const select = document.getElementById('bookingTypeSelect');
                    if (!select.value) {
                        e.preventDefault();
                        alert('Please select a booking type.');
                        return;
                    }
                    <?php endif; ?>
                });

                // Date: prevent past dates
                document.getElementById('event_date').addEventListener('change', function() {
                    const selected = new Date(this.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (selected <= today) {
                        alert('Please select a future date for your event.');
                        this.value = '';
                    }
                });
            <?php endif; ?>
            // Live Availability Check
            async function checkLiveAvailability(date) {
                if (!date) return;
                
                const statusDiv = document.getElementById('availabilityStatus');
                const span = statusDiv.querySelector('span');
                const icon = statusDiv.querySelector('i');
                const submitBtn = document.querySelector('button[type="submit"]');
                
                statusDiv.style.display = 'flex';
                statusDiv.style.background = '#f1f5f9';
                statusDiv.style.color = '#475569';
                span.textContent = 'Checking availability for ' + date + '...';
                
                try {
                    const roomId = <?php echo (int)($room_id); ?>;
                    const hallId = <?php echo (int)($hall_id); ?>;
                    const response = await fetch(`actions/check_availability.php?date=${date}&room_id=${roomId}&hall_id=${hallId}`);
                    const result = await response.json();
                    
                    if (result.error) {
                        statusDiv.style.background = '#fff1f2';
                        statusDiv.style.color = '#e11d48';
                        span.textContent = 'Error: ' + result.error;
                        return;
                    }
                    
                    if (result.available > 0) {
                        statusDiv.style.background = '#f0fdf4';
                        statusDiv.style.color = '#16a34a';
                        icon.className = 'fas fa-check-circle';
                        span.textContent = result.message;
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                    } else {
                        statusDiv.style.background = '#fff1f2';
                        statusDiv.style.color = '#e11d48';
                        icon.className = 'fas fa-times-circle';
                        span.textContent = result.message;
                        submitBtn.disabled = true;
                        submitBtn.style.opacity = '0.5';
                    }
                } catch (err) {
                    span.textContent = 'Connection error. Please try again.';
                }
            }
        </script>
</body>

</html>
