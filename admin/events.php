<?php
$pageTitle  = 'Events';
$activePage = 'events';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$filterMonth = sanitize($_GET['month'] ?? date('Y-m'));
[$year,$month] = explode('-', $filterMonth.'-01');
$startDate = "$year-$month-01";
$endDate   = date('Y-m-t', strtotime($startDate));

$events = $pdo->prepare(
    "SELECT b.*, s.name AS service_name, s.price,
            CONCAT(u.first_name,' ',u.last_name) AS customer_name, u.phone AS customer_phone,
            p.payment_status
     FROM bookings b
     JOIN services s ON b.service_id=s.id
     JOIN users u ON b.user_id=u.id
     LEFT JOIN payments p ON p.booking_id=b.id
     WHERE b.event_date BETWEEN ? AND ? AND b.status NOT IN ('rejected','cancelled')
     ORDER BY b.event_date ASC, b.start_time ASC"
);
$events->execute([$startDate,$endDate]);
$events = $events->fetchAll();

$grouped = [];
foreach ($events as $e) $grouped[$e['event_date']][] = $e;

$prevM = date('Y-m',strtotime($startDate.' -1 month'));
$nextM = date('Y-m',strtotime($startDate.' +1 month'));
?>

<div class="admin-page-header">
    <div>
        <div class="admin-page-title">Event Schedule</div>
        <div class="admin-page-sub"><?= count($events) ?> events in <?= date('F Y',strtotime($startDate)) ?></div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <form method="GET" style="display:flex;gap:8px">
            <input type="month" name="month" value="<?= htmlspecialchars($filterMonth) ?>" class="filter-select" style="padding:9px 14px">
            <button type="submit" class="btn-save" style="padding:9px 20px">View</button>
        </form>
        <a href="?month=<?= $prevM ?>" class="btn-cancel" style="padding:9px 16px">← Prev</a>
        <a href="?month=<?= $nextM ?>" class="btn-cancel" style="padding:9px 16px">Next →</a>
    </div>
</div>

<?php if(empty($grouped)): ?>
<div style="text-align:center;padding:80px;background:var(--card);border-radius:var(--r-lg);box-shadow:var(--shadow-sm);border:1px solid var(--border)">
    <div style="font-size:3rem;margin-bottom:16px">&#128197;</div>
    <h3 style="font-family:var(--font-display);color:var(--text-dark);margin-bottom:8px">No Events This Month</h3>
    <p style="color:var(--text-light)">No approved bookings for <?= date('F Y',strtotime($startDate)) ?>.</p>
</div>
<?php else: ?>
<?php foreach($grouped as $date => $dayEvents): ?>
<div style="margin-bottom:28px">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px">
        <div style="background:var(--maroon);color:#fff;border-radius:var(--r-md);padding:10px 16px;text-align:center;min-width:68px;box-shadow:0 4px 16px rgba(107,15,15,.3)">
            <div style="font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;opacity:.8"><?= date('D',strtotime($date)) ?></div>
            <div style="font-family:var(--font-display);font-size:1.7rem;font-weight:900;line-height:1"><?= date('d',strtotime($date)) ?></div>
            <div style="font-size:.6rem;opacity:.8"><?= date('M Y',strtotime($date)) ?></div>
        </div>
        <div style="flex:1;height:1px;background:var(--border)"></div>
        <span style="font-size:.8rem;color:var(--text-light)"><?= count($dayEvents) ?> event<?= count($dayEvents)>1?'s':'' ?></span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:12px;margin-left:84px">
        <?php foreach($dayEvents as $e):
        $bc = match($e['status']){'approved'=>'#22c55e','completed'=>'#3b82f6',default=>'#f59e0b'};
        ?>
        <div style="background:var(--card);border-radius:var(--r-md);padding:16px;box-shadow:var(--shadow-sm);border:1px solid var(--border);border-left:4px solid <?= $bc ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
                <div>
                    <div style="font-weight:700;font-size:.92rem"><?= htmlspecialchars($e['event_name']) ?></div>
                    <div style="font-size:.75rem;color:var(--text-light);margin-top:2px"><?= htmlspecialchars($e['booking_ref']) ?></div>
                </div>
                <span class="status-badge status-<?= $e['status'] ?>"><?= ucfirst($e['status']) ?></span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:.82rem;color:var(--text-mid)">
                <div>&#128338; <?= date('h:i A',strtotime($e['start_time'])) ?> – <?= date('h:i A',strtotime($e['end_time'])) ?></div>
                <div>&#128100; <?= htmlspecialchars($e['customer_name']) ?></div>
                <div>&#128230; <?= htmlspecialchars($e['service_name']) ?></div>
                <div>&#128179; <span class="status-badge status-<?= $e['payment_status']??'pending' ?>"><?= ucfirst($e['payment_status']??'pending') ?></span></div>
                <?php if($e['venue']): ?><div style="grid-column:1/-1">&#128205; <?= htmlspecialchars($e['venue']) ?></div><?php endif; ?>
                <div style="font-weight:700;color:var(--maroon)">₱<?= number_format($e['price'],0) ?></div>
                <?php if($e['customer_phone']): ?><div>&#9990; <?= htmlspecialchars($e['customer_phone']) ?></div><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
