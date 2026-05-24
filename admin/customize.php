<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();
$msg = $err = '';

// Determine active tab
$tab = in_array($_GET['tab'] ?? '', ['sizes','layouts','filters','designs'])
     ? $_GET['tab'] : 'sizes';

// Handle POST actions 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $type   = $_POST['type']   ?? '';

    // Map type to table
    $tables = ['sizes'=>'photo_sizes','layouts'=>'layouts','filters'=>'filters','designs'=>'designs'];
    $table  = $tables[$type] ?? null;

    if ($table) {
        if ($action === 'create') {
            $name  = trim($_POST['name']        ?? '');
            $desc  = trim($_POST['description'] ?? '');
            $img   = trim($_POST['sample_image']?? '');
            $order = intval($_POST['sort_order'] ?? 0);

            if (empty($name)) { $err = 'Name is required.'; }
            else {
                if ($type === 'sizes') {
                    $dim   = trim($_POST['dimensions']  ?? '');
                    $price = floatval($_POST['addon_price'] ?? 0);
                    $pdo->prepare("INSERT INTO photo_sizes (name,description,dimensions,addon_price,sample_image,sort_order) VALUES (?,?,?,?,?,?)")
                        ->execute([$name,$desc,$dim,$price,$img,$order]);
                } elseif ($type === 'layouts') {
                    $cnt = intval($_POST['photos_count'] ?? 3);
                    $pdo->prepare("INSERT INTO layouts (name,description,photos_count,sample_image,sort_order) VALUES (?,?,?,?,?)")
                        ->execute([$name,$desc,$cnt,$img,$order]);
                } elseif ($type === 'filters') {
                    $pdo->prepare("INSERT INTO filters (name,description,sample_image,sort_order) VALUES (?,?,?,?)")
                        ->execute([$name,$desc,$img,$order]);
                } elseif ($type === 'designs') {
                    $cat = trim($_POST['category'] ?? 'general');
                    $pdo->prepare("INSERT INTO designs (name,description,category,sample_image,sort_order) VALUES (?,?,?,?,?)")
                        ->execute([$name,$desc,$cat,$img,$order]);
                }
                $msg = "✓ " . ucfirst(rtrim($type,'s')) . " <strong>$name</strong> added!";
            }
        }

        if ($action === 'delete') {
            $id = intval($_POST['item_id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
                $msg = '✓ Item deleted.';
            }
        }

        if ($action === 'toggle') {
            $id = intval($_POST['item_id'] ?? 0);
            $pdo->prepare("UPDATE $table SET is_active = IF(is_active=1,0,1) WHERE id=?")->execute([$id]);
            $msg = '✓ Visibility toggled.';
        }

        if ($action === 'update') {
            $id   = intval($_POST['item_id'] ?? 0);
            $name = trim($_POST['name']        ?? '');
            $desc = trim($_POST['description'] ?? '');
            $img  = trim($_POST['sample_image']?? '');
            $ord  = intval($_POST['sort_order'] ?? 0);
            if ($id > 0 && !empty($name)) {
                if ($type === 'sizes') {
                    $pdo->prepare("UPDATE photo_sizes SET name=?,description=?,dimensions=?,addon_price=?,sample_image=?,sort_order=? WHERE id=?")
                        ->execute([$name,$desc,trim($_POST['dimensions']??''),floatval($_POST['addon_price']??0),$img,$ord,$id]);
                } elseif ($type === 'layouts') {
                    $pdo->prepare("UPDATE layouts SET name=?,description=?,photos_count=?,sample_image=?,sort_order=? WHERE id=?")
                        ->execute([$name,$desc,intval($_POST['photos_count']??3),$img,$ord,$id]);
                } elseif ($type === 'filters') {
                    $pdo->prepare("UPDATE filters SET name=?,description=?,sample_image=?,sort_order=? WHERE id=?")
                        ->execute([$name,$desc,$img,$ord,$id]);
                } elseif ($type === 'designs') {
                    $pdo->prepare("UPDATE designs SET name=?,description=?,category=?,sample_image=?,sort_order=? WHERE id=?")
                        ->execute([$name,$desc,trim($_POST['category']??'general'),$img,$ord,$id]);
                }
                $msg = '✓ Updated successfully.';
            }
        }
    }

    // Only redirect if no error redirect happens BEFORE any HTML output
    if (empty($err)) {
        header("Location: ?tab=$type");
        exit;
    }
}

// Fetch data for display
$sizes   = $pdo->query("SELECT * FROM photo_sizes ORDER BY sort_order,id")->fetchAll();
$layouts = $pdo->query("SELECT * FROM layouts     ORDER BY sort_order,id")->fetchAll();
$filters = $pdo->query("SELECT * FROM filters     ORDER BY sort_order,id")->fetchAll();
$designs = $pdo->query("SELECT * FROM designs     ORDER BY sort_order,id")->fetchAll();

$tabData = ['sizes'=>$sizes,'layouts'=>$layouts,'filters'=>$filters,'designs'=>$designs];
$current = $tabData[$tab];

// NOW include header
$pageTitle  = 'Customizations';
$activePage = 'customize';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($msg): ?><div class="alert alert-success" data-dismiss><?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"   data-dismiss><?= $err ?></div><?php endif; ?>

<div class="admin-page-header">
    <div>
        <div class="admin-page-title">Customizations Manager</div>
        <div class="admin-page-sub">Manage photo sizes, layouts, filters &amp; designs</div>
    </div>
    <a href="http://localhost/PixelPodWeb/public/customize.php" target="_blank" class="btn-save">
        👁 Preview Public Page
    </a>
</div>

<!-- Tab Navigation -->
<div style="display:flex;gap:8px;margin-bottom:28px;border-bottom:2px solid var(--border);padding-bottom:0">
    <?php
    $tabs = ['sizes'=>'Photo Sizes','layouts'=>'Layouts','filters'=>'Filters','designs'=>'Designs'];
    foreach ($tabs as $key => $label):
        $cnt = count($tabData[$key]);
    ?>
    <a href="?tab=<?= $key ?>"
       style="padding:10px 20px;font-size:.88rem;font-weight:600;text-decoration:none;border-radius:8px 8px 0 0;border:2px solid <?= $tab===$key ? 'var(--border)' : 'transparent' ?>;border-bottom:none;background:<?= $tab===$key ? '#fff' : 'transparent' ?>;color:<?= $tab===$key ? 'var(--maroon)' : 'var(--text-mid)' ?>;margin-bottom:-2px">
        <?= $label ?> <span style="font-size:.75rem;background:rgba(107,15,15,.08);padding:2px 8px;border-radius:100px;margin-left:4px"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
</div>

<div class="row" style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

    <!-- Items List -->
    <div>
        <div class="admin-form-card" style="padding:0;overflow:hidden">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Preview</th>
                        <th>Name</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($current)): ?>
                    <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-light)">No items yet. Add one →</td></tr>
                <?php endif; ?>
                <?php foreach ($current as $item): ?>
                <tr>
                    <td>
                        <?php if (!empty($item['sample_image'])): ?>
                        <img src="<?= htmlspecialchars($item['sample_image']) ?>"
                             style="width:64px;height:48px;object-fit:cover;border-radius:6px;border:1px solid var(--border)"
                             onerror="this.style.display='none'">
                        <?php else: ?>
                        <div style="width:64px;height:48px;background:var(--panel);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.4rem">
                            <?= $tab==='sizes'?'':($tab==='layouts'?'':($tab==='filters'?'':'')) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong style="color:var(--maroon-deep)"><?= htmlspecialchars($item['name']) ?></strong>
                        <?php if (!empty($item['description'])): ?>
                        <div style="font-size:.75rem;color:var(--text-light);margin-top:2px"><?= htmlspecialchars(substr($item['description'],0,60)) ?>...</div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.82rem;color:var(--text-mid)">
                        <?php if ($tab==='sizes'): ?>
                            <?= htmlspecialchars($item['dimensions']) ?><br>
                            <strong style="color:var(--maroon)"><?= $item['addon_price']>0 ? '+₱'.number_format($item['addon_price'],0) : 'Included' ?></strong>
                        <?php elseif ($tab==='layouts'): ?>
                            <?= $item['photos_count'] ?> shots
                        <?php elseif ($tab==='designs'): ?>
                            <?= ucfirst($item['category']) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?= $item['is_active'] ? 'status-approved' : 'status-cancelled' ?>">
                            <?= $item['is_active'] ? 'Visible' : 'Hidden' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <!-- Edit button -->
                            <button class="action-btn edit"
                                onclick="openEdit(<?= htmlspecialchars(json_encode($item)) ?>, '<?= $tab ?>')"
                                title="Edit">&#x270E;</button>
                            <!-- Toggle visibility -->
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action"  value="toggle">
                                <input type="hidden" name="type"    value="<?= $tab ?>">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="action-btn view" title="Toggle visibility">&#128065;</button>
                            </form>
                            <!-- Delete -->
                            <form method="POST" style="display:inline" id="del-<?= $tab ?>-<?= $item['id'] ?>">
                                <input type="hidden" name="action"  value="delete">
                                <input type="hidden" name="type"    value="<?= $tab ?>">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <button type="button" class="action-btn delete" title="Delete"
                                    onclick="confirmDelete('del-<?= $tab ?>-<?= $item['id'] ?>')">&#128465;</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add New Form -->
    <div>
        <div class="admin-form-card">
            <div class="admin-form-title">Add New <?= rtrim(ucfirst($tab),'s') ?></div>
            <form method="POST" action="?tab=<?= $tab ?>" class="admin-form">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="type"   value="<?= $tab ?>">

                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Classic Strip">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="2" placeholder="Short description shown to customers"></textarea>
                </div>

                <?php if ($tab === 'sizes'): ?>
                <div class="form-group">
                    <label>Dimensions</label>
                    <input type="text" name="dimensions" placeholder='e.g. 2" × 6"'>
                </div>
                <div class="form-group">
                    <label>Add-on Price (₱) <small style="color:var(--text-light)">0 = included</small></label>
                    <input type="number" name="addon_price" min="0" step="50" value="0" placeholder="0">
                </div>
                <?php elseif ($tab === 'layouts'): ?>
                <div class="form-group">
                    <label>Number of Photos per Session</label>
                    <input type="number" name="photos_count" min="1" max="10" value="3" placeholder="3">
                </div>
                <?php elseif ($tab === 'designs'): ?>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:.88rem;background:var(--panel)">
                        <option value="general">General</option>
                        <option value="wedding">Wedding</option>
                        <option value="birthday">Birthday</option>
                        <option value="corporate">Corporate</option>
                        <option value="debut">Debut</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Sample Image URL <small style="color:var(--text-light)">(preview photo)</small></label>
                    <input type="text" name="sample_image" placeholder="https://example.com/image.jpg"
                           oninput="previewAddImg(this)">
                    <div id="addImgPreview" style="display:none;margin-top:8px">
                        <img id="addImgEl" style="width:100%;max-height:120px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
                    </div>
                </div>
                <div class="form-group">
                    <label>Sort Order <small style="color:var(--text-light)">(lower = appears first)</small></label>
                    <input type="number" name="sort_order" value="0" min="0">
                </div>
                <button type="submit" class="btn-save" style="width:100%">Add <?= rtrim(ucfirst($tab),'s') ?></button>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box" style="max-width:500px;max-height:90vh;overflow-y:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;position:sticky;top:0;background:var(--card);padding-bottom:14px;border-bottom:1px solid var(--border)">
            <div class="modal-title">Edit Item</div>
            <button data-modal-close style="background:none;border:none;cursor:pointer;font-size:1.3rem">✕</button>
        </div>
        <form method="POST" action="?tab=<?= $tab ?>" class="admin-form" id="editForm">
            <input type="hidden" name="action"  value="update">
            <input type="hidden" name="type"    value="<?= $tab ?>">
            <input type="hidden" name="item_id" id="editId">
            <div class="form-group"><label>Name *</label><input type="text" name="name" id="editName" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="2" id="editDesc"></textarea></div>
            <div id="editExtraFields"></div>
            <div class="form-group">
                <label>Sample Image URL</label>
                <input type="text" name="sample_image" id="editImg" oninput="previewEditImg(this)">
                <div id="editImgPreview" style="margin-top:8px;display:none">
                    <img id="editImgEl" style="width:100%;max-height:120px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
                </div>
            </div>
            <div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" id="editOrder" min="0"></div>
            <div class="modal-actions">
                <button type="submit" class="btn-save">Save Changes</button>
                <button type="button" class="btn-cancel" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function previewAddImg(input) {
    const wrap = document.getElementById('addImgPreview');
    const img  = document.getElementById('addImgEl');
    if (input.value.trim()) {
        img.src     = input.value.trim();
        img.onload  = () => wrap.style.display = 'block';
        img.onerror = () => wrap.style.display = 'none';
    } else { wrap.style.display = 'none'; }
}

function previewEditImg(input) {
    const wrap = document.getElementById('editImgPreview');
    const img  = document.getElementById('editImgEl');
    if (input.value.trim()) {
        img.src     = input.value.trim();
        img.onload  = () => wrap.style.display = 'block';
        img.onerror = () => wrap.style.display = 'none';
    } else { wrap.style.display = 'none'; }
}

function openEdit(item, type) {
    document.getElementById('editId').value    = item.id;
    document.getElementById('editName').value  = item.name || '';
    document.getElementById('editDesc').value  = item.description || '';
    document.getElementById('editImg').value   = item.sample_image || '';
    document.getElementById('editOrder').value = item.sort_order || 0;

    if (item.sample_image) {
        const wrap = document.getElementById('editImgPreview');
        const img  = document.getElementById('editImgEl');
        img.src = item.sample_image;
        img.onload  = () => wrap.style.display = 'block';
        img.onerror = () => wrap.style.display = 'none';
    }

    let extra = '';
    if (type === 'sizes') {
        extra = `
        <div class="form-group"><label>Dimensions</label><input type="text" name="dimensions" value="${item.dimensions||''}"></div>
        <div class="form-group"><label>Add-on Price (₱)</label><input type="number" name="addon_price" min="0" step="50" value="${item.addon_price||0}"></div>`;
    } else if (type === 'layouts') {
        extra = `<div class="form-group"><label>Photos per Session</label><input type="number" name="photos_count" min="1" max="10" value="${item.photos_count||3}"></div>`;
    } else if (type === 'designs') {
        const cats = ['general','wedding','birthday','corporate','debut'];
        const opts = cats.map(c => `<option value="${c}" ${item.category===c?'selected':''}>${c.charAt(0).toUpperCase()+c.slice(1)}</option>`).join('');
        extra = `<div class="form-group"><label>Category</label><select name="category" style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:.88rem;background:var(--panel)">${opts}</select></div>`;
    }
    document.getElementById('editExtraFields').innerHTML = extra;
    document.getElementById('editModal').classList.add('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
