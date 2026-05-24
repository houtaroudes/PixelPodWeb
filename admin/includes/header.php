<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$pageTitle  = $pageTitle  ?? 'Dashboard';
$activePage = $activePage ?? 'dashboard';
$u   = currentUser();
$ini = strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | Pixel Pod Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= SITE_URL ?>/admin/css/admin.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    .admin-sidebar {
      display: flex !important;
      flex-direction: column !important;
      height: 100vh !important;
      overflow: hidden !important;
    }

    .sidebar-nav {
      flex: 1 !important;
      overflow-y: scroll !important;
      scroll-behavior: smooth !important;
      -ms-overflow-style: none !important;
      scrollbar-width: none !important;
    }

    .sidebar-nav::-webkit-scrollbar {
      display: none !important;
    }

    .sidebar-footer {
      flex-shrink: 0 !important;
      background: #180808 !important;
      border-top: 1px solid rgba(143, 31, 31, 0.3) !important;
      padding: 16px 0 !important;
    }
  </style>
  <script>
    window.SITE_URL = '<?= SITE_URL ?>';
  </script>
  <?= $extraHead ?? '' ?>
</head>

<body>
  <?php require_once __DIR__ . '/sidebar.php'; ?>
  <div class="admin-main">
    <header class="admin-topbar">
      <div style="display:flex;align-items:center;gap:16px">
        <button id="sidebarToggle" style="display:none;background:none;border:none;cursor:pointer;font-size:1.3rem">☰</button>
        <div class="topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
      </div>
      <div class="topbar-right">
        <span class="topbar-date"><?= date('l, F j, Y') ?></span>
        <a href="<?= SITE_URL ?>/admin/inquiries.php" class="notif-btn" title="Inquiries">💬
          <?php if (getDB()->query("SELECT COUNT(*) FROM inquiries WHERE is_read=0")->fetchColumn() > 0): ?>
            <span class="notif-dot"></span>
          <?php endif; ?>
        </a>
        <!-- Show username in topbar -->
        <div class="topbar-avatar" title="@<?= htmlspecialchars($u['username']) ?>"><?= $ini ?></div>
      </div>
    </header>
    <div class="admin-content">