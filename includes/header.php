<?php
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = $pageTitle ?? 'Pixel Pod Photobooth';
$user      = isLoggedIn() ? currentUser() : null;
$base      = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> | Pixel Pod Photobooth</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/public/css/style.css">
<?= $extraHead ?? '' ?>
</head>
<body>

<nav class="site-nav" id="siteNav">
  <div class="nav-container">
    <a href="<?= SITE_URL ?>/public/index.php" class="nav-logo">
      <span class="logo-text">Pixel <em>Pod</em></span>
    </a>
    <ul class="nav-links" id="navLinks">
      <li><a href="<?= SITE_URL ?>/public/index.php"    <?= $base==='index.php'   ?'class="active"':'' ?>>Home</a></li>
      <li><a href="<?= SITE_URL ?>/public/services.php" <?= $base==='services.php'?'class="active"':'' ?>>Services</a></li>
      <li><a href="<?= SITE_URL ?>/public/booking.php"  <?= $base==='booking.php' ?'class="active"':'' ?>>Book Now</a></li>
      <li><a href="http://localhost/PixelPodWeb/public/customize.php">Customize</a></li>
<li><a href="http://localhost/PixelPodWeb/public/photobooth/index.php">Try Booth</a></li>
      <li><a href="<?= SITE_URL ?>/public/contact.php"  <?= $base==='contact.php' ?'class="active"':'' ?>>Contact</a></li>
    </ul>
    <div class="nav-actions" id="navActions">
      <?php if ($user): ?>
        <!-- Show @username when logged in -->
        <a href="<?= SITE_URL ?>/public/dashboard.php" class="btn-ghost">
          @<?= htmlspecialchars($user['username']) ?>
        </a>
        <?php if ($user['role']==='admin'): ?>
          <a href="<?= SITE_URL ?>/admin/index.php" class="btn-primary">Admin Panel</a>
        <?php endif; ?>
        <a href="<?= SITE_URL ?>/public/logout.php" class="btn-ghost">Logout</a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/public/login.php"    class="btn-ghost">Login</a>
        <a href="<?= SITE_URL ?>/public/register.php" class="btn-primary">Register</a>
      <?php endif; ?>
    </div>
    <button class="nav-toggle" id="navToggle" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>
