<?php
$pageTitle  = 'Services';
$activePage = 'services';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/image_helper.php';

$pdo = getDB();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $imgResult = handleServiceImage($_FILES['image'] ?? [], $_POST['image_url'] ?? '');
        if (!$imgResult['success']) {
            $err = $imgResult['message'];
        } else {
            $pdo->prepare("INSERT INTO services (name,description,price,duration_hours,max_guests,image,image_type,is_active) VALUES (?,?,?,?,?,?,?,?)")
                ->execute([sanitize($_POST['name']),sanitize($_POST['description']),(float)$_POST['price'],(int)$_POST['duration_hours'],$_POST['max_guests']!==''?(int)$_POST['max_guests']:null,$imgResult['image'],$imgResult['image_type'],isset($_POST['is_active'])?1:0]);
            $msg = 'Package <strong>'.sanitize($_POST['name']).'</strong> added!';
        }

    } elseif ($action === 'edit' && !empty($_POST['id'])) {
        $sid = (int)$_POST['id'];
        $cur = $pdo->prepare("SELECT image,image_type FROM services WHERE id=?"); $cur->execute([$sid]); $curRow=$cur->fetch();
        $imgResult = handleServiceImage($_FILES['image'] ?? [], $_POST['image_url'] ?? '', $curRow['image'] ?? null);
        if (!$imgResult['success']) {
            $err = $imgResult['message'];
        } else {
            $newImg  = $imgResult['image'];
            $newType = $imgResult['image_type'] ?? $curRow['image_type'];
            $pdo->prepare("UPDATE services SET name=?,description=?,price=?,duration_hours=?,max_guests=?,image=?,image_type=?,is_active=? WHERE id=?")
                ->execute([sanitize($_POST['name']),sanitize($_POST['description']),(float)$_POST['price'],(int)$_POST['duration_hours'],$_POST['max_guests']!==''?(int)$_POST['max_guests']:null,$newImg,$newType,isset($_POST['is_active'])?1:0,$sid]);
            $msg = 'Package updated!';
        }

    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $sid = (int)$_POST['id'];
        $chk = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE service_id=? AND status IN ('pending','approved')"); $chk->execute([$sid]);
        if ($chk->fetchColumn() > 0) {
            $err = 'Cannot delete: this package has active bookings.';
        } else {
            $img = $pdo->prepare("SELECT image,image_type FROM services WHERE id=?"); $img->execute([$sid]); $ir=$img->fetch();
            if ($ir && $ir['image_type']==='upload' && !empty($ir['image'])) { $p=__DIR__.'/../../'.$ir['image']; if(file_exists($p)) @unlink($p); }
            $pdo->prepare("DELETE FROM services WHERE id=?")->execute([$sid]);
            $msg = 'Package deleted.';
        }

    } elseif ($action === 'remove_image' && !empty($_POST['id'])) {
        $sid = (int)$_POST['id'];
        $img = $pdo->prepare("SELECT image,image_type FROM services WHERE id=?"); $img->execute([$sid]); $ir=$img->fetch();
        if ($ir && $ir['image_type']==='upload' && !empty($ir['image'])) { $p=__DIR__.'/../../'.$ir['image']; if(file_exists($p)) @unlink($p); }
        $pdo->prepare("UPDATE services SET image=NULL,image_type=NULL WHERE id=?")->execute([$sid]);
        $msg = 'Thumbnail removed.';

    } elseif ($action === 'toggle' && !empty($_POST['id'])) {
        $pdo->prepare("UPDATE services SET is_active=NOT is_active WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = 'Visibility updated.';
    }
}

$services = $pdo->query("SELECT s.*,(SELECT COUNT(*) FROM bookings b WHERE b.service_id=s.id) AS booking_count FROM services s ORDER BY s.price ASC")->fetchAll();
$grads = ['135deg,#6b0f0f,#8b1a1a','135deg,#4a0808,#c9a84c','135deg,#8b1a1a,#c94f4f','135deg,#c9a84c,#e8c97a','135deg,#4a0808,#8b1a1a','135deg,#6b0f0f,#c9a84c'];
?>

<?php if($msg): ?><div class="alert alert-success" data-dismiss><?= $msg ?></div><?php endif; ?>
<?php if($err): ?><div class="alert alert-error" data-dismiss><?= $err ?></div><?php endif; ?>

<div class="admin-page-header">
    <div>
        <div class="admin-page-title">Services & Packages</div>
        <div class="admin-page-sub"><?= count($services) ?> packages configured</div>
    </div>
    <button class="btn-save" data-modal-open="addModal">+ Add New Package</button>
</div>

<!-- Services Grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:24px">
<?php foreach($services as $i => $s):
    $imgSrc = getServiceImageSrc($s['image'], $s['image_type']);
?>
<div style="background:var(--card);border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--shadow-sm);border:1px solid var(--border);transition:var(--ease)" onmouseenter="this.style.boxShadow='var(--shadow-md)'" onmouseleave="this.style.boxShadow='var(--shadow-sm)'">

    <!-- Thumbnail -->
    <div style="height:200px;position:relative;overflow:hidden;background:linear-gradient(<?= $grads[$i%count($grads)] ?>)">
        <?php if($imgSrc): ?>
        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($s['name']) ?>"
             style="width:100%;height:100%;object-fit:cover"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;flex-direction:column;gap:8px;color:rgba(255,255,255,.8)">
            <span style="font-size:2rem"></span><span style="font-size:.78rem">Image unavailable</span>
        </div>
        <?php else: ?>
        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px;color:rgba(255,255,255,.8)">
            <span style="font-size:3rem"></span><span style="font-size:.82rem;font-weight:500">No thumbnail yet</span>
        </div>
        <?php endif; ?>

        <!-- Badges -->
        <div style="position:absolute;top:12px;left:12px;display:flex;gap:6px">
            <?php if(!$s['is_active']): ?><span style="background:rgba(0,0,0,.65);color:#fff;font-size:.68rem;font-weight:700;padding:4px 10px;border-radius:100px">HIDDEN</span><?php endif; ?>
            <span style="background:rgba(0,0,0,.5);color:#fff;font-size:.68rem;font-weight:700;padding:4px 10px;border-radius:100px"><?= $s['booking_count'] ?> bookings</span>
        </div>

        <!-- Change photo btn -->
        <button onclick="openEditImg(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)"
            style="position:absolute;bottom:12px;right:12px;background:rgba(255,255,255,.92);color:var(--maroon);border:none;border-radius:8px;padding:7px 14px;font-size:.78rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px">
            Change Photo
        </button>
    </div>

    <!-- Body -->
    <div style="padding:20px 22px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
            <h3 style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:var(--text-dark)"><?= htmlspecialchars($s['name']) ?></h3>
            <span style="font-family:var(--font-display);font-size:1.25rem;font-weight:700;color:var(--maroon);white-space:nowrap;margin-left:8px">₱<?= number_format($s['price'],0) ?></span>
        </div>
        <p style="font-size:.84rem;color:var(--text-mid);margin-bottom:14px;line-height:1.6"><?= htmlspecialchars(mb_strimwidth($s['description'],0,100,'…')) ?></p>
        <div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
            <span style="font-size:.75rem;background:rgba(107,15,15,.07);color:var(--maroon);padding:3px 10px;border-radius:100px">⏱ <?= $s['duration_hours'] ?>h</span>
            <?php if($s['max_guests']): ?><span style="font-size:.75rem;background:rgba(59,130,246,.07);color:#3b82f6;padding:3px 10px;border-radius:100px">👥 <?= $s['max_guests'] ?></span><?php endif; ?>
            <span style="font-size:.75rem;background:<?= $s['is_active']?'rgba(34,197,94,.07)':'rgba(239,68,68,.07)' ?>;color:<?= $s['is_active']?'#166534':'#991b1b' ?>;padding:3px 10px;border-radius:100px"><?= $s['is_active']?'&#10003; Active':'&#10006; Hidden' ?></span>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn-save" style="padding:8px 16px;font-size:.82rem;flex:1"
                onclick="openEditModal(<?= htmlspecialchars(json_encode($s),ENT_QUOTES) ?>)">Edit Details</button>
            <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn-cancel" style="padding:8px 14px;font-size:.82rem" title="<?= $s['is_active']?'Hide from website':'Show on website' ?>"><?= $s['is_active']?'&#x274C;':'&#128065;' ?></button>
            </form>
            <form method="POST" id="del-svc-<?= $s['id'] ?>" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button type="button" class="btn-danger" style="padding:8px 14px;font-size:.82rem"
                    onclick="confirmDelete('del-svc-<?= $s['id'] ?>')">&#128465;</button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php if(empty($services)): ?>
<div style="grid-column:1/-1;text-align:center;padding:80px;background:var(--card);border-radius:var(--r-lg);box-shadow:var(--shadow-sm);border:1px solid var(--border)">
    <div style="font-size:3rem;margin-bottom:16px"></div>
    <h3 style="font-family:var(--font-display);color:var(--text-dark);margin-bottom:12px">No packages yet</h3>
    <button class="btn-save" data-modal-open="addModal">+ Add First Package</button>
</div>
<?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box" style="max-width:620px;max-height:90vh;overflow-y:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;position:sticky;top:0;background:var(--card);padding-bottom:14px;border-bottom:1px solid var(--border)">
            <div class="modal-title">&#43; Add New Package</div>
            <button data-modal-close style="background:none;border:none;cursor:pointer;font-size:1.3rem">✕</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="form-group"><label>Package Name *</label><input type="text" name="name" required placeholder="e.g. Classic Booth"></div>
            <div class="form-group"><label>Description</label><textarea name="description" placeholder="What's included…" style="min-height:80px"></textarea></div>
            <div class="form-grid-3">
                <div class="form-group"><label>Price (₱) *</label><input type="number" name="price" required min="0" step="0.01" placeholder="5500"></div>
                <div class="form-group"><label>Duration (hrs) *</label><input type="number" name="duration_hours" required min="1" max="12" value="2"></div>
                <div class="form-group"><label>Max Guests</label><input type="number" name="max_guests" min="1" placeholder="100"></div>
            </div>
            <?php include __DIR__ . '/includes/image_fields.php'; // reusable image tab partial ?>
            <?= renderImageTabs('add') ?>
            <div class="form-group" style="display:flex;align-items:center;gap:10px;margin-top:4px">
                <input type="checkbox" name="is_active" id="addActive" checked style="width:auto">
                <label for="addActive" style="margin:0;cursor:pointer;font-size:.88rem">Active — show on public website</label>
            </div>
            <div class="form-actions"><button type="submit" class="btn-save">Add Package</button><button type="button" class="btn-cancel" data-modal-close>Cancel</button></div>
        </form>
    </div>
</div>

<!-- Edit Details Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box" style="max-width:560px;max-height:90vh;overflow-y:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;position:sticky;top:0;background:var(--card);padding-bottom:14px;border-bottom:1px solid var(--border)">
            <div class="modal-title">Edit Package Details</div>
            <button data-modal-close style="background:none;border:none;cursor:pointer;font-size:1.3rem">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="form-group"><label>Package Name *</label><input type="text" name="name" id="editName" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" id="editDesc" style="min-height:80px"></textarea></div>
            <div class="form-grid-3">
                <div class="form-group"><label>Price (₱) *</label><input type="number" name="price" id="editPrice" required min="0" step="0.01"></div>
                <div class="form-group"><label>Duration (hrs) *</label><input type="number" name="duration_hours" id="editDur" required min="1" max="12"></div>
                <div class="form-group"><label>Max Guests</label><input type="number" name="max_guests" id="editGuests" min="1"></div>
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:10px">
                <input type="checkbox" name="is_active" id="editActive" style="width:auto">
                <label for="editActive" style="margin:0;cursor:pointer;font-size:.88rem">Active — show on public website</label>
            </div>
            <div class="form-actions"><button type="submit" class="btn-save">Save Changes</button><button type="button" class="btn-cancel" data-modal-close>Cancel</button></div>
        </form>
    </div>
</div>

<!-- Change Photo Modal -->
<div class="modal-overlay" id="editImgModal">
    <div class="modal-box" style="max-width:500px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <div class="modal-title">Change Thumbnail</div>
            <button data-modal-close style="background:none;border:none;cursor:pointer;font-size:1.3rem">✕</button>
        </div>
        <div id="editImgLabel" style="font-size:.85rem;color:var(--text-mid);margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border)"></div>

        <!-- Current image preview -->
        <div id="currentImgWrap" style="margin-bottom:16px;display:none">
            <div style="font-size:.78rem;font-weight:600;color:var(--text-light);margin-bottom:6px;text-transform:uppercase;letter-spacing:.06em">Current Thumbnail</div>
            <img id="currentImgPreview" style="width:100%;max-height:150px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editImgId">
            <input type="hidden" name="name"           id="eIN">
            <input type="hidden" name="description"    id="eID">
            <input type="hidden" name="price"          id="eIP">
            <input type="hidden" name="duration_hours" id="eDH">
            <input type="hidden" name="max_guests"     id="eMG">
            <input type="hidden" name="is_active"      id="eIA">

            <!-- Upload / URL tabs -->
            <div style="display:flex;border-radius:8px;overflow:hidden;border:1px solid var(--border);margin-bottom:14px">
                <button type="button" id="tab-up" onclick="switchImgTab('up')"
                    style="flex:1;padding:9px;font-size:.82rem;font-weight:600;border:none;cursor:pointer;background:var(--maroon);color:#fff;transition:.2s">Upload File</button>
                <button type="button" id="tab-url" onclick="switchImgTab('url')"
                    style="flex:1;padding:9px;font-size:.82rem;font-weight:600;border:none;cursor:pointer;background:var(--panel);color:var(--text-mid);transition:.2s">Paste URL</button>
            </div>

            <div id="pane-up">
                <input type="file" name="image" accept="image/*" onchange="previewFile(this,'fp-edit')"
                    style="width:100%;padding:10px;border:1.5px solid var(--border);border-radius:8px;font-size:.85rem;background:var(--panel);cursor:pointer">
                <p style="font-size:.75rem;color:var(--text-light);margin-top:5px">JPG, PNG, WebP, GIF — max 5MB</p>
                <div id="fp-edit" style="display:none;margin-top:10px"><img style="width:100%;max-height:160px;object-fit:cover;border-radius:8px;border:1px solid var(--border)"></div>
            </div>
            <div id="pane-url" style="display:none">
                <input type="text" name="image_url" placeholder="https://example.com/photo.jpg" oninput="previewFromUrl(this,'up-edit')"
                    style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:.88rem;background:var(--panel);outline:none;font-family:inherit">
                <p style="font-size:.75rem;color:var(--text-light);margin-top:5px">Paste a direct link to any online image</p>
                <div id="up-edit" style="display:none;margin-top:10px"><img style="width:100%;max-height:160px;object-fit:cover;border-radius:8px;border:1px solid var(--border)"></div>
            </div>

            <div class="form-actions" style="margin-top:18px">
                <button type="submit" class="btn-save">Save Photo</button>
                <button type="button" class="btn-cancel" data-modal-close>Cancel</button>
            </div>
        </form>

        <form method="POST" id="rm-img-form" style="margin-top:10px">
            <input type="hidden" name="action" value="remove_image">
            <input type="hidden" name="id" id="rmImgId">
            <button type="button" style="width:100%;padding:10px;background:#fef2f2;color:#ef4444;border:1px solid rgba(239,68,68,.3);border-radius:var(--r-xl);font-size:.85rem;font-weight:600;cursor:pointer;font-family:inherit"
                onclick="confirmDelete('rm-img-form')">Remove Current Thumbnail</button>
        </form>
    </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
function openEditModal(s) {
    document.getElementById('editId').value       = s.id;
    document.getElementById('editName').value     = s.name;
    document.getElementById('editDesc').value     = s.description || '';
    document.getElementById('editPrice').value    = s.price;
    document.getElementById('editDur').value      = s.duration_hours;
    document.getElementById('editGuests').value   = s.max_guests || '';
    document.getElementById('editActive').checked = s.is_active == 1;
    document.getElementById('editModal').classList.add('active');
}

function openEditImg(s) {
    document.getElementById('editImgId').value = s.id;
    document.getElementById('rmImgId').value   = s.id;
    document.getElementById('editImgLabel').textContent = ' ' + s.name;
    // Preserve all existing fields
    document.getElementById('eIN').value  = s.name;
    document.getElementById('eID').value  = s.description || '';
    document.getElementById('eIP').value  = s.price;
    document.getElementById('eDH').value  = s.duration_hours;
    document.getElementById('eMG').value  = s.max_guests || '';
    document.getElementById('eIA').value  = s.is_active;
    // Show current image if exists
    const wrap = document.getElementById('currentImgWrap');
    const prev = document.getElementById('currentImgPreview');
    if (s.image) {
        prev.src = s.image_type === 'upload'
            ? (window.SITE_URL + '/' + s.image)
            : s.image;
        wrap.style.display = 'block';
    } else {
        wrap.style.display = 'none';
    }
    // Reset tabs
    switchImgTab('up');
    // Reset previews
    ['fp-edit','up-edit'].forEach(id => document.getElementById(id).style.display='none');
    document.getElementById('editImgModal').classList.add('active');
}

function switchImgTab(tab) {
    const isUp = tab === 'up';
    document.getElementById('tab-up').style.background  = isUp  ? 'var(--maroon)' : 'var(--panel)';
    document.getElementById('tab-up').style.color       = isUp  ? '#fff' : 'var(--text-mid)';
    document.getElementById('tab-url').style.background = !isUp ? 'var(--maroon)' : 'var(--panel)';
    document.getElementById('tab-url').style.color      = !isUp ? '#fff' : 'var(--text-mid)';
    document.getElementById('pane-up').style.display    = isUp  ? 'block' : 'none';
    document.getElementById('pane-url').style.display   = !isUp ? 'block' : 'none';
}

function previewFile(input, wrapId) {
    const wrap = document.getElementById(wrapId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { wrap.querySelector('img').src = e.target.result; wrap.style.display='block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewFromUrl(input, wrapId) {
    const wrap = document.getElementById(wrapId);
    const img  = wrap.querySelector('img');
    const url  = input.value.trim();
    if (url) {
        img.src     = url;
        img.onload  = () => wrap.style.display = 'block';
        img.onerror = () => wrap.style.display = 'none';
    } else {
        wrap.style.display = 'none';
    }
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
