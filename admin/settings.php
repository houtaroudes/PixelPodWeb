<?php
$pageTitle  = 'Settings';
$activePage = 'settings';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$msg = $err = '';

// Handle account actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE new user (admin or customer)
    if ($action === 'create_user') {
        $username   = trim($_POST['username']   ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name']  ?? '');
        $email      = trim($_POST['email']      ?? '');
        $phone      = trim($_POST['phone']      ?? '');
        $password   = trim($_POST['password']   ?? '');
        $role       = in_array($_POST['role'] ?? '', ['admin','customer']) ? $_POST['role'] : 'customer';

        if (empty($username) || empty($first_name) || empty($password)) {
            $err = 'Username, first name, and password are required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $err = 'Username must be 3-30 chars. Letters, numbers, underscores only.';
        } elseif (strlen($password) < 6) {
            $err = 'Password must be at least 6 characters.';
        } else {
            // Check username uniqueness
            $chk = $pdo->prepare("SELECT id FROM users WHERE username=?");
            $chk->execute([$username]);
            if ($chk->fetch()) {
                $err = "Username \"$username\" is already taken.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare("INSERT INTO users (username,first_name,last_name,email,password,phone,role) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$username, $first_name, $last_name, $email, $hash, $phone, $role]);
                $msg = "✓ Account <strong>@$username</strong> created successfully as $role.";
            }
        }
    }

    // RESET PASSWORD
    if ($action === 'reset_password') {
        $uid      = intval($_POST['user_id'] ?? 0);
        $newpass  = trim($_POST['new_password'] ?? '');
        if ($uid > 0 && strlen($newpass) >= 6) {
            $hash = password_hash($newpass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $uid]);
            $msg = '✓ Password updated successfully.';
        } else {
            $err = 'Password must be at least 6 characters.';
        }
    }

    // DELETE user
    if ($action === 'delete_user') {
        $uid = intval($_POST['user_id'] ?? 0);
        // Prevent deleting yourself
        if ($uid === (int)$_SESSION['user_id']) {
            $err = 'You cannot delete your own account.';
        } elseif ($uid > 0) {
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
            $msg = '✓ Account deleted.';
        }
    }

    // TOGGLE role admin ↔ customer
    if ($action === 'toggle_role') {
        $uid = intval($_POST['user_id'] ?? 0);
        if ($uid === (int)$_SESSION['user_id']) {
            $err = 'You cannot change your own role.';
        } elseif ($uid > 0) {
            $pdo->prepare("UPDATE users SET role=IF(role='admin','customer','admin') WHERE id=?")->execute([$uid]);
            $msg = '✓ Role updated.';
        }
    }
}

// Fetch all users
$admins    = $pdo->query("SELECT * FROM users WHERE role='admin'    ORDER BY created_at ASC")->fetchAll();
$customers = $pdo->query("SELECT * FROM users WHERE role='customer' ORDER BY created_at ASC")->fetchAll();
?>

<?php if ($msg): ?><div class="alert alert-success" data-dismiss><?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"   data-dismiss><?= $err ?></div><?php endif; ?>

<div class="admin-page-header">
    <div>
        <div class="admin-page-title">Settings & Accounts</div>
        <div class="admin-page-sub">Manage admin/staff and customer accounts</div>
    </div>
    <button class="btn-save" data-modal-open="createUserModal">&#43; Create New Account</button>
</div>

<!-- Admin/Staff Accounts -->
<div class="admin-form-card" style="margin-bottom:28px">
    <div class="admin-form-title">👤 Admin / Staff Accounts (<?= count($admins) ?>)</div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($admins as $u): ?>
            <tr class="searchable-row">
                <td>
                    <strong style="color:var(--maroon)">@<?= htmlspecialchars($u['username']) ?></strong>
                    <?php if ($u['id'] == $_SESSION['user_id']): ?>
                        <span style="font-size:.7rem;background:rgba(34,197,94,.1);color:#166534;padding:2px 8px;border-radius:100px;margin-left:6px">You</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                <td style="color:var(--text-mid)"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                <td style="color:var(--text-mid)"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                <td style="color:var(--text-light)"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <!-- Reset password -->
                        <button class="action-btn edit" title="Reset Password"
                            onclick="openResetModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">&#128273;</button>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <!-- Toggle role -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action"  value="toggle_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="action-btn view" title="Make Customer" onclick="return confirm('Change this account to Customer?')">&#128100;</button>
                        </form>
                        <!-- Delete -->
                        <form method="POST" style="display:inline" id="del-u-<?= $u['id'] ?>">
                            <input type="hidden" name="action"  value="delete_user">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="button" class="action-btn delete" title="Delete"
                                onclick="confirmDelete('del-u-<?= $u['id'] ?>')">&#128465;</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Customer Accounts -->
<div class="admin-form-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid var(--border)">
        <div class="admin-form-title" style="margin:0;padding:0;border:none">&#128101; Customer Accounts (<?= count($customers) ?>)</div>
        <input type="text" id="tableSearch" class="filter-input" placeholder="Search customers…" style="max-width:260px">
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $u): ?>
            <tr class="searchable-row">
                <td><strong style="color:var(--maroon)">@<?= htmlspecialchars($u['username']) ?></strong></td>
                <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                <td style="color:var(--text-mid)"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                <td style="color:var(--text-mid)"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                <td style="color:var(--text-light)"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <button class="action-btn edit" title="Reset Password"
                            onclick="openResetModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">&#128273;</button>
                        <!-- Make admin -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action"  value="toggle_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="action-btn approve" title="Make Admin" onclick="return confirm('Promote this account to Admin?')">&#9733;</button>
                        </form>
                        <form method="POST" style="display:inline" id="del-u-<?= $u['id'] ?>">
                            <input type="hidden" name="action"  value="delete_user">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="button" class="action-btn delete" title="Delete"
                                onclick="confirmDelete('del-u-<?= $u['id'] ?>')">&#128465;</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($customers)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--text-light);padding:2rem">No customers yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Create User Modal -->
<div class="modal-overlay" id="createUserModal">
    <div class="modal-box" style="max-width:540px;max-height:90vh;overflow-y:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;position:sticky;top:0;background:var(--card);padding-bottom:14px;border-bottom:1px solid var(--border)">
            <div class="modal-title">+ Create New Account</div>
            <button data-modal-close style="background:none;border:none;cursor:pointer;font-size:1.3rem">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create_user">
            <div class="form-group">
                <label>Role *</label>
                <select name="role" style="width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:var(--r-sm);font-size:.9rem;background:var(--panel);font-family:var(--font-body)">
                    <option value="customer">&#128100; Customer</option>
                    <option value="admin">&#9733; Admin / Staff</option>
                </select>
            </div>
            <div class="form-group">
                <label>Username * <small style="color:var(--text-light);font-weight:400">(used to log in)</small></label>
                <input type="text" name="username" required
                       placeholder="e.g. staff_anna or juan_dc"
                       pattern="[a-zA-Z0-9_]{3,30}"
                       title="3-30 chars. Letters, numbers, underscores only.">
                <small style="color:var(--text-light);font-size:.75rem">3–30 characters. Letters, numbers, underscores only.</small>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" required placeholder="Maria">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Santos">
                </div>
            </div>
            <div class="form-group">
                <label>Password * <small style="color:var(--text-light);font-weight:400">(min 6 chars)</small></label>
                <input type="text" name="password" required minlength="6" placeholder="Set a password for this account">
            </div>
            <div class="form-group">
                <label>Email <small style="color:var(--text-light);font-weight:400">(optional)</small></label>
                <input type="email" name="email" placeholder="email@example.com">
            </div>
            <div class="form-group">
                <label>Phone <small style="color:var(--text-light);font-weight:400">(optional)</small></label>
                <input type="text" name="phone" placeholder="+63 9XX XXX XXXX">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-save">Create Account</button>
                <button type="button" class="btn-cancel" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>

<!--Reset Password Modal -->
<div class="modal-overlay" id="resetModal">
    <div class="modal-box" style="max-width:400px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
            <div class="modal-title">&#128273; Reset Password</div>
            <button data-modal-close style="background:none;border:none;cursor:pointer;font-size:1.3rem">✕</button>
        </div>
        <div id="resetLabel" style="font-size:.88rem;color:var(--text-mid);margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border)"></div>
        <form method="POST">
            <input type="hidden" name="action"  value="reset_password">
            <input type="hidden" name="user_id" id="resetUserId">
            <div class="form-group">
                <label>New Password * <small style="color:var(--text-light);font-weight:400">(min 6 chars)</small></label>
                <input type="password" name="new_password" required minlength="6"
       placeholder="Enter new password" id="resetPassInput"
       autocomplete="new-password">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-save">Save Password</button>
                <button type="button" class="btn-cancel" data-modal-close>Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
function openResetModal(userId, username) {
    document.getElementById('resetUserId').value   = userId;
    document.getElementById('resetLabel').textContent = 'Resetting password for: @' + username;
    document.getElementById('resetPassInput').value = '';
    document.getElementById('resetModal').classList.add('active');
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
