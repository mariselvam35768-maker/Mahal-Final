<?php
require_once 'includes/auth_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Fetch user's bookings
$query = "
    SELECT b.*, 
           h.name AS hall_name, h.location AS hall_location, h.main_image AS hall_image,
           r.name AS room_name, r.location AS room_location, r.main_image AS room_image,
           s.name AS slot_name, s.start_time, s.end_time,
           h.price_per_day AS hall_price, r.price_per_day AS room_price
    FROM bookings b
    LEFT JOIN halls h ON b.hall_id = h.id
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN slots s ON b.slot_id = s.id
    WHERE b.user_id = ?
";
$params = [$user_id];

if ($filter_status) {
    $query .= " AND b.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY b.created_at DESC";

$bookings = [];
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {}

// Stats
$all_bookings_stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM bookings WHERE user_id = ? GROUP BY status");
$all_bookings_stmt->execute([$user_id]);
$stats_raw = $all_bookings_stmt->fetchAll();
$stats = [];
foreach ($stats_raw as $s) {
    $stats[$s['status']] = ['count' => $s['cnt']];
}

if (isset($_GET['success'])) {
    $success_msg = 'Your Request has been Sent and is currently Processing. Admin will confirm shortly.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | <?php echo $brand_name; ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding-top: 75px; }
        .booking-card { background: white; border-radius: var(--radius-lg); border: 1px solid var(--border); overflow: hidden; transition: var(--transition); margin-bottom: 1.25rem; }
        .booking-card:hover { box-shadow: var(--shadow-md); }
        .booking-card-header { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f8fafc; flex-wrap: wrap; gap: 0.75rem; }
        .booking-card-body { padding: 1.5rem; display: grid; grid-template-columns: auto 1fr auto; gap: 1.5rem; align-items: center; }
        @media(max-width:640px) { .booking-card-body { grid-template-columns: 1fr; } }
        .booking-hall-img { width: 110px; height: 80px; border-radius: var(--radius); overflow: hidden; flex-shrink: 0; }
        .booking-hall-img img { width: 100%; height: 100%; object-fit: cover; }
        .booking-hall-img .placeholder { width: 100%; height: 100%; background: var(--gradient-hero); display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.3); font-size: 1.5rem; }
        .filter-tabs { display: flex; gap: 0.4rem; flex-wrap: wrap; }
        .filter-tab { padding: 0.5rem 1.1rem; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 600; transition: var(--transition); border: 1px solid var(--border); color: var(--gray); text-decoration: none; }
        .filter-tab:hover, .filter-tab.active { background: var(--primary); color: white; border-color: var(--primary); }
        .stat-mini .val { font-size: 1.5rem; font-weight: 800; font-family: 'Poppins',sans-serif; color: var(--primary); }

        /* Payment Processing Overlay */
        .payment-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);
            z-index: 9999; display: none; align-items: center; justify-content: center;
            color: white; text-align: center;
        }
        .processing-box { max-width: 400px; width: 90%; }
        @keyframes pulse-ring { 0% { transform: scale(.7); opacity: .7; } 100% { transform: scale(1.1); opacity: 0; } }
        .spinner-ring { position: relative; width: 80px; height: 80px; margin: 0 auto 1.5rem; }
        .spinner-ring div { position: absolute; border: 4px solid var(--primary); opacity: 1; border-radius: 50%; animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite; width: 100%; height: 100%; }
        .success-check { font-size: 4rem; color: var(--success); display: none; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <!-- SHARED NAVBAR -->
    <?php include 'includes/navbar.php'; ?>



    <div class="container" style="padding-top:2.5rem;padding-bottom:4rem;">
        <?php if ($success_msg): ?>
            <div class="alert alert-success animate-fade-in" style="border-left: 5px solid #10b981; background: #ecfdf5; padding: 1.5rem; display: flex; align-items: center; gap: 1.25rem;">
                <div style="width: 48px; height: 48px; background: #d1fae5; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-paper-plane" style="color: #059669; font-size: 1.25rem;"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 0.25rem 0; color: #065f46; font-size: 1.1rem;">Request Sent!</h4>
                    <p style="margin: 0; color: #047857; font-size: 0.9rem; line-height: 1.5;"><?php echo $success_msg; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem;">
            <div class="stat-mini">
                <div class="val"><?php echo array_sum(array_column($stats, 'count')); ?></div>
                <div style="color:var(--gray);font-size:0.8rem;margin-top:0.2rem;">Total Bookings</div>
            </div>
            <div class="stat-mini">
                <div class="val" style="color:#10b981;"><?php echo $stats['confirmed']['count'] ?? 0; ?></div>
                <div style="color:var(--gray);font-size:0.8rem;margin-top:0.2rem;">Confirmed</div>
            </div>
            <div class="stat-mini">
                <div class="val" style="color:#f59e0b;"><?php echo $stats['pending']['count'] ?? 0; ?></div>
                <div style="color:var(--gray);font-size:0.8rem;margin-top:0.2rem;">Pending</div>
            </div>
            <div class="stat-mini">
                <div class="val" style="color:#ef4444;"><?php echo $stats['cancelled']['count'] ?? 0; ?></div>
                <div style="color:var(--gray);font-size:0.8rem;margin-top:0.2rem;">Cancelled</div>
            </div>

        </div>

        <!-- Filter Tabs + Action -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
            <div class="filter-tabs">
                <a href="my_bookings.php" class="filter-tab <?php echo !$filter_status ? 'active' : ''; ?>">All</a>
                <a href="my_bookings.php?status=confirmed" class="filter-tab <?php echo $filter_status === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
                <a href="my_bookings.php?status=pending" class="filter-tab <?php echo $filter_status === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="my_bookings.php?status=cancelled" class="filter-tab <?php echo $filter_status === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
            </div>
            <a href="halls.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Booking</a>
        </div>

        <!-- Bookings List -->
        <?php if (!empty($bookings)): ?>
            <?php foreach ($bookings as $b):
                $status_colors = [
                    'confirmed'  => ['badge' => 'success', 'icon' => 'fa-check-circle', 'color' => '#10b981'],
                    'processing' => ['badge' => 'info',    'icon' => 'fa-sync fa-spin', 'color' => '#3b82f6'],
                    'pending'    => ['badge' => 'warning', 'icon' => 'fa-hourglass-half', 'color' => '#f59e0b'],
                    'cancelled'  => ['badge' => 'danger',  'icon' => 'fa-times-circle', 'color' => '#ef4444'],
                ];
                
                // If paid but still processing, don't show the "Processing" badge yet
                // Only show "Confirmed" when admin manually confirms it
                $display_status = $b['status'];
                $hide_status_badge = false;
                
                if ($b['payment_status'] === 'paid' && $display_status === 'processing') {
                    $hide_status_badge = true; // Hide the "Processing" label if they already paid
                }
                
                $sc = $status_colors[$display_status] ?? $status_colors['pending'];
            ?>
                <div class="booking-card animate-fade-in">
                    <div class="booking-card-header">
                        <div style="display:flex;align-items:center;gap:1rem;">
                            <span style="font-weight:700;font-size:0.8rem;font-family:'Poppins',sans-serif;color:var(--primary);background:var(--primary-light);padding:0.3rem 0.8rem;border-radius:var(--radius-full);"><?php echo htmlspecialchars($b['booking_id']); ?></span>
                            <span style="color:var(--gray-light);font-size:0.78rem;">Booked: <?php echo date('d M Y', strtotime($b['created_at'])); ?></span>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <?php if (!$hide_status_badge): ?>
                                <i class="fas <?php echo $sc['icon']; ?>" style="color:<?php echo $sc['color']; ?>;"></i>
                                <span class="badge badge-<?php echo $sc['badge']; ?>"><?php echo ucfirst($display_status); ?></span>
                            <?php endif; ?>
                            <?php if ($b['status'] === 'confirmed' || $b['status'] === 'processing'): ?>
                                <span class="badge badge-<?php echo $b['payment_status'] === 'paid' ? 'success' : 'danger'; ?>" style="font-size:0.65rem;">
                                    <i class="fas <?php echo $b['payment_status'] === 'paid' ? 'fa-check' : 'fa-clock'; ?>"></i>
                                    <?php echo $b['payment_status'] === 'paid' ? 'Paid' : 'Unpaid'; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="booking-card-body">
                        <div class="booking-hall-img">
                            <?php 
                            $img_path = $b['hall_id'] ? 'halls/' . $b['hall_image'] : 'rooms/' . $b['room_image'];
                            $has_img = $b['hall_id'] ? !empty($b['hall_image']) : !empty($b['room_image']);
                            if ($has_img): ?>
                                <img src="assets/images/<?php echo $img_path; ?>" alt="">
                            <?php else: ?>
                                <div class="placeholder"><i class="fas <?php echo $b['room_id'] ? 'fa-bed' : 'fa-building-columns'; ?>"></i></div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h4 style="margin-bottom:0.35rem;font-size:1.05rem;"><?php echo htmlspecialchars($b['hall_name'] ?? $b['room_name']); ?> <span style="font-size:0.6rem;color:var(--primary);vertical-align:middle;margin-left:0.5rem;background:var(--primary-light);padding:2px 6px;border-radius:4px;"><?php echo $b['room_id'] ? 'ROOM' : 'HALL'; ?></span></h4>
                            <p style="color:var(--gray);font-size:0.82rem;margin-bottom:0.75rem;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($b['hall_location'] ?? $b['room_location']); ?></p>

                            <div style="display:flex;flex-wrap:wrap;gap:1.25rem;font-size:0.8rem;color:var(--dark-3);">
                                <span><i class="fas fa-tag" style="color:var(--primary);"></i> <?php echo htmlspecialchars($b['event_name']); ?></span>
                                <span><i class="fas fa-calendar-alt" style="color:var(--primary);"></i> <?php echo date('d M Y, D', strtotime($b['event_date'])); ?></span>
                                <span><i class="fas fa-clock" style="color:var(--primary);"></i>
                                    <?php if ($b['is_full_day']): ?>
                                        Full Day (9:00am - 11:00pm)
                                    <?php elseif ($b['slot_name']): ?>
                                        <?php echo htmlspecialchars($b['slot_name']); ?>
                                        <?php if ($b['start_time'] && $b['end_time']): ?>
                                            (<?php echo date('g:ia', strtotime($b['start_time'])); ?> - <?php echo date('g:ia', strtotime($b['end_time'])); ?>)
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <div style="text-align:right;min-width:130px;display:flex;flex-direction:column;gap:0.5rem;">
                            <a href="halls.php?<?php echo $b['room_id'] ? 'room_id='.$b['room_id'] : 'id='.$b['hall_id']; ?>" class="btn btn-outline btn-sm">View Details</a>
                            <?php if (($b['status'] === 'confirmed' || $b['status'] === 'processing') && $b['payment_status'] !== 'paid'): 
                                $price = $b['hall_price'] ?: $b['room_price'] ?: 0;
                            ?>
                                <button onclick="openPaymentModal('<?php echo $b['booking_id']; ?>', '<?php echo htmlspecialchars($b['hall_name'] ?? $b['room_name']); ?>', '<?php echo number_format($price); ?>')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-credit-card"></i> Pay Now
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div style="text-align:center;padding:5rem 2rem;background:white;border-radius:var(--radius-xl);border:1px solid var(--border);">
                <div style="margin-bottom: 2rem;">
                    <img src="assets/images/wedding_illust.svg" alt="No Bookings" style="width: 100%; max-width: 300px; opacity: 0.8;">
                </div>
                <h3 style="margin-bottom:0.5rem;">No Bookings Yet</h3>
                <p style="color:var(--gray);margin-bottom:2rem;">You haven't booked any halls yet. Browse our collection and book your perfect venue!</p>
                <a href="halls.php" class="btn btn-primary btn-lg"><i class="fas fa-building"></i> Browse Halls</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/modals.php'; ?>
    <?php include 'includes/chatbot.php'; ?>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal-overlay" onclick="if(event.target===this)closeModal('paymentModal')">
        <div class="modal-box" style="max-width:450px;">
            <button class="modal-close" onclick="closeModal('paymentModal')"><i class="fas fa-times"></i></button>
            <div style="text-align:center;margin-bottom:1.5rem;">
                <div style="width:65px;height:65px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;color:var(--primary);font-size:1.75rem;">
                    <i class="fas fa-shield-check"></i>
                </div>
                <h3>Complete Payment</h3>
                <p style="color:var(--gray);font-size:0.875rem;">Secure Checkout for <span id="payHallName" style="color:var(--dark);font-weight:700;"></span></p>
            </div>

            <div style="background:#f8fafc;padding:1.25rem;border-radius:var(--radius);border:1px solid var(--border);margin-bottom:1.5rem;">
                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;font-size:0.9rem;">
                    <span style="color:var(--gray);">Booking ID:</span>
                    <span id="payBookingId" style="font-weight:700;color:var(--primary);"></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:800;color:var(--dark);">
                    <span>Amount:</span>
                    <span>Rs. <span id="payAmount"></span></span>
                </div>
            </div>

            <form id="paymentForm" onsubmit="handlePaymentSubmit(event)">
                <input type="hidden" id="hiddenBookingId" name="booking_id">
                <div class="form-group">
                    <label>Payment Method</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                        <input type="hidden" id="selected_method" name="payment_method" value="card">
                        <div id="pay-card" onclick="selectMethod('card')" style="border:2px solid var(--primary);padding:0.8rem;border-radius:var(--radius);display:flex;align-items:center;gap:0.5rem;background:var(--primary-light);cursor:pointer;transition:all 0.3s;border-color:var(--primary);">
                            <i class="fab fa-cc-visa" style="color:#1a1f71;font-size:1.2rem;"></i>
                            <span style="font-size:0.85rem;font-weight:700;">Visa / Card</span>
                        </div>
                        <div id="pay-upi" onclick="selectMethod('upi')" style="border:2px solid var(--border);padding:0.8rem;border-radius:var(--radius);display:flex;align-items:center;GAP:0.5rem;cursor:pointer;transition:all 0.3s;opacity:0.6;">
                            <i class="fas fa-mobile-screen-button" style="color:var(--success);font-size:1.2rem;"></i>
                            <span style="font-size:0.85rem;font-weight:700;">UPI / GPay</span>
                        </div>
                    </div>
                </div>
                
                <!-- Card Fields -->
                <div id="card-fields">
                    <div class="form-group">
                        <label>Card Number</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-credit-card"></i>
                            <input type="text" class="form-control" name="card_num" placeholder="XXXX XXXX XXXX 4242" value="4242 4242 4242 4242">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label>Expiry</label>
                            <input type="text" class="form-control" name="expiry" placeholder="MM/YY" value="12/28">
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="password" class="form-control" name="cvv" placeholder="***" value="123">
                        </div>
                    </div>
                </div>

                <!-- UPI Fields -->
                <div id="upi-fields" style="display:none;">
                    <div class="form-group">
                        <label>UPI ID / Phone Number</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-mobile-screen"></i>
                            <input type="text" class="form-control" name="upi_id" placeholder="your-name@upi" value="user@okaxis">
                        </div>
                    </div>
                    <div style="text-align:center; padding:1rem; border:1px dashed var(--border); border-radius:var(--radius); margin-bottom:1rem; background:#fcfcfc;">
                        <div style="font-size:1.5rem; color:var(--success); margin-bottom:0.3rem;">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div style="font-weight:700; font-size:0.85rem; color:var(--dark);">subhamahal@payment</div>
                    </div>
                </div>

                <button type="submit" id="payBtn" class="btn btn-primary" style="width:100%;justify-content:center;padding:1rem;">
                    <i class="fas fa-lock"></i> Pay Securely Now
                </button>
            </form>
        </div>
    </div>

    <!-- Processing Overlay -->
    <div id="paymentOverlay" class="payment-overlay">
        <div class="processing-box">
            <div id="paySpinner" class="spinner-ring"><div></div></div>
            <div id="paySuccessIcon" class="success-check"><i class="fas fa-check-circle"></i></div>
            <h2 id="payStatusTitle" style="margin-bottom:0.5rem;">Processing Payment...</h2>
            <p id="payStatusDesc" style="color:rgba(255,255,255,0.6); font-size:0.9rem;">Please do not refresh or close the window.</p>
        </div>
    </div>

    <script>
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('navbar');
            if(nav) nav.classList.toggle('scrolled', window.scrollY > 50);
        });

        function openPaymentModal(bookingId, hallName, amount) {
            document.getElementById('payBookingId').innerText = bookingId;
            document.getElementById('payHallName').innerText = hallName;
            document.getElementById('payAmount').innerText = amount;
            document.getElementById('hiddenBookingId').value = bookingId;
            selectMethod('card'); // Reset to card by default
            openModal('paymentModal');
        }

        function selectMethod(method) {
            const cardBtn = document.getElementById('pay-card');
            const upiBtn = document.getElementById('pay-upi');
            const cardFields = document.getElementById('card-fields');
            const upiFields = document.getElementById('upi-fields');
            const hiddenInput = document.getElementById('selected_method');

            hiddenInput.value = method;

            if (method === 'card') {
                cardBtn.style.borderColor = 'var(--primary)';
                cardBtn.style.background = 'var(--primary-light)';
                cardBtn.style.opacity = '1';
                
                upiBtn.style.borderColor = 'var(--border)';
                upiBtn.style.background = 'transparent';
                upiBtn.style.opacity = '0.6';
                
                cardFields.style.display = 'block';
                upiFields.style.display = 'none';
            } else {
                upiBtn.style.borderColor = 'var(--primary)';
                upiBtn.style.background = 'var(--primary-light)';
                upiBtn.style.opacity = '1';
                
                cardBtn.style.borderColor = 'var(--border)';
                cardBtn.style.background = 'transparent';
                cardBtn.style.opacity = '0.6';
                
                cardFields.style.display = 'none';
                upiFields.style.display = 'block';
            }
        }

        async function handlePaymentSubmit(event) {
            event.preventDefault();
            
            const overlay = document.getElementById('paymentOverlay');
            const spinner = document.getElementById('paySpinner');
            const successIcon = document.getElementById('paySuccessIcon');
            const title = document.getElementById('payStatusTitle');
            const desc = document.getElementById('payStatusDesc');

            // Show Overlay
            overlay.style.display = 'flex';
            
            // Phase 1: Contacting Bank
            setTimeout(() => {
                title.innerText = "Verifying Details...";
                desc.innerText = "Establishing secure connection to your bank.";
            }, 800);

            // Phase 2: Processing
            setTimeout(async () => {
                title.innerText = "Finalizing Transaction...";
                desc.innerText = "Almost there! Confirming reservation.";

                const formData = new FormData(document.getElementById('paymentForm'));

                try {
                    const response = await fetch('actions/process_payment.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        // Success Transition
                        spinner.style.display = 'none';
                        successIcon.style.display = 'block';
                        title.innerText = "Payment Successful!";
                        desc.innerText = "Redirecting you back to your bookings...";
                        
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        overlay.style.display = 'none';
                        alert(data.message || 'Payment failed. Please try again.');
                    }
                } catch (error) {
                    overlay.style.display = 'none';
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                }
            }, 2000);
        }
    </script>
</body>
</html>


