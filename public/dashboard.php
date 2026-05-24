<?php
$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$user = currentUser();
$pdo  = getDB();
$profileMsg = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])) {
    $pdo->prepare("UPDATE users SET first_name=?,last_name=?,phone=? WHERE id=?")
        ->execute([sanitize($_POST['first_name']),sanitize($_POST['last_name']),sanitize($_POST['phone']),$user['id']]);
    $profileMsg = 'Profile updated!';
    $user = currentUser();
}

$stmt = $pdo->prepare("SELECT b.*,s.name AS sname,s.price,p.payment_status,p.amount AS pamount,p.payment_method
    FROM bookings b JOIN services s ON b.service_id=s.id LEFT JOIN payments p ON p.booking_id=b.id
    WHERE b.user_id=? ORDER BY b.created_at DESC");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

$total    = count($bookings);
$pending  = count(array_filter($bookings, fn($b)=>$b['status']==='pending'));
$approved = count(array_filter($bookings, fn($b)=>$b['status']==='approved'));
$done     = count(array_filter($bookings, fn($b)=>$b['status']==='completed'));
?>

<div class="page-hero">
  <div class="page-hero-content">
    <div class="section-tag" style="margin-bottom:12px">My Account</div>
    <h1>Hello, <?= htmlspecialchars($user['first_name']) ?>! 👋</h1>
    <p>@<?= htmlspecialchars($user['username']) ?> &nbsp;·&nbsp; Manage your bookings and profile here</p>
  </div>
</div>

<div class="dashboard-wrapper">
  <!-- Stats -->
  <div class="dash-cards">
    <?php
    $stats = [['Total',$total,'#6b0f0f','📋'],['Pending',$pending,'#f59e0b','⏳'],['Confirmed',$approved,'#22c55e','✅'],['Completed',$done,'#3b82f6','🎉']];
    foreach ($stats as [$lbl,$val,$col,$ico]): ?>
    <div class="dash-card">
      <div style="font-size:1.8rem;margin-bottom:6px"><?= $ico ?></div>
      <div style="font-family:var(--font-display);font-size:2rem;font-weight:700;color:<?= $col ?>"><?= $val ?></div>
      <div style="font-size:.82rem;color:var(--text-light);font-weight:500;margin-top:4px"><?= $lbl ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <h2 style="font-family:var(--font-display);font-size:1.8rem;color:var(--maroon-deep);margin-bottom:20px">Booking History</h2>
  <?php if (empty($bookings)): ?>
  <div style="text-align:center;padding:60px;background:#fff;border-radius:16px;box-shadow:var(--shadow-sm)">
    <div style="font-size:3rem;margin-bottom:16px">📷</div>
    <h3 style="font-family:var(--font-display);color:var(--maroon-deep);margin-bottom:8px">No bookings yet</h3>
    <p style="color:var(--text-mid);margin-bottom:24px">Ready to create a memorable event?</p>
    <a href="<?= SITE_URL ?>/public/booking.php" class="btn-primary">Book Now →</a>
  </div>
  <?php else: ?>
  <div style="overflow-x:auto;border-radius:16px;box-shadow:var(--shadow-sm)">
    <table class="data-table">
      <thead><tr><th>Ref #</th><th>Service</th><th>Event</th><th>Date</th><th>Status</th><th>Payment</th><th>Amount</th></tr></thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
        <tr>
          <td><strong style="color:var(--maroon)"><?= htmlspecialchars($b['booking_ref']) ?></strong></td>
          <td><?= htmlspecialchars($b['sname']) ?></td>
          <td><?= htmlspecialchars($b['event_name']) ?></td>
          <td><?= date('M d, Y',strtotime($b['event_date'])) ?></td>
          <td><span class="status-badge status-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
          <td><span class="status-badge status-<?= $b['payment_status']??'pending' ?>"><?= ucfirst($b['payment_status']??'pending') ?></span></td>
          <td>₱<?= number_format($b['pamount']??$b['price'],2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- Edit Profile -->
  <div style="margin-top:48px">
    <h2 style="font-family:var(--font-display);font-size:1.8rem;color:var(--maroon-deep);margin-bottom:20px">Edit Profile</h2>
    <?php if ($profileMsg): ?><div class="alert alert-success"><?= htmlspecialchars($profileMsg) ?></div><?php endif; ?>
    <div class="form-wrapper" style="max-width:none;padding:36px">
      <form method="POST">
        <div class="form-row">
          <div class="form-group">
            <label>Username (cannot change)</label>
            <input type="text" value="@<?= htmlspecialchars($user['username']) ?>" disabled style="opacity:.6">
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']??'') ?>" placeholder="+63 9XX XXX XXXX">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>First Name</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
          </div>
        </div>
        <button type="submit" name="update_profile" value="1" class="form-submit" style="max-width:220px">Save Changes</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
