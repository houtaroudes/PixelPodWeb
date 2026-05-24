<?php
$pageTitle  = 'Analytics';
$activePage = 'analytics';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

$revenueByMonth = $pdo->query(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COALESCE(SUM(amount),0) AS revenue
     FROM payments WHERE payment_status IN ('paid','partial') AND created_at>=DATE_SUB(NOW(),INTERVAL 12 MONTH)
     GROUP BY month ORDER BY month ASC"
)->fetchAll();

$bookingsByStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status")->fetchAll();

$revenueByService = $pdo->query(
    "SELECT s.name, COALESCE(SUM(p.amount),0) AS revenue, COUNT(b.id) AS bookings
     FROM services s LEFT JOIN bookings b ON b.service_id=s.id
     LEFT JOIN payments p ON p.booking_id=b.id AND p.payment_status='paid'
     GROUP BY s.id ORDER BY revenue DESC"
)->fetchAll();

$topCustomers = $pdo->query(
    "SELECT CONCAT(u.first_name,' ',u.last_name) AS name, u.email, COUNT(b.id) AS bookings, COALESCE(SUM(p.amount),0) AS spent
     FROM users u JOIN bookings b ON b.user_id=u.id
     LEFT JOIN payments p ON p.booking_id=b.id AND p.payment_status='paid'
     WHERE u.role='customer' GROUP BY u.id ORDER BY spent DESC LIMIT 5"
)->fetchAll();

$avgVal      = $pdo->query("SELECT COALESCE(AVG(amount),0) FROM payments WHERE payment_status='paid'")->fetchColumn();
$thisMonthRev = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='paid' AND MONTH(created_at)=MONTH(NOW())")->fetchColumn();
$totalDone   = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='completed'")->fetchColumn();

$monthLabels  = json_encode(array_map(fn($r) => date('M Y', strtotime($r['month'] . '-01')), $revenueByMonth));
$monthRevenue = json_encode(array_column($revenueByMonth, 'revenue'));
$statusLabels = json_encode(array_column($bookingsByStatus, 'status'));
$statusValues = json_encode(array_column($bookingsByStatus, 'cnt'));
$svcLabels    = json_encode(array_column($revenueByService, 'name'));
$svcRevenue   = json_encode(array_column($revenueByService, 'revenue'));
?>

<div class="admin-page-header">
    <div>
        <div class="admin-page-title">Analytics & Reports</div>
        <div class="admin-page-sub">Business performance overview</div>
    </div>
</div>

<!-- KPI Cards -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:24px">
    <div style="background:var(--card);border-radius:var(--r-lg);padding:24px;box-shadow:var(--shadow-sm);border:1px solid var(--border)">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-light);margin-bottom:8px">This Month's Revenue</div>
        <div style="font-family:var(--font-display);font-size:2rem;font-weight:700;color:var(--maroon)">₱<?= number_format($thisMonthRev, 0) ?></div>
    </div>
    <div style="background:var(--card);border-radius:var(--r-lg);padding:24px;box-shadow:var(--shadow-sm);border:1px solid var(--border)">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-light);margin-bottom:8px">Avg. Booking Value</div>
        <div style="font-family:var(--font-display);font-size:2rem;font-weight:700;color:var(--maroon)">₱<?= number_format($avgVal, 0) ?></div>
    </div>
    <div style="background:var(--card);border-radius:var(--r-lg);padding:24px;box-shadow:var(--shadow-sm);border:1px solid var(--border)">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-light);margin-bottom:8px">Completed Events</div>
        <div style="font-family:var(--font-display);font-size:2rem;font-weight:700;color:var(--maroon)"><?= $totalDone ?></div>
    </div>
</div>

<!-- Charts -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px">
    <div class="chart-card">
        <div class="chart-card-header">
            <div>
                <div class="chart-card-title">Monthly Revenue (12 Months)</div>
                <div class="chart-card-sub">Paid payments only</div>
            </div>
        </div>
        <canvas id="revenueChart" height="85"></canvas>
    </div>
    <div class="chart-card">
        <div class="chart-card-header">
            <div>
                <div class="chart-card-title">Booking Statuses</div>
                <div class="chart-card-sub">All time distribution</div>
            </div>
        </div>
        <canvas id="statusDonut" height="180"></canvas>
        <div class="donut-legend" style="margin-top:12px">
            <?php $sc = ['pending' => '#f59e0b', 'approved' => '#22c55e', 'rejected' => '#ef4444', 'cancelled' => '#9ca3af', 'completed' => '#3b82f6'];
            foreach ($bookingsByStatus as $row): ?>
                <div class="legend-item">
                    <div class="legend-dot" style="background:<?= $sc[$row['status']] ?? '#ccc' ?>"></div>
                    <span class="legend-label" style="text-transform:capitalize"><?= $row['status'] ?></span>
                    <span class="legend-val"><?= $row['cnt'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="chart-card" style="margin-bottom:24px">
    <div class="chart-card-header">
        <div>
            <div class="chart-card-title">Revenue by Service</div>
            <div class="chart-card-sub">Total paid per package</div>
        </div>
    </div>
    <canvas id="svcBar" height="45"></canvas>
</div>

<!-- Top Customers -->
<div class="admin-table-card">
    <div class="table-card-header">
        <div class="table-card-title">Top Customers by Revenue</div>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Bookings</th>
                <th>Total Spent</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topCustomers as $i => $c): ?>
                <tr>
                    <td><span style="background:var(--maroon);color:#fff;border-radius:50%;width:26px;height:26px;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700">#<?= $i + 1 ?></span></td>
                    <td style="font-weight:600"><?= htmlspecialchars($c['name']) ?></td>
                    <td style="font-size:.85rem;color:var(--text-mid)"><?= htmlspecialchars($c['email']) ?></td>
                    <td style="text-align:center"><?= $c['bookings'] ?></td>
                    <td style="font-weight:700;color:var(--maroon)">₱<?= number_format($c['spent'], 0) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$extraScript = <<<SCRIPT
<script>
(function(){
    const ctx=document.getElementById('revenueChart');
    if(!ctx)return;
    new Chart(ctx,{type:'line',data:{labels:{$monthLabels},datasets:[{label:'Revenue (₱)',data:{$monthRevenue},borderColor:'#6b0f0f',backgroundColor:'rgba(107,15,15,.08)',borderWidth:2.5,pointRadius:4,pointBackgroundColor:'#6b0f0f',tension:.4,fill:true}]},options:{responsive:true,plugins:{legend:{labels:{font:{family:'DM Sans',size:12},color:'#5a3a3a'}}},scales:{x:{grid:{color:'rgba(107,15,15,.05)'},ticks:{font:{family:'DM Sans',size:11},color:'#9a7575'}},y:{grid:{color:'rgba(107,15,15,.05)'},ticks:{font:{family:'DM Sans',size:11},color:'#9a7575',callback:v=>'₱'+v.toLocaleString()}}}}});
})();
(function(){
    const ctx=document.getElementById('statusDonut');
    if(!ctx)return;
    new Chart(ctx,{type:'doughnut',data:{labels:{$statusLabels},datasets:[{data:{$statusValues},backgroundColor:['#f59e0b','#22c55e','#ef4444','#9ca3af','#3b82f6'],borderWidth:2,borderColor:'#fff'}]},options:{responsive:true,cutout:'65%',plugins:{legend:{display:false}}}});
})();
(function(){
    const ctx=document.getElementById('svcBar');
    if(!ctx)return;
    new Chart(ctx,{type:'bar',data:{labels:{$svcLabels},datasets:[{label:'Revenue (₱)',data:{$svcRevenue},backgroundColor:['#6b0f0f','#8b1a1a','#c9a84c','#e8c97a','#3b82f6','#22c55e'],borderRadius:6,borderSkipped:false}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{font:{family:'DM Sans',size:11},color:'#9a7575'}},y:{grid:{color:'rgba(107,15,15,.05)'},ticks:{font:{family:'DM Sans',size:11},color:'#9a7575',callback:v=>'₱'+v.toLocaleString()}}}}});
})();
</script>
SCRIPT;
require_once __DIR__ . '/includes/footer.php';
?>