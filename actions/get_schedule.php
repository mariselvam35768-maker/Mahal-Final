<?php
// Buffer ALL output from top to prevent any stray content corrupting JSON
ob_start();

// Only include db.php directly (no session_start needed for read-only public data)
require_once '../includes/db.php';

// Clean any buffered output from includes before we send headers
ob_clean();

header('Content-Type: application/json; charset=utf-8');

$hall_id = (int)($_GET['hall_id'] ?? 0);
$month   = trim($_GET['month'] ?? date('Y-m'));

// Validate month format strictly
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

if (!$hall_id || !isset($pdo)) {
    echo json_encode(['html' => '<p style="color:red;text-align:center;padding:2rem;">Error: Invalid request.</p>', 'label' => '', 'prev' => '', 'next' => '']);
    exit();
}

$month_start = $month . '-01';
$month_end   = date('Y-m-t', strtotime($month_start));
$prev_month  = date('Y-m', strtotime($month_start . ' -1 month'));
$next_month  = date('Y-m', strtotime($month_start . ' +1 month'));
$label       = date('F Y', strtotime($month_start));

$booked_dates = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.event_date, b.is_full_day, s.name AS slot_name, b.event_name, b.status, u.name AS user_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        LEFT JOIN slots s ON b.slot_id = s.id
        WHERE b.hall_id = ? AND b.event_date >= ? AND b.event_date <= ? AND b.status != 'cancelled'
        ORDER BY b.event_date ASC
    ");
    $stmt->execute([$hall_id, $month_start, $month_end]);
    $booked_dates = $stmt->fetchAll();
} catch (Exception $e) {
    echo json_encode(['html' => '<p style="color:red;text-align:center;padding:2rem;">DB error: ' . htmlspecialchars($e->getMessage()) . '</p>', 'label' => $label, 'prev' => $prev_month, 'next' => $next_month]);
    exit();
}

// Build HTML into variable
ob_start();
if (!empty($booked_dates)): ?>
<div style="overflow-x:auto;">
    <table class="occupancy-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Event</th>
                <th>Slot</th>
                <th>Booked By</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($booked_dates as $bk): ?>
                <tr>
                    <td><strong><?php echo date('d M Y (D)', strtotime($bk['event_date'])); ?></strong></td>
                    <td><?php echo htmlspecialchars($bk['event_name'] ?? '-'); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $bk['is_full_day'] ? 'primary' : 'info'; ?>" style="white-space:nowrap;">
                            <?php echo $bk['is_full_day'] ? 'Full Day (9:00am - 11:00pm)' : htmlspecialchars($bk['slot_name'] ?? '-'); ?>
                        </span>
                    </td>
                    <td style="color:var(--gray);"><?php echo htmlspecialchars(substr($bk['user_name'], 0, 1) . '***'); ?></td>
                    <td><span class="badge badge-<?php 
                        $st = $bk['status'];
                        if ($st === 'confirmed') echo 'success';
                        elseif ($st === 'pending' || $st === 'processing') echo 'warning';
                        else echo 'info';
                    ?>"><?php echo ucfirst($bk['status']); ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div style="text-align:center;padding:3rem;">
    <i class="fas fa-calendar-check" style="font-size:2.5rem;margin-bottom:1rem;color:#10b981;display:block;"></i>
    <p style="font-size:1rem;font-weight:600;color:#10b981;">Fully Available</p>
    <p style="font-size:0.875rem;color:#94a3b8;">No bookings this month. All slots are free!</p>
</div>
<?php endif;
$html = ob_get_clean();

echo json_encode([
    'html'  => $html,
    'label' => $label,
    'prev'  => $prev_month,
    'next'  => $next_month,
]);
?>
