<?php
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) {
  header('Location: ' . SITE_URL . (isAdmin() ? '/admin/index.php' : '/public/dashboard.php'));
  exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $r = loginUser($_POST['username'] ?? '', $_POST['password'] ?? '');
  if ($r['success']) {
    if ($r['role'] === 'admin') {
      header('Location: http://localhost/PixelPodWeb/admin/index.php');
    } else {
      header('Location: http://localhost/PixelPodWeb/public/index.php');
    }
    exit;
  }
  $error = $r['message'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Login | Pixel Pod Photobooth</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/style.css">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background-image: url('https://images.unsplash.com/photo-1519741497674-611481863552?w=1920&q=80');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: rgba(56, 8, 8, 0.80);
      z-index: 0;
    }

    .auth-page {
      position: relative;
      z-index: 1;
    }

    .auth-page {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px
    }
  </style>
</head>

<body>
  <div class="auth-page">
    <div style="width:100%;max-width:440px">
      <div style="text-align:center;margin-bottom:32px">
        <div style="font-size:2.5rem;margin-bottom:8px">&#128248;</div>
        <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:#fff">Pixel <em style="color:var(--gold-light)">Pod</em> Photobooth</div>
      </div>
      <div class="form-wrapper">
        <h2 class="form-title">Welcome Back</h2>
        <p class="form-sub">Sign in with your username to manage your bookings</p>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required autocomplete="username"
              placeholder="Your username"
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
          </div>
          <button type="submit" class="form-submit">Sign In →</button>
        </form>
        <p class="form-footer">Don't have an account? <a href="<?= SITE_URL ?>/public/register.php">Create one →</a></p>
        <p class="form-footer" style="margin-top:8px"><a href="<?= SITE_URL ?>/public/index.php" style="color:var(--text-light)">← Back to website</a></p>
      </div>
    </div>
  </div>
  <script src="<?= SITE_URL ?>/public/js/main.js"></script>
</body>
</html>