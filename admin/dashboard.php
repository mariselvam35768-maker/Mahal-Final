<?php
require_once '../includes/auth_functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: adminlogin.php');
    exit();
}

// Dashboard stats
try {
    $stats = [
        'halls'    => $pdo->query("SELECT COUNT(*) FROM halls")->fetchColumn(),
        'bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'users'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
        'pending'  => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(),
        'processing'=> $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='processing'")->fetchColumn(),
        'confirmed'=> $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn(),
    ];

    // Recent bookings
    $recent = $pdo->query("
        SELECT b.*, h.name AS hall_name, u.name AS user_name, u.phone AS user_phone
        FROM bookings b
        JOIN halls h ON b.hall_id = h.id
        JOIN users u ON b.user_id = u.id
        ORDER BY b.created_at DESC LIMIT 8
    ")->fetchAll();

    // Monthly bookings for chart (last 6 months)
    $monthly = $pdo->query("
        SELECT DATE_FORMAT(event_date,'%b %Y') AS month_label, COUNT(*) AS cnt
        FROM bookings
        WHERE event_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(event_date), MONTH(event_date)
        ORDER BY YEAR(event_date) ASC, MONTH(event_date) ASC
        LIMIT 6
    ")->fetchAll();

    // Hall utilization
    $hall_util = $pdo->query("
        SELECT h.name, COUNT(b.id) AS booking_count
        FROM halls h
        LEFT JOIN bookings b ON h.id = b.hall_id AND b.status = 'confirmed'
        GROUP BY h.id, h.name
        ORDER BY booking_count DESC
        LIMIT 5
    ")->fetchAll();

} catch (Exception $e) {
    $stats = ['halls'=>0,'bookings'=>0,'users'=>0,'pending'=>0,'confirmed'=>0];
    $recent = []; $monthly = []; $hall_util = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Sri Lakshmi Residency & Mahal</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=rose2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: var(--bg); }
        .greeting { font-size: 1.6rem; font-weight: 800; margin-bottom: 0.25rem; }
        .stat-trend { font-size: 0.75rem; color: #10b981; font-weight: 600; margin-top: 0.25rem; }
        .donut-wrap { position: relative; width: 120px; height: 120px; }
        .quick-action { display: flex; align-items: center; gap: 0.75rem; padding: 0.875rem 1rem; background: #f8fafc; border-radius: var(--radius); border: 1px solid var(--border); transition: var(--transition); font-size: 0.875rem; font-weight: 600; color: var(--dark-2); }
        .quick-action:hover { background: var(--primary-light); border-color: var(--primary); color: var(--primary); }
        .quick-action i { color: var(--primary); width: 18px; text-align: center; }

        /* Stat Cards */
        .admin-stat-card { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; background: #fff; border-radius: 1rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .glass-card { background: rgba(255,255,255,0.85); backdrop-filter: blur(8px); }
        .stat-icon { width: 52px; height: 52px; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
        .stagger-children > * { opacity: 1; }
        .reveal { opacity: 1; }

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
            <div>
                <div style="font-weight:700;font-size:1rem;">Dashboard Overview</div>
                <!--<div style="font-size:0.78rem;color:var(--gray);"><?php echo date('l, d F Y'); ?></div>-->
            </div>
            <div style="display:flex;align-items:center;gap:1rem;">
                <?php if ($stats['pending'] > 0): ?>
                    <a href="manage_bookings.php?status=pending" style="display:flex;align-items:center;gap:0.5rem;background:#fef3c7;color:#92400e;padding:0.4rem 0.85rem;border-radius:var(--radius-full);font-size:0.75rem;font-weight:700;">
                        <i class="fas fa-bell"></i> <?php echo $stats['pending']; ?> Pending
                    </a>
                <?php endif; ?>
                <div style="display:flex;align-items:center;gap:0.5rem;background:var(--bg);padding:0.4rem 1rem;border-radius:var(--radius-full);border:1px solid var(--border);font-size:0.85rem;font-weight:600;">
                    <i class="fas fa-user-shield" style="color:var(--primary);"></i>
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                </div>
            </div>
        </div>

        <div class="admin-content">
            <!-- Greeting -->
            <div class="reveal" style="margin-bottom:1rem;">
                <div class="greeting">Welcome, Admin!</div>
                <div style="color:var(--gray);font-size:0.9rem;">Here's what's happening with your halls today.</div>
            </div>


            <!-- Stat Cards -->
            <div class="stagger-children" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.25rem;margin-bottom:1.5rem;">
                <?php
                $stat_items = [
                    ['label'=>'Total Halls',  'val'=>$stats['halls'],    'icon'=>'fas fa-building', 'bg'=>'#fce7f3','color'=>'#e91e63','sub'=>'Active venues'],
                    ['label'=>'Total Bookings','val'=>$stats['bookings'],'icon'=>'fas fa-calendar-check','bg'=>'#fce7f3','color'=>'#e91e63','sub'=>'All time'],
                    ['label'=>'Active Users', 'val'=>$stats['users'],    'icon'=>'fas fa-users',  'bg'=>'#fce7f3','color'=>'#e91e63','sub'=>'Registered'],
                    ['label'=>'Pending',       'val'=>$stats['pending'], 'icon'=>'fas fa-hourglass-half','bg'=>'#fce7f3','color'=>'#e91e63','sub'=>'Awaiting review'],
                    ['label'=>'Processing',    'val'=>$stats['processing'], 'icon'=>'fas fa-sync','bg'=>'#fce7f3','color'=>'#e91e63','sub'=>'In progress'],
                    ['label'=>'Confirmed',     'val'=>$stats['confirmed'],'icon'=>'fas fa-check-circle','bg'=>'#fce7f3','color'=>'#e91e63','sub'=>'This month'],
                ];
                foreach ($stat_items as $item): ?>
                    <div class="admin-stat-card glass-card reveal">
                        <div class="stat-icon" style="background:<?php echo $item['bg']; ?>;color:<?php echo $item['color']; ?>;">
                            <i class="<?php echo $item['icon']; ?>"></i>
                        </div>
                        <div>
                            <div style="font-size:0.75rem;color:var(--gray);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem;"><?php echo $item['label']; ?></div>
                            <div style="font-size:1.6rem;font-weight:800;font-family:'Poppins',sans-serif;color:var(--dark);line-height:1;"><?php echo $item['val']; ?></div>
                            <div style="font-size:0.72rem;color:var(--gray-light);margin-top:0.2rem;"><?php echo $item['sub']; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Two Column Layout -->
            <div style="display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start;">
                <!-- Recent Bookings -->
                <div class="admin-table-card">
                    <div class="admin-table-header">
                        <div>
                            <h4 style="margin:0;font-size:1rem;">Recent Bookings</h4>
                            <p style="margin:0;font-size:0.78rem;color:var(--gray);">Latest booking requests</p>
                        </div>
                        <a href="manage_bookings.php" class="btn btn-outline btn-sm">View All</a>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Customer</th>
                                    <th>Hall</th>
                                    <th>Date</th>
                                     <th>Status</th>
                                     <th>Payment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent)): ?>
                                    <tr><td colspan="8" style="text-align:center;color:var(--gray-light);padding:3rem;">No bookings yet.</td></tr>
                                <?php else: foreach ($recent as $b): ?>
                                    <tr>
                                        <td style="font-weight:700;color:var(--primary);font-size:0.78rem;"><?php echo $b['booking_id']; ?></td>
                                        <td>
                                            <div style="font-weight:600;font-size:0.875rem;"><?php echo htmlspecialchars($b['user_name']); ?></div>
                                            <div style="font-size:0.72rem;color:var(--gray);"><?php echo htmlspecialchars($b['user_phone']); ?></div>
                                        </td>
                                        <td style="font-size:0.875rem;"><?php echo htmlspecialchars($b['hall_name']); ?></td>
                                        <td style="font-size:0.8rem;"><?php echo date('d M Y', strtotime($b['event_date'])); ?></td>

                                        <td>
                                            <?php 
                                            $sc = [
                                                'confirmed'=>'success',
                                                'pending'=>'warning',
                                                'processing'=>'info',
                                                'cancelled'=>'danger'
                                            ][$b['status']] ?? 'warning'; 
                                            ?>
                                            <span class="badge badge-<?php echo $sc; ?>"><?php echo ucfirst($b['status']); ?></span>
                                        </td>
                                         <td>
                                             <?php $pc = $b['payment_status'] === 'paid' ? 'success' : 'danger'; ?>
                                             <span class="badge badge-<?php echo $pc; ?>" style="font-size: 0.65rem;">
                                                 <?php echo ucfirst($b['payment_status']); ?>
                                             </span>
                                        </td>
                                        <td>
                                            <a href="manage_bookings.php" class="btn btn-primary btn-sm">Review</a>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="display:flex;flex-direction:column;gap:1.5rem;">
                    <!-- Quick Actions -->
                    <div class="admin-table-card" style="overflow:visible;">
                        <div class="admin-table-header">
                            <h4 style="margin:0;font-size:1rem;">Quick Actions</h4>
                        </div>
                        <div style="padding:1rem;display:flex;flex-direction:column;gap:0.5rem;">
                            <a href="manage_halls.php?action=add" class="quick-action"><i class="fas fa-plus"></i> Add New Hall</a>
                            <a href="manage_bookings.php?status=pending" class="quick-action"><i class="fas fa-hourglass-half"></i> Review Pending (<?php echo $stats['pending']; ?>)</a>
                            <a href="manage_users.php" class="quick-action"><i class="fas fa-users"></i> Manage Users</a>

                        </div>
                    </div>

                    <!-- Hall Utilization -->
                    <?php if (!empty($hall_util)): ?>
                    <div class="admin-table-card" style="overflow:visible;">
                        <div class="admin-table-header">
                            <h4 style="margin:0;font-size:1rem;">Hall Utilization</h4>
                        </div>
                        <div style="padding:1.25rem;">
                            <?php foreach ($hall_util as $hu):
                                $maxCount = max(array_column($hall_util, 'booking_count')) ?: 1;
                                $pct = ($hu['booking_count'] / $maxCount) * 100;
                            ?>
                                <div style="margin-bottom:1rem;">
                                    <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:0.35rem;">
                                        <span style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:70%;"><?php echo htmlspecialchars($hu['name']); ?></span>
                                        <span style="color:var(--primary);font-weight:700;"><?php echo $hu['booking_count']; ?> bookings</span>
                                    </div>
                                    <div style="height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                                        <div style="height:100%;width:<?php echo $pct; ?>%;background:var(--gradient-primary);border-radius:3px;transition:width 1s ease;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>


