<?php
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

$totalRevenue   = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status IN ('paid','partial')")->fetchColumn();
$totalBookings  = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$pendingCount   = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
$approvedCount  = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='approved'")->fetchColumn();

$thisMonth = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='paid' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
$lastMonth = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='paid' AND MONTH(created_at)=MONTH(NOW()-INTERVAL 1 MONTH) AND YEAR(created_at)=YEAR(NOW()-INTERVAL 1 MONTH)")->fetchColumn();
$revChange = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;

$recentBookings = $pdo->query(
    "SELECT b.*, s.name AS service_name,
            CONCAT(u.first_name,' ',u.last_name) AS customer_name,
            p.payment_status
     FROM bookings b
     JOIN services s ON b.service_id=s.id
     JOIN users u ON b.user_id=u.id
     LEFT JOIN payments p ON p.booking_id=b.id
     ORDER BY b.created_at DESC LIMIT 8"
)->fetchAll();

$upcomingEvents = $pdo->query(
    "SELECT b.*, s.name AS service_name,
            CONCAT(u.first_name,' ',u.last_name) AS customer_name
     FROM bookings b
     JOIN services s ON b.service_id=s.id
     JOIN users u ON b.user_id=u.id
     WHERE b.status='approved' AND b.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY)
     ORDER BY b.event_date ASC LIMIT 5"
)->fetchAll();

$svcBreakdown = $pdo->query(
    "SELECT s.name, COUNT(b.id) AS cnt FROM services s
     LEFT JOIN bookings b ON b.service_id=s.id
     GROUP BY s.id ORDER BY cnt DESC"
)->fetchAll();

$svcLabels = json_encode(array_column($svcBreakdown,'name'));
$svcValues = json_encode(array_column($svcBreakdown,'cnt'));
?>

<!-- Stat Cards -->
<div class="stat-cards">
    <div class="stat-card blue">
        <div class="stat-icon">&#128176;</div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value">₱<?= number_format($totalRevenue,0) ?></div>
        <span class="stat-change <?= $revChange>=0?'up':'down' ?>"><?= $revChange>=0?'▲':'▼' ?> <?= abs($revChange) ?>% vs last month</span>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">&#128197;</div>
        <div class="stat-label">Total Bookings</div>
        <div class="stat-value"><?= $totalBookings ?></div>
        <span class="stat-change up">▲ <?= $approvedCount ?> approved</span>
    </div>
    <div class="stat-card amber">
        <div class="stat-icon">&#128101;</div>
        <div class="stat-label">Total Customers</div>
        <div class="stat-value"><?= $totalCustomers ?></div>
        <span class="stat-change up">Active users</span>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon">&#9203;</div>
        <div class="stat-label">Pending Approval</div>
        <div class="stat-value"><?= $pendingCount ?></div>
        <span class="stat-change <?= $pendingCount>0?'down':'up' ?>"><?= $pendingCount>0?'Needs attention':'All clear!' ?></span>
    </div>
</div>

<!-- Charts Row -->
<div class="charts-row">
    <div class="chart-card">
        <div class="chart-card-header">
            <div>
                <div class="chart-card-title">Booking & Revenue Trends</div>
                <div class="chart-card-sub">Monthly activity overview</div>
            </div>
        </div>
        <canvas id="bookingsChart" height="80"></canvas>
    </div>
    <div class="chart-card">
        <div class="chart-card-header">
            <div>
                <div class="chart-card-title">Service Breakdown</div>
                <div class="chart-card-sub">Bookings per package</div>
            </div>
        </div>
        <canvas id="serviceDonut" height="160"></canvas>
        <div class="donut-legend">
            <?php
            $colors = ['#6b0f0f','#8b1a1a','#c9a84c','#e8c97a','#3b82f6','#22c55e'];
            foreach ($svcBreakdown as $i => $row): ?>
            <div class="legend-item">
                <div class="legend-dot" style="background:<?= $colors[$i%count($colors)] ?>"></div>
                <span class="legend-label"><?= htmlspecialchars($row['name']) ?></span>
                <span class="legend-val"><?= $row['cnt'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Bottom Row -->
<div class="bottom-row">
    <!-- Recent Bookings -->
    <div class="admin-table-card">
        <div class="table-card-header">
            <div class="table-card-title">Recent Bookings</div>
            <a href="<?= SITE_URL ?>/admin/bookings.php" class="view-all-link">View All →</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr><th>Ref</th><th>Customer</th><th>Service</th><th>Date</th><th>Status</th><th>Payment</th></tr>
            </thead>
            <tbody>
                <?php foreach($recentBookings as $b): ?>
                <tr>
                    <td><strong style="color:var(--maroon);font-size:.82rem"><?= htmlspecialchars($b['booking_ref']) ?></strong></td>
                    <td><?= htmlspecialchars($b['customer_name']) ?></td>
                    <td><?= htmlspecialchars($b['service_name']) ?></td>
                    <td><?= date('M d, Y',strtotime($b['event_date'])) ?></td>
                    <td><span class="status-badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                    <td><span class="status-badge status-<?= $b['payment_status']??'pending' ?>"><?= ucfirst($b['payment_status']??'Pending') ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Upcoming Events -->
    <div class="admin-table-card">
        <div class="table-card-header">
            <div class="table-card-title">Upcoming Events (30 Days)</div>
            <a href="<?= SITE_URL ?>/admin/events.php" class="view-all-link">View All →</a>
        </div>
        <?php if(empty($upcomingEvents)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-light)">
            <div style="font-size:2rem;margin-bottom:8px">🗓️</div>
            No upcoming events
        </div>
        <?php else: ?>
        <div style="padding:0 8px 8px">
            <?php foreach($upcomingEvents as $e): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:14px 12px;border-bottom:1px solid var(--border)">
                <div style="background:rgba(107,15,15,.08);border-radius:10px;padding:8px 12px;text-align:center;min-width:50px;flex-shrink:0">
                    <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;color:var(--maroon);letter-spacing:.08em"><?= date('M',strtotime($e['event_date'])) ?></div>
                    <div style="font-family:var(--font-display);font-size:1.3rem;font-weight:700;color:var(--maroon-deep);line-height:1"><?= date('d',strtotime($e['event_date'])) ?></div>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:600;font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($e['event_name']) ?></div>
                    <div style="font-size:.78rem;color:var(--text-light)"><?= htmlspecialchars($e['customer_name']) ?> · <?= htmlspecialchars($e['service_name']) ?></div>
                </div>
                <div style="font-size:.78rem;color:var(--text-light);flex-shrink:0"><?= date('h:i A',strtotime($e['start_time'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$extraScript = "<script>window.serviceChartData={labels:{$svcLabels},values:{$svcValues}};</script>";
require_once __DIR__ . '/includes/footer.php';
?>
