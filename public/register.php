<?php
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) { header('Location: ' . SITE_URL . '/public/dashboard.php'); exit; }
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['password'] !== ($_POST['password_confirm'] ?? '')) {
        $error = 'Passwords do not match.';
    } else {
        $r = registerUser($_POST);
        if ($r['success']) $success = $r['message'];
        else $error = $r['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Register | Pixel Pod Photobooth</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/public/css/style.css">
<style>
body{min-height:100vh;display:flex;flex-direction:column;background:linear-gradient(160deg,var(--maroon-deep) 0%,var(--maroon) 100%)}
.auth-page{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px}
</style>
</head>
<body>
<div class="auth-page">
  <div style="width:100%;max-width:520px">
    <div style="text-align:center;margin-bottom:32px">
      <div style="font-size:2.5rem;margin-bottom:8px">&#128248;</div>
      <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;color:#fff">Pixel <em style="color:var(--gold-light)">Pod</em> Photobooth</div>
    </div>
    <div class="form-wrapper">
      <h2 class="form-title">Create Account</h2>
      <p class="form-sub">Start booking your photobooth experience</p>
      <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <div style="text-align:center;padding:10px 0"><a href="<?= SITE_URL ?>/public/login.php" class="btn-primary">Sign In →</a></div>
      <?php else: ?>
      <form method="POST">
        <!-- Username — used for login -->
        <div class="form-group">
          <label>Username * <small style="color:var(--text-light);font-weight:400">(used to log in)</small></label>
          <input type="text" name="username" required
                 placeholder="e.g. maria_santos"
                 pattern="[a-zA-Z0-9_]{3,30}"
                 title="3-30 characters. Letters, numbers, underscores only."
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
          <small style="color:var(--text-light);font-size:.78rem">3–30 characters. Letters, numbers, underscores only.</small>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>First Name *</label>
            <input type="text" name="first_name" required placeholder="Maria" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Last Name *</label>
            <input type="text" name="last_name" required placeholder="Santos" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Email <small style="color:var(--text-light);font-weight:400">(optional)</small></label>
          <input type="email" name="email" placeholder="you@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Phone</label>
          <input type="text" name="phone" placeholder="+63 9XX XXX XXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Password * <small style="color:var(--text-light);font-weight:400">(min 6 chars)</small></label>
            <input type="password" name="password" required minlength="6" placeholder="••••••••">
          </div>
          <div class="form-group">
            <label>Confirm Password *</label>
            <input type="password" name="password_confirm" required minlength="6" placeholder="••••••••">
          </div>
        </div>
        <button type="submit" class="form-submit">Create Account →</button>
      </form>
      <?php endif; ?>
      <p class="form-footer">Already have an account? <a href="<?= SITE_URL ?>/public/login.php">Sign in →</a></p>
    </div>
  </div>
</div>
<script src="<?= SITE_URL ?>/public/js/main.js"></script>
</body>
</html>
