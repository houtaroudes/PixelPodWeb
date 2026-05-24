<?php
$u       = currentUser();
$ini     = strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1));
$unread  = getDB()->query("SELECT COUNT(*) FROM inquiries WHERE is_read=0")->fetchColumn();
$pending = getDB()->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn();
$ap      = $activePage ?? '';
?>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-logo">
    <a href="http://localhost/PixelPodWeb/admin/index.php" class="sidebar-logo-inner">
      <span class="logo-icon" style="font-size:1.5rem"></span>
      <div>
        <div class="logo-text">Pixel <em>Pod</em></div>
        <span class="sidebar-tag">Admin Panel</span>
      </div>
    </a>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section-title">Main</div>
    <a href="http://localhost/PixelPodWeb/admin/index.php"     class="sidebar-link <?= $ap==='dashboard'?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="http://localhost/PixelPodWeb/admin/bookings.php"  class="sidebar-link <?= $ap==='bookings' ?'active':'' ?>">
      <i class="bi bi-calendar-check"></i> Bookings
      <?php if($pending>0):?><span class="badge"><?=$pending?></span><?php endif;?>
    </a>
    <a href="http://localhost/PixelPodWeb/admin/events.php"    class="sidebar-link <?= $ap==='events'   ?'active':'' ?>"><i class="bi bi-calendar-event"></i> Events</a>

    <div class="sidebar-section-title">Management</div>
    <a href="http://localhost/PixelPodWeb/admin/services.php"  class="sidebar-link <?= $ap==='services' ?'active':'' ?>"><i class="bi bi-box-seam"></i> Packages</a>
    <a href="http://localhost/PixelPodWeb/admin/customize.php" class="sidebar-link <?= $ap==='customize'?'active':'' ?>"><i class="bi bi-sliders"></i> Customizations</a>
    <a href="http://localhost/PixelPodWeb/admin/photos.php"    class="sidebar-link <?= $ap==='photos'   ?'active':'' ?>"><i class="bi bi-camera"></i> Photo Sessions</a>
    <a href="http://localhost/PixelPodWeb/admin/customers.php" class="sidebar-link <?= $ap==='customers'?'active':'' ?>"><i class="bi bi-people"></i> Customers</a>
    <a href="http://localhost/PixelPodWeb/admin/payments.php"  class="sidebar-link <?= $ap==='payments' ?'active':'' ?>"><i class="bi bi-credit-card"></i> Payments</a>
    <div class="sidebar-section-title">Reports</div>
    <a href="http://localhost/PixelPodWeb/admin/analytics.php" class="sidebar-link <?= $ap==='analytics'?'active':'' ?>"><i class="bi bi-bar-chart-line"></i> Analytics</a>
    <a href="http://localhost/PixelPodWeb/admin/inquiries.php" class="sidebar-link <?= $ap==='inquiries'?'active':'' ?>">
      <i class="bi bi-chat-text"></i> Inquiries
      <?php if($unread>0):?><span class="badge"><?=$unread?></span><?php endif;?>
    </a>

    <div class="sidebar-section-title">System</div>
    <a href="http://localhost/PixelPodWeb/admin/settings.php"  class="sidebar-link <?= $ap==='settings' ?'active':'' ?>"><i class="bi bi-gear"></i> Settings</a>
    <a href="http://localhost/PixelPodWeb/public/index.php" target="_blank" class="sidebar-link"><i class="bi bi-globe"></i> View Website</a>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar"><?= $ini ?></div>
      <div>
        <div class="user-info-name">@<?= htmlspecialchars($u['username']) ?></div>
        <div class="user-info-role"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?> · <?= $u['role'] ?></div>
      </div>
    </div>
    <a href="http://localhost/PixelPodWeb/public/logout.php" class="sidebar-logout"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </div>
</aside>
