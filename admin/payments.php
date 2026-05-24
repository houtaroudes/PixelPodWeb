<?php
$pageTitle  = 'Payments';
$activePage = 'payments';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update') {
    $pid    = (int)$_POST['payment_id'];
    $status = sanitize($_POST['payment_status']);
    $method = sanitize($_POST['payment_method']);
    $ref    = sanitize($_POST['reference_number']);
    $notes  = sanitize($_POST['notes']);
    $paid_at= ($status==='paid'&&empty($_POST['paid_at']))?date('Y-m-d H:i:s'):sanitize($_POST['paid_at']);
    $pdo->prepare("UPDATE payments SET payment_status=?,payment_method=?,reference_number=?,notes=?,paid_at=? WHERE id=?")
        ->execute([$status,$method,$ref,$notes,$paid_at?:null,$pid]);
    $msg = 'Payment updated.';
}

$filterStatus = sanitize($_GET['status'] ?? '');
$where  = $filterStatus ? "WHERE p.payment_status=?" : '';
$params = $filterStatus ? [$filterStatus] : [];

$payments = $pdo->prepare(
    "SELECT p.*, b.booking_ref, b.event_name, b.event_date,
            s.name AS service_name,
            CONCAT(u.first_name,' ',u.last_name) AS customer_name, u.email AS customer_email
     FROM payments p
     JOIN bookings b ON p.booking_id=b.id
     JOIN services s ON b.service_id=s.id
     JOIN users u ON b.user_id=u.id
     $where ORDER BY p.created_at DESC"
);
$payments->execute($params);
$payments = $payments->fetchAll();

$totalPaid    = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='paid'")->fetchColumn();
$totalPartial = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='partial'")->fetchColumn();
$totalPending = $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status='pending'")->fetchColumn();
?>

<?php if($msg): ?><div class="alert alert-success" data-dismiss><?= $msg ?></div><?php endif; ?>

<div class="admin-page-header">
    <div>
        <div class="admin-page-title">Payment Tracking</div>
        <div class="admin-page-sub"><?= count($payments) ?> payment records</div>
    </div>
</div>

<!-- Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:24px">
    <div style="background:var(--card);border-radius:var(--r-lg);padding:20px;box-shadow:var(--shadow-sm);border:1px solid var(--border);border-left:4px solid #22c55e">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-light);margin-bottom:6px">Total Paid</div>
        <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:#166534">₱<?= number_format($totalPaid,0) ?></div>
    </div>
    <div style="background:var(--card);border-radius:var(--r-lg);padding:20px;box-shadow:var(--shadow-sm);border:1px solid var(--border);border-left:4px solid #f59e0b">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-light);margin-bottom:6px">Partial Payments</div>
        <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:#92400e">₱<?= number_format($totalPartial,0) ?></div>
    </div>
    <div style="background:var(--card);border-radius:var(--r-lg);padding:20px;box-shadow:var(--shadow-sm);border:1px solid var(--border);border-left:4px solid #ef4444">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-light);margin-bottom:6px">Pending Payments</div>
        <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:#991b1b"><?= $totalPending ?></div>
    </div>
</div>

<div class="filters-bar">
    <form method="GET" style="display:contents">
        <select class="filter-select" name="status">
            <option value="">All Statuses</option>
            <?php foreach(['pending','partial','paid','refunded'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-save" style="padding:9px 20px">Filter</button>
        <a href="<?= SITE_URL ?>/admin/payments.php" class="btn-cancel" style="padding:9px 20px">Reset</a>
    </form>
</div>

<div class="admin-table-card">
    <div style="overflow-x:auto">
        <table class="admin-table">
            <thead>
                <tr><th>Booking Ref</th><th>Customer</th><th>Service</th><th>Event Date</th><th>Amount</th><th>Method</th><th>Status</th><th>Ref #</th><th>Paid At</th><th>Edit</th></tr>
            </thead>
            <tbody>
                <?php if(empty($payments)): ?>
                <tr><td colspan="10" style="text-align:center;padding:40px;color:var(--text-light)">No records.</td></tr>
                <?php endif; ?>
                <?php foreach($payments as $p): ?>
                <tr class="searchable-row">
                    <td><strong style="color:var(--maroon);font-size:.82rem"><?= htmlspecialchars($p['booking_ref']) ?></strong></td>
                    <td>
                        <div style="font-size:.88rem;font-weight:600"><?= htmlspecialchars($p['customer_name']) ?></div>
                        <div style="font-size:.75rem;color:var(--text-light)"><?= htmlspecialchars($p['customer_email']) ?></div>
                    </td>
                    <td style="font-size:.85rem"><?= htmlspecialchars($p['service_name']) ?></td>
                    <td style="font-size:.85rem"><?= date('M d, Y',strtotime($p['event_date'])) ?></td>
                    <td style="font-weight:700;color:var(--maroon)">₱<?= number_format($p['amount'],2) ?></td>
                    <td style="font-size:.82rem"><?php $methods=['gcash'=>'GCash','maya'=>'Maya','cod'=>'COD']; echo $methods[$p['payment_method']] ?? ucfirst($p['payment_method']??'—'); ?></td>
                    <td><span class="status-badge status-<?= $p['payment_status'] ?>"><?= ucfirst($p['payment_status']) ?></span></td>
                    <td style="font-size:.82rem;color:var(--text-mid)"><?= htmlspecialchars($p['reference_number']??'—') ?></td>
                    <td style="font-size:.82rem;color:var(--text-mid)"><?= $p['paid_at']?date('M d, Y',strtotime($p['paid_at'])):'—' ?></td>
                    <td><button class="action-btn edit" title="Edit" onclick="openEditPay(<?= htmlspecialchars(json_encode($p),ENT_QUOTES) ?>)">&#x270E;</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal-overlay" id="editPayModal">
    <div class="modal-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <div class="modal-title">Update Payment</div>
            <button data-modal-close style="background:none;border:none;cursor:pointer;font-size:1.3rem">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="payment_id" id="epId">
            <div class="form-group"><label>Booking</label><input type="text" id="epRef" disabled style="opacity:.6"></div>
            <div class="form-grid-2">
                <div class="form-group"><label>Status</label>
                    <select name="payment_status" id="epStatus">
                        <?php foreach(['pending','partial','paid','refunded'] as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Method</label>
                    <select name="payment_method" id="epMethod">
                        <option value="gcash">GCash</option>
                        <option value="maya">Maya</option>
                        <option value="cod">Cash on Delivery (COD)</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Reference Number</label><input type="text" name="reference_number" id="epRef2" placeholder="e.g. GC-20250401-9912"></div>
            <div class="form-group"><label>Paid At</label><input type="datetime-local" name="paid_at" id="epPaidAt"></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" id="epNotes" placeholder="Internal notes…"></textarea></div>
            <div class="form-actions"><button type="submit" class="btn-save">Save</button><button type="button" class="btn-cancel" data-modal-close>Cancel</button></div>
        </form>
    </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
function openEditPay(p) {
    document.getElementById('epId').value     = p.id;
    document.getElementById('epRef').value    = p.booking_ref;
    document.getElementById('epStatus').value = p.payment_status;
    document.getElementById('epMethod').value = p.payment_method||'cash';
    document.getElementById('epRef2').value   = p.reference_number||'';
    document.getElementById('epNotes').value  = p.notes||'';
    document.getElementById('epPaidAt').value = p.paid_at?p.paid_at.replace(' ','T').slice(0,16):'';
    document.getElementById('editPayModal').classList.add('active');
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
