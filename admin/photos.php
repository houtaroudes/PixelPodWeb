<?php
$pageTitle  = 'Photo Sessions';
$activePage = 'photos';
require_once __DIR__ . '/includes/header.php';
$pdo = getDB();

// Delete session
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='delete') {
    $id = intval($_POST['session_id']??0);
    // Get photos to delete files
    $s = $pdo->prepare("SELECT photos,qr_code FROM photo_sessions WHERE id=?");
    $s->execute([$id]);
    $row = $s->fetch();
    if ($row) {
        $files = json_decode($row['photos'],true) ?: [];
        foreach ($files as $f) { @unlink(__DIR__.'/../uploads/photos/'.$f); }
        if ($row['qr_code']) @unlink(__DIR__.'/../uploads/photos/'.$row['qr_code']);
        $pdo->prepare("DELETE FROM photo_sessions WHERE id=?")->execute([$id]);
    }
    $msg = '✓ Session deleted.';
}

// Fetch all sessions
$sessions = $pdo->query("
    SELECT ps.*, u.username, u.first_name, u.last_name
    FROM photo_sessions ps
    LEFT JOIN users u ON ps.user_id = u.id
    ORDER BY ps.created_at DESC
")->fetchAll();

$total  = count($sessions);
$today  = count(array_filter($sessions, fn($s)=>date('Y-m-d',strtotime($s['created_at']))=== date('Y-m-d')));
?>

<?php if (!empty($msg)): ?><div class="alert alert-success" data-dismiss><?= $msg ?></div><?php endif; ?>

<div class="admin-page-header">
    <div>
        <div class="admin-page-title">Photo Sessions</div>
        <div class="admin-page-sub"><?= $total ?> total sessions · <?= $today ?> today</div>
    </div>
    <a href="http://localhost/PixelPodWeb/public/photobooth/index.php" target="_blank" class="btn-save">
         Open Photobooth
    </a>
</div>

<div class="admin-table-card">
    <div style="overflow-x:auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Photos</th>
                    <th>QR</th>
                    <th>Layout</th>
                    <th>Filter</th>
                    <th>User</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($sessions)): ?>
                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-light)">No photo sessions yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($sessions as $s):
                $photos = json_decode($s['photos'],true) ?: [];
            ?>
            <tr>
                <td>
                    <strong style="font-family:monospace;color:var(--maroon);font-size:.85rem">
                        <?= htmlspecialchars($s['session_code']) ?>
                    </strong>
                </td>
                <td>
                    <div style="display:flex;gap:4px">
                        <?php foreach (array_slice($photos,0,2) as $f): ?>
                        <img src="http://localhost/PixelPodWeb/uploads/photos/<?= htmlspecialchars($f) ?>"
                             style="width:36px;height:36px;object-fit:cover;border-radius:4px;border:1px solid var(--border)"
                             onerror="this.style.display='none'">
                        <?php endforeach; ?>
                        <?php if (count($photos)>2): ?>
                        <div style="width:36px;height:36px;background:var(--panel);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:var(--text-mid)">
                            +<?= count($photos)-2 ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <?php if ($s['qr_code']): ?>
                    <img src="http://localhost/PixelPodWeb/uploads/photos/<?= htmlspecialchars($s['qr_code']) ?>"
                         style="width:40px;height:40px;border-radius:4px;border:1px solid var(--border)"
                         onerror="this.style.display='none'">
                    <?php else: ?>
                    <span style="color:var(--text-light);font-size:.78rem">—</span>
                    <?php endif; ?>
                </td>
                <td><span class="status-badge status-approved"><?= ucfirst($s['layout']) ?></span></td>
                <td style="font-size:.82rem;color:var(--text-mid)"><?= ucfirst(str_replace('_',' ',$s['filter_name'])) ?></td>
                <td style="font-size:.82rem">
                    <?php if ($s['username']): ?>
                        <strong>@<?= htmlspecialchars($s['username']) ?></strong>
                    <?php else: ?>
                        <span style="color:var(--text-light)">Guest</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:.82rem;color:var(--text-mid)"><?= date('M d, Y g:i A',strtotime($s['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="http://localhost/PixelPodWeb/public/photobooth/view.php?code=<?= urlencode($s['session_code']) ?>"
                           target="_blank" class="action-btn view" title="View photos">👁</a>
                        <form method="POST" id="del-ps-<?= $s['id'] ?>" style="display:inline">
                            <input type="hidden" name="action"     value="delete">
                            <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                            <button type="button" class="action-btn delete" title="Delete"
                                onclick="confirmDelete('del-ps-<?= $s['id'] ?>')">&#128465;</button>
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
