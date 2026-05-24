<?php
$pageTitle  = 'Inquiries';
$activePage = 'inquiries';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    $iid    = (int)($_POST['inquiry_id'] ?? 0);
    if ($action==='mark_read'&&$iid)     { $pdo->prepare("UPDATE inquiries SET is_read=1 WHERE id=?")->execute([$iid]); $msg='Marked as read.'; }
    elseif ($action==='delete'&&$iid)    { $pdo->prepare("DELETE FROM inquiries WHERE id=?")->execute([$iid]); $msg='Inquiry deleted.'; }
    elseif ($action==='mark_all_read')   { $pdo->exec("UPDATE inquiries SET is_read=1"); $msg='All marked as read.'; }
}

$filterRead = $_GET['read'] ?? '';
$where = '';
if ($filterRead==='0') $where='WHERE is_read=0';
if ($filterRead==='1') $where='WHERE is_read=1';

$inquiries  = $pdo->query("SELECT * FROM inquiries $where ORDER BY created_at DESC")->fetchAll();
$unreadCount= $pdo->query("SELECT COUNT(*) FROM inquiries WHERE is_read=0")->fetchColumn();
?>

<?php if($msg): ?><div class="alert alert-success" data-dismiss><?= $msg ?></div><?php endif; ?>

<div class="admin-page-header">
    <div>
        <div class="admin-page-title">Contact Inquiries</div>
        <div class="admin-page-sub"><?= count($inquiries) ?> total · <?= $unreadCount ?> unread</div>
    </div>
    <form method="POST"><input type="hidden" name="action" value="mark_all_read">
        <button type="submit" class="btn-cancel" style="padding:9px 20px">&#9989; Mark All Read</button>
    </form>
</div>

<!-- Tabs -->
<div style="display:flex;gap:4px;margin-bottom:20px;background:var(--card);padding:6px;border-radius:var(--r-xl);width:fit-content;box-shadow:var(--shadow-sm)">
    <?php foreach([''=> 'All','0'=>'Unread','1'=>'Read'] as $val=>$label): ?>
    <a href="?read=<?= $val ?>" style="padding:8px 20px;border-radius:var(--r-xl);font-size:.85rem;font-weight:600;transition:all .2s;text-decoration:none;<?= $filterRead===(string)$val?'background:var(--maroon);color:#fff;box-shadow:0 2px 10px rgba(107,15,15,.3)':'color:var(--text-mid)' ?>">
        <?= $label ?>
        <?php if($val==='0'&&$unreadCount>0): ?><span style="background:rgba(255,255,255,.25);border-radius:100px;padding:1px 7px;font-size:.72rem;margin-left:4px"><?= $unreadCount ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if(empty($inquiries)): ?>
<div style="text-align:center;padding:80px;background:var(--card);border-radius:var(--r-lg);box-shadow:var(--shadow-sm);border:1px solid var(--border)">
    <div style="font-size:3rem;margin-bottom:16px">&#128172;</div>
    <h3 style="font-family:var(--font-display);color:var(--text-dark)">No inquiries</h3>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:12px">
    <?php foreach($inquiries as $inq): ?>
    <div style="background:var(--card);border-radius:var(--r-lg);padding:22px 24px;box-shadow:var(--shadow-sm);border:1px solid var(--border);<?= !$inq['is_read']?'border-left:4px solid var(--maroon)':'' ?>;position:relative">
        <?php if(!$inq['is_read']): ?>
        <span style="position:absolute;top:16px;right:16px;background:var(--maroon);color:#fff;font-size:.68rem;font-weight:700;padding:3px 10px;border-radius:100px;text-transform:uppercase;letter-spacing:.08em">New</span>
        <?php endif; ?>
        <div style="display:flex;gap:14px;align-items:flex-start">
            <div style="width:42px;height:42px;border-radius:50%;background:<?= !$inq['is_read']?'var(--maroon)':'#e5e7eb' ?>;color:<?= !$inq['is_read']?'#fff':'var(--text-mid)' ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;flex-shrink:0">
                <?= strtoupper(substr($inq['full_name'],0,1)) ?>
            </div>
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:6px">
                    <span style="font-weight:700;font-size:.95rem"><?= htmlspecialchars($inq['full_name']) ?></span>
                    <a href="mailto:<?= htmlspecialchars($inq['email']) ?>" style="font-size:.82rem;color:var(--maroon)"><?= htmlspecialchars($inq['email']) ?></a>
                    <?php if($inq['phone']): ?><span style="font-size:.82rem;color:var(--text-light)">&#128222; <?= htmlspecialchars($inq['phone']) ?></span><?php endif; ?>
                    <span style="font-size:.75rem;color:var(--text-light);margin-left:auto"><?= date('M d, Y g:i A',strtotime($inq['created_at'])) ?></span>
                </div>
                <?php if($inq['subject']): ?><div style="font-size:.85rem;font-weight:600;color:var(--maroon-deep);margin-bottom:8px">Re: <?= htmlspecialchars($inq['subject']) ?></div><?php endif; ?>
                <p style="font-size:.9rem;color:var(--text-mid);line-height:1.6;margin-bottom:14px"><?= nl2br(htmlspecialchars($inq['message'])) ?></p>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <a href="mailto:<?= htmlspecialchars($inq['email']) ?>?subject=Re: <?= urlencode($inq['subject']??'Your Inquiry') ?>" class="btn-save" style="padding:7px 16px;font-size:.8rem;display:inline-block">📧 Reply</a>
                    <?php if(!$inq['is_read']): ?>
                    <form method="POST" style="display:inline"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="inquiry_id" value="<?= $inq['id'] ?>">
                        <button type="submit" class="btn-cancel" style="padding:7px 14px;font-size:.8rem">✅ Mark Read</button></form>
                    <?php endif; ?>
                    <form method="POST" id="del-inq-<?= $inq['id'] ?>" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="inquiry_id" value="<?= $inq['id'] ?>">
                        <button type="button" class="btn-danger" style="padding:7px 14px;font-size:.8rem" onclick="confirmDelete('del-inq-<?= $inq['id'] ?>')">🗑 Delete</button></form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
