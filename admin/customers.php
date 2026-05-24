<?php
$pageTitle  = 'Customers';
$activePage = 'customers';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete') {
    $pdo->prepare("DELETE FROM users WHERE id=? AND role='customer'")->execute([(int)$_POST['user_id']]);
    $msg = 'Customer removed.';
}

$search = sanitize($_GET['q'] ?? '');
$params = ['customer'];
$where  = "WHERE u.role=?";
if ($search) {
    $where .= " AND (u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}

$stmt = $pdo->prepare(
    "SELECT u.*,
            COUNT(b.id) AS total_bookings,
            SUM(CASE WHEN b.status='completed' THEN 1 ELSE 0 END) AS completed_bookings,
            COALESCE(SUM(p.amount),0) AS total_spent
     FROM users u
     LEFT JOIN bookings b ON b.user_id=u.id
     LEFT JOIN payments p ON p.booking_id=b.id AND p.payment_status='paid'
     $where GROUP BY u.id ORDER BY u.created_at DESC"
);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<?php if($msg): ?><div class="alert alert-success" data-dismiss><?= $msg ?></div><?php endif; ?>

<div class="admin-page-header">
    <div>
        <div class="admin-page-title">Customer Management</div>
        <div class="admin-page-sub"><?= count($customers) ?> registered customers</div>
    </div>
</div>

<div class="filters-bar">
    <form method="GET" style="display:contents">
        <input type="text" class="filter-input" name="q"
               placeholder="&#128269; Search by username, name, or email…"
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn-save" style="padding:9px 20px">Search</button>
        <a href="<?= SITE_URL ?>/admin/customers.php" class="btn-cancel" style="padding:9px 20px">Reset</a>
    </form>
</div>

<div class="admin-table-card">
    <div style="overflow-x:auto">
        <table class="admin-table">
            <thead>
                <tr><th>Username</th><th>Name</th><th>Contact</th><th>Registered</th><th>Bookings</th><th>Spent</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if(empty($customers)): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-light)">No customers found.</td></tr>
                <?php endif; ?>
                <?php foreach($customers as $c): ?>
                <tr class="searchable-row">
                    <td>
                        <strong style="color:var(--maroon)">@<?= htmlspecialchars($c['username']) ?></strong>
                        <div style="font-size:.72rem;color:var(--text-light)">ID #<?= $c['id'] ?></div>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:34px;height:34px;border-radius:50%;background:var(--maroon);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0">
                                <?= strtoupper(substr($c['first_name'],0,1).substr($c['last_name'],0,1)) ?>
                            </div>
                            <div style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?></div>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:.85rem"><?= htmlspecialchars($c['email'] ?? '—') ?></div>
                        <?php if($c['phone']): ?><div style="font-size:.78rem;color:var(--text-light)"><?= htmlspecialchars($c['phone']) ?></div><?php endif; ?>
                    </td>
                    <td style="font-size:.85rem;color:var(--text-mid)"><?= date('M d, Y',strtotime($c['created_at'])) ?></td>
                    <td style="text-align:center;font-weight:700;color:var(--maroon)"><?= $c['total_bookings'] ?></td>
                    <td style="font-weight:600">₱<?= number_format($c['total_spent'],0) ?></td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="<?= SITE_URL ?>/admin/bookings.php?q=<?= urlencode($c['username']) ?>" class="action-btn view" title="View bookings">&#128203;</a>
                            <form method="POST" id="del-c-<?= $c['id'] ?>" style="display:inline">
                                <input type="hidden" name="action"  value="delete">
                                <input type="hidden" name="user_id" value="<?= $c['id'] ?>">
                                <button type="button" class="action-btn delete" title="Delete"
                                    onclick="confirmDelete('del-c-<?= $c['id'] ?>')">&#128465;</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
