<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

$from_date = $_GET['from_date'] ?? date('Y-m-d', strtotime('-30 days'));
$to_date = $_GET['to_date'] ?? date('Y-m-d');

try {
    // Basic stats for the period
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
        FROM bookings 
        WHERE event_date BETWEEN ? AND ?
    ");
    $stmt->execute([$from_date, $to_date]);
    $period_stats = $stmt->fetch();

    // Revenue by Hall (for the period)
    $stmt = $pdo->prepare("
        SELECT h.name as hall_name, COUNT(b.id) as booking_count
        FROM halls h
        LEFT JOIN bookings b ON h.id = b.hall_id AND b.status = 'confirmed' AND b.event_date BETWEEN ? AND ?
        GROUP BY h.id, h.name
        ORDER BY booking_count DESC
    ");
    $stmt->execute([$from_date, $to_date]);
    $hall_stats = $stmt->fetchAll();

    // Monthly Breakdown
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(event_date, '%Y-%m') as month_raw,
            DATE_FORMAT(event_date, '%M %Y') as month_name,
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings
        FROM bookings
        WHERE event_date BETWEEN ? AND ?
        GROUP BY month_raw, month_name
        ORDER BY month_raw ASC
    ");
    $stmt->execute([$from_date, $to_date]);
    $monthly_stats = $stmt->fetchAll();

    $best_booking_month = null;
    $best_revenue_month = null;
    $max_bookings = 0;
    $max_revenue = 0;

    foreach ($monthly_stats as $ms) {
        if ($ms['confirmed_bookings'] > $max_bookings) {
            $max_bookings = $ms['confirmed_bookings'];
            $best_booking_month = $ms['month_name'];
        }
        if ($ms['total_revenue'] > $max_revenue) {
            $max_revenue = $ms['total_revenue'];
            $best_revenue_month = $ms['month_name'];
        }
    }

} catch (Exception $e) {
    $period_stats = ['total_bookings'=>0, 'confirmed_bookings'=>0, 'cancelled_bookings'=>0];
    $hall_stats = [];
    $monthly_stats = [];
    $best_booking_month = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=reports">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); }
        .greeting { font-size: 1.6rem; font-weight: 800; margin-bottom: 0.25rem; }
        .admin-stat-card { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; background: #fff; border-radius: 1rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .glass-card { background: rgba(255,255,255,0.85); backdrop-filter: blur(8px); }
        .stat-icon { width: 52px; height: 52px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
        .stagger-children > * { opacity: 1; }
        .reveal { opacity: 1; }

        /* Print styles */
        @media print {
            @page { margin: 1cm; }
            body { background: #fff !important; line-height: 1.3; }
            .admin-sidebar, .admin-topbar, .no-print, .reveal[style*="text-align:center"], .stagger-children { display: none !important; }
            .admin-main { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .admin-content { padding: 0 !important; margin: 0 !important; }
            .greeting { margin-bottom: 0 !important; }
            .admin-table-card { margin-top: 0.5rem !important; margin-bottom: 1rem !important; padding: 0 !important; box-shadow: none !important; border: none !important; break-inside: avoid; }
            .admin-table-header { padding-bottom: 0.5rem !important; border-bottom: none !important; }
            h3, h4 { margin-top: 0 !important; margin-bottom: 0.25rem !important; }
            .badge { border: 1px solid #ccc; background: transparent !important; color: #000 !important; }
            table { width: 100% !important; border-collapse: collapse !important; }
            th, td { border: 1px solid #ddd !important; padding: 0.5rem !important; }
            div[style*="border-bottom: 2px solid var(--border)"] { margin-bottom: 0.5rem !important; padding-bottom: 0.5rem !important; }
            div[style*="margin-top:2rem;"], div[style*="margin-top: 2rem;"] { margin-top: 0.5rem !important; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <!-- SIDEBAR -->
    <?php include '_sidebar.php'; ?>

    <!-- MAIN -->
    <div class="admin-main">
        <!-- Topbar -->
        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <h2 style="font-weight:700; font-size:1.1rem; margin:0; color:var(--dark);">Management Reports</h2>
                <span style="font-size:0.78rem; color:var(--gray); margin-top:0.2rem;">Generate and print business performance</span>
            </div>
            <div style="display:flex;align-items:center;gap:1rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;background:var(--bg);padding:0.4rem 1rem;border-radius:var(--radius-full);border:1px solid var(--border);font-size:0.85rem;font-weight:600;">
                    <i class="fas fa-user-shield" style="color:var(--primary);"></i>
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                </div>
            </div>
        </div>

        <div class="admin-content">
            <div class="reveal" style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:1rem;">
                <div>
                    <div class="greeting">Reports & Analytics</div>
                    <div style="color:var(--gray);font-size:0.9rem;">View booking performance and revenue data across your venues.</div>
                </div>
                <button onclick="window.print()" class="btn btn-outline no-print" style="padding: 0.6rem 1.25rem;">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>

            <!-- Filter Section -->
            <div class="admin-table-card no-print reveal" style="margin-bottom:1.5rem; padding: 1.5rem;">
                <form method="GET" action="reports.php" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
                    <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                        <label>From Date</label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                        <label>To Date</label>
                        <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="form-control" required>
                    </div>
                    <div style="margin:0;">
                        <button type="submit" class="btn btn-primary" style="height: 46px;">Generate Report</button>
                    </div>
                </form>
            </div>

            <!-- Report Header -->
            <div style="margin-bottom: 2rem; border-bottom: 2px solid var(--border); padding-bottom: 1rem;">
                <h3 style="margin:0; font-size:1.25rem;">Performance Report</h3>
                <p style="margin:0; color:var(--gray); font-size:0.9rem;">Period: <?php echo date('d M Y', strtotime($from_date)); ?> to <?php echo date('d M Y', strtotime($to_date)); ?></p>
            </div>

            <!-- Stat Cards -->
            <div class="stagger-children" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.25rem;margin-bottom:2rem;">


                <div class="admin-stat-card glass-card reveal">
                    <div class="stat-icon" style="background:#fce7f3;color:#e91e63;">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <div style="font-size:0.75rem;color:var(--gray);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem;">Total Events Scheduled</div>
                        <div style="font-size:1.6rem;font-weight:800;font-family:'Poppins',sans-serif;color:var(--dark);line-height:1;"><?php echo $period_stats['total_bookings']; ?></div>
                        <div style="font-size:0.72rem;color:var(--gray-light);margin-top:0.2rem;">Across all venues</div>
                    </div>
                </div>

                <div class="admin-stat-card glass-card reveal">
                    <div class="stat-icon" style="background:#dcfce7;color:#16a34a;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div style="font-size:0.75rem;color:var(--gray);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem;">Confirmed Bookings</div>
                        <div style="font-size:1.6rem;font-weight:800;font-family:'Poppins',sans-serif;color:var(--dark);line-height:1;"><?php echo $period_stats['confirmed_bookings']; ?></div>
                        <div style="font-size:0.72rem;color:var(--gray-light);margin-top:0.2rem;">Successfully verified</div>
                    </div>
                </div>

                <div class="admin-stat-card glass-card reveal">
                    <div class="stat-icon" style="background:#fee2e2;color:#ef4444;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <div style="font-size:0.75rem;color:var(--gray);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem;">Cancelled Bookings</div>
                        <div style="font-size:1.6rem;font-weight:800;font-family:'Poppins',sans-serif;color:var(--dark);line-height:1;"><?php echo $period_stats['cancelled_bookings']; ?></div>
                        <div style="font-size:0.72rem;color:var(--gray-light);margin-top:0.2rem;">Failed or revoked</div>
                    </div>
                </div>
            </div>

            <!-- Hall Breakdown -->
            <div class="admin-table-card reveal">
                <div class="admin-table-header">
                    <h4 style="margin:0;font-size:1rem;">Revenue & Performance by Venue</h4>
                </div>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Hall Name</th>
                                <th style="text-align:center;">Confirmed Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($hall_stats)): ?>
                                <tr><td colspan="3" style="text-align:center;color:var(--gray-light);padding:2rem;">No bookings found for the selected period.</td></tr>
                            <?php else: foreach ($hall_stats as $hs): ?>
                                <tr>
                                    <td style="font-weight:600; font-size:0.9rem;"><?php echo htmlspecialchars($hs['hall_name']); ?></td>
                                    <td style="text-align:center; font-weight:700; color:var(--primary);"><?php echo $hs['booking_count']; ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            
                            <!-- Totals Row -->
                            <tr style="background-color: #f8fafc; border-top: 2px solid var(--border);">
                                <td style="font-weight:800; font-size:1rem; text-align:right;">TOTALS:</td>
                                <td style="text-align:center; font-weight:800; font-size:1rem; color:var(--dark);"><?php echo $period_stats['confirmed_bookings']; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Monthly Breakdown -->
            <div class="admin-table-card reveal" style="margin-top:2rem;">
                <div class="admin-table-header" style="justify-content:space-between; flex-wrap:wrap; gap:1rem;">
                    <h4 style="margin:0;font-size:1rem;">Monthly Performance Breakdown</h4>
                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                        <?php if ($best_booking_month): ?>
                            <span class="badge badge-primary" style="font-size:0.75rem;"><i class="fas fa-star"></i> Most Bookings: <strong><?php echo $best_booking_month; ?></strong></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Month & Year</th>
                                <th style="text-align:center;">Total Inquiries</th>
                                <th style="text-align:center;">Confirmed Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($monthly_stats)): ?>
                                <tr><td colspan="4" style="text-align:center;color:var(--gray-light);padding:2rem;">No monthly data found for the selected period.</td></tr>
                            <?php else: foreach ($monthly_stats as $ms): ?>
                                <tr>
                                    <td style="font-weight:600; font-size:0.9rem;"><?php echo htmlspecialchars($ms['month_name']); ?></td>
                                    <td style="text-align:center; font-weight:600; color:var(--gray);"><?php echo $ms['total_bookings']; ?></td>
                                    <td style="text-align:center; font-weight:700; color:var(--primary);">
                                        <?php echo $ms['confirmed_bookings']; ?>
                                        <?php if ($ms['month_name'] === $best_booking_month && $ms['confirmed_bookings'] > 0): ?>
                                            <i class="fas fa-star" style="color:#fbbf24; margin-left:4px; font-size:0.7rem;" title="Top Booking Month"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="margin-top:2rem; text-align:center; font-size:0.8rem; color:var(--gray-light);" class="reveal">
                Report Generated on <?php echo date('d M Y, h:i A'); ?>
            </div>

        </div>
    </div>
</div>
</body>
</html>
