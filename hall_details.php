<?php
require_once 'includes/auth_functions.php';

if (!isset($_GET['id'])) {
    header('Location: halls.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM halls WHERE id = ?");
$stmt->execute([$_GET['id']]);
$hall = $stmt->fetch();

if (!$hall) {
    header('Location: halls.php');
    exit();
}

// Get slots for availability check
$slots_stmt = $pdo->query("SELECT * FROM slots WHERE status = 'active'");
$slots = $slots_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($hall['name']); ?> | HallBooking</title>
    <link rel="stylesheet" href="assets/css/style.css?v=rose2">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
</head>
<body style="padding-top: 80px;">
    <nav class="scrolled">
        <div class="logo">HallBooking</div>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="halls.php">Find Halls</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="my_bookings.php">My Bookings</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="admin/dashboard.php">Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="btn btn-secondary">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php" class="btn btn-primary">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main style="padding: 3rem 10%;">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 3rem;">
            <!-- Hall Content -->
            <div>
                <div style="height: 400px; background: #ddd; border-radius: 20px; overflow: hidden; margin-bottom: 2rem;">
                    <?php if ($hall['main_image']): ?>
                        <img src="assets/images/<?php echo $hall['main_image']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 2rem;">
                            No Image Available
                        </div>
                    <?php endif; ?>
                </div>

                <h1 style="font-size: 3rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($hall['name']); ?></h1>
                <p style="font-size: 1.25rem; color: var(--gray); margin-bottom: 2rem;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hall['location']); ?></p>

                <div class="card" style="margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem;">Description</h3>
                    <p style="color: #4b5563;"><?php echo nl2br(htmlspecialchars($hall['description'])); ?></p>
                </div>

                <div class="card">
                    <h3 style="margin-bottom: 1rem;">Facilities</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                        <?php 
                        $facilities = explode(',', $hall['facilities']);
                        foreach ($facilities as $facility): 
                        ?>
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: #4b5563;">
                                <i class="fas fa-check-circle" style="color: var(--primary);"></i>
                                <?php echo trim(htmlspecialchars($facility)); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Booking Sidebar -->
            <div style="position: sticky; top: 110px; height: fit-content;">
                <div class="card" style="border: 1px solid #e2e8f0;">
                    <h3 style="margin-bottom: 1.5rem; text-align: center;">Book This Hall</h3>
                    <div style="text-align: center; margin-bottom: 2rem; padding: 1rem; background: #fef2f2; border-radius: 12px;">
                        <span style="font-size: 0.8rem; color: var(--gray);">Starting from</span>
                        <h2 style="color: var(--secondary); font-size: 2rem;">Rs. <?php echo number_format($hall['price_per_day']); ?></h2>
                    </div>

                    <form action="actions/book_hall.php" method="POST">
                        <input type="hidden" name="hall_id" value="<?php echo $hall['id']; ?>">
                        
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Select Date</label>
                            <input type="date" name="event_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Select Time Slot</label>
                            <?php foreach ($slots as $slot): ?>
                                <label style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; padding: 0.75rem; border: 1px solid #eee; border-radius: 10px; cursor: pointer;">
                                    <input type="radio" name="slot_id" value="<?php echo $slot['id']; ?>" required>
                                    <div>
                                        <p style="font-weight: 600;"><?php echo htmlspecialchars($slot['name']); ?></p>
                                        <p style="font-size: 0.8rem; color: var(--gray);"><?php echo date('h:i A', strtotime($slot['start_time'])); ?> - <?php echo date('h:i A', strtotime($slot['end_time'])); ?></p>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>


                        <?php if (isLoggedIn()): ?>
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; margin-bottom: 1rem;">Book Now</button>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary" style="width: 100%; text-align: center; display: block;">Login to Book</a>
                        <?php endif; ?>
                    </form>
                    
                    <p style="font-size: 0.8rem; color: var(--gray); text-align: center;">No payment required upfront. Subject to confirmation.</p>
                </div>
            </div>
        </div>
    </main>

    <footer style="background: var(--dark); color: white; padding: 3rem 10%; text-align: center;">
        <p>&copy; 2026 HallBooking Management System. All rights reserved.</p>
    </footer>
    <script src="assets/js/validation.js"></script>
</body>
</html>



