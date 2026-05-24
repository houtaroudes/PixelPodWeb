<?php
$pageTitle  = 'Bookings';
$activePage = 'bookings';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bid    = (int)($_POST['booking_id'] ?? 0);

    if ($action === 'update_status' && $bid) {
        $status = sanitize($_POST['status'] ?? '');
        $notes  = sanitize($_POST['admin_notes'] ?? '');
        $allowed = ['pending', 'approved', 'rejected', 'cancelled', 'completed'];
        if (in_array($status, $allowed)) {
            $pdo->prepare("UPDATE bookings SET status=?,admin_notes=? WHERE id=?")->execute([$status, $notes, $bid]);
            $msg = "Booking updated to <strong>$status</strong>.";
        }
    } elseif ($action === 'delete' && $bid) {
        $pdo->prepare("DELETE FROM bookings WHERE id=?")->execute([$bid]);
        $msg = 'Booking deleted.';
    }
}

$filterStatus = sanitize($_GET['status'] ?? '');
$filterDate   = sanitize($_GET['date'] ?? '');
$search       = sanitize($_GET['q'] ?? '');
$where = [];
$params = [];

if ($filterStatus) {
    $where[] = 'b.status=?';
    $params[] = $filterStatus;
}
if ($filterDate) {
    $where[] = 'b.event_date=?';
    $params[] = $filterDate;
}
if ($search) {
    $where[] = "(b.booking_ref LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR b.event_name LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}
$wc = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare(
    "SELECT b.*, s.name AS service_name, s.price AS service_price,
            CONCAT(u.first_name,' ',u.last_name) AS customer_name,
            u.email AS customer_email, u.phone AS customer_phone,
            p.payment_status, p.amount AS paid_amount
     FROM bookings b
     JOIN services s ON b.service_id=s.id
     JOIN users u ON b.user_id=u.id
     LEFT JOIN payments p ON p.booking_id=b.id
     $wc ORDER BY b.created_at DESC"
);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<?php if ($msg): ?><div class="alert alert-success" data-dismiss><?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error" data-dismiss><?= $err ?></div><?php endif; ?>

<div class="admin-page-header">
    <div>
        <div class="admin-page-title">Booking Management</div>
        <div class="admin-page-sub"><?= count($bookings) ?> records found</div>
    </div>
</div>

<!-- Filters -->
<div class="filters-bar">
    <form method="GET" style="display:contents">
        <input type="text" class="filter-input" name="q" placeholder=" Search ref, name, event…" value="<?= htmlspecialchars($search) ?>">
        <select class="filter-select" name="status">
            <option value="">All Statuses</option>
            <?php foreach (['pending', 'approved', 'rejected', 'cancelled', 'completed'] as $s): ?>
                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" class="filter-select" name="date" value="<?= htmlspecialchars($filterDate) ?>">
        <button type="submit" class="btn-save" style="padding:9px 20px">Filter</button>
        <a href="<?= SITE_URL ?>/admin/bookings.php" class="btn-cancel" style="padding:9px 20px">Reset</a>
    </form>
</div>

<!-- Table -->
<div class="admin-table-card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
        <input type="text" id="tableSearch" class="filter-input" placeholder="&#128269; Quick filter table…" style="max-width:300px">
    </div>
    <div style="overflow-x:auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Customer</th>
                    <th>Service</th>
                    <th>Event</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:40px;color:var(--text-light)">No bookings found.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($bookings as $b): ?>
                    <tr class="searchable-row">
                        <td><strong style="color:var(--maroon);font-size:.82rem"><?= htmlspecialchars($b['booking_ref']) ?></strong></td>
                        <td>
                            <div style="font-weight:600;font-size:.88rem"><?= htmlspecialchars($b['customer_name']) ?></div>
                            <div style="font-size:.75rem;color:var(--text-light)"><?= htmlspecialchars($b['customer_email']) ?></div>
                        </td>
                        <td style="font-size:.88rem"><?= htmlspecialchars($b['service_name']) ?></td>
                        <td>
                            <div style="font-size:.88rem;max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($b['event_name']) ?></div>
                            <?php if ($b['venue']): ?>
                                <div style="font-size:.75rem;color:var(--text-light)">&#128205; <?= htmlspecialchars(mb_strimwidth($b['venue'], 0, 28, '…')) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-size:.88rem;font-weight:600"><?= date('M d, Y', strtotime($b['event_date'])) ?></div>
                            <div style="font-size:.75rem;color:var(--text-light)"><?= date('h:i A', strtotime($b['start_time'])) ?> – <?= date('h:i A', strtotime($b['end_time'])) ?></div>
                        </td>
                        <td><span class="status-badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                        <td><span class="status-badge status-<?= $b['payment_status'] ?? 'pending' ?>"><?= ucfirst($b['payment_status'] ?? 'pending') ?></span></td>
                        <td style="font-weight:600;font-size:.88rem">₱<?= number_format($b['paid_amount'] ?? $b['service_price'], 0) ?></td>
                        <td>
                            <div style="display:flex;gap:5px;flex-wrap:wrap">
                                <button class="action-btn view" title="View" onclick="openViewModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)">👁</button>
                                <?php if ($b['status'] === 'pending'): ?>
                                    <button class="action-btn approve" title="Approve" onclick="updateBookingStatus(<?= $b['id'] ?>,'approved')">&#10004;</button>
                                    <button class="action-btn reject" title="Reject" onclick="updateBookingStatus(<?= $b['id'] ?>,'rejected')">&#128473;</button>
                                <?php endif; ?>
                                <button class="action-btn edit" title="Edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES) ?>)">&#9998;</button>
                                <form method="POST" id="del-<?= $b['id'] ?>" style="display:inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                    <button type="button" class="action-btn delete" title="Delete" onclick="confirmDelete('del-<?= $b['id'] ?>')">&#128465;</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Modal -->
<div class="modal-overlay" id="viewModal">
    <div class="modal-box" style="max-width:620px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <div class="modal-title" id="viewTitle">Booking Details</div>
            <button data-modal-close style="background:none;border:none;cursor:pointer;font-size:1.3rem">✕</button>
        </div>
        <div id="viewBody"></div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <div class="modal-title">Update Booking</div>
            <button data-modal-close style="background:none;border:none;cursor:pointer;font-size:1.3rem">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="booking_id" id="editId">
            <div class="form-group">
                <label>Booking Ref</label>
                <input type="text" id="editRef" disabled style="opacity:.6">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="editStatus">
                    <?php foreach (['pending', 'approved', 'rejected', 'cancelled', 'completed'] as $s): ?>
                        <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Admin Notes</label>
                <textarea name="admin_notes" id="editNotes" placeholder="Internal notes…"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-save">Save Changes</button>
                <button type="button" class="btn-cancel" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
function openViewModal(b) {
    document.getElementById('viewTitle').textContent = 'Booking: ' + b.booking_ref;
    document.getElementById('viewBody').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:.88rem">
            <div><strong>Customer</strong><br>${b.customer_name}</div>
            <div><strong>Email</strong><br>${b.customer_email}</div>
            <div><strong>Service</strong><br>${b.service_name}</div>
            <div><strong>Event</strong><br>${b.event_name}</div>
            <div><strong>Date</strong><br>${b.event_date}</div>
            <div><strong>Time</strong><br>${b.start_time} – ${b.end_time}</div>
            <div><strong>Venue</strong><br>${b.venue||'—'}</div>
            <div><strong>Guests</strong><br>${b.guest_count}</div>
            <div><strong>Status</strong><br><span class="status-badge status-${b.status}">${b.status}</span></div>
            <div><strong>Payment</strong><br><span class="status-badge status-${b.payment_status||'pending'}">${b.payment_status||'pending'}</span></div>
            ${b.special_requests?`<div style="grid-column:1/-1"><strong>Special Requests</strong><br>${b.special_requests}</div>`:''}
            ${b.admin_notes?`<div style="grid-column:1/-1"><strong>Admin Notes</strong><br>${b.admin_notes}</div>`:''}
        </div>`;
    document.getElementById('viewModal').classList.add('active');
}
function openEditModal(b) {
    document.getElementById('editId').value     = b.id;
    document.getElementById('editRef').value    = b.booking_ref;
    document.getElementById('editStatus').value = b.status;
    document.getElementById('editNotes').value  = b.admin_notes||'';
    document.getElementById('editModal').classList.add('active');
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>