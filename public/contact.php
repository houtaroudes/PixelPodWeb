<?php
$pageTitle = 'Contact Us';
require_once __DIR__ . '/../includes/header.php';
$error = $success = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name = sanitize($_POST['full_name']??''); $email = sanitizeEmail($_POST['email']??'');
    $phone = sanitize($_POST['phone']??''); $subject = sanitize($_POST['subject']??''); $msg = sanitize($_POST['message']??'');
    if (!$name||!$email||!$msg) $error='Please fill name, email and message.';
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Please enter a valid email.';
    elseif (strlen($msg)<10) $error='Message must be at least 10 characters.';
    else {
        getDB()->prepare("INSERT INTO inquiries (full_name,email,phone,subject,message) VALUES (?,?,?,?,?)")->execute([$name,$email,$phone,$subject,$msg]);
        $success='Thank you! Your message has been sent. We\'ll get back to you within 24 hours.';
    }
}
?>
<div class="page-hero">
  <div class="page-hero-content">
    <div class="section-tag" style="margin-bottom:12px">Get In Touch</div>
    <h1>Contact Us</h1>
    <p>We'd love to hear from you about events, pricing, or anything else!</p>
  </div>
</div>
<section style="padding:80px 5%;background:var(--cream)">
  <div class="container">
    <div class="contact-grid">
      <div>
        <h3 style="font-family:var(--font-display);font-size:1.5rem;color:var(--maroon-deep);margin-bottom:20px">Reach Pixel Pod</h3>
        <p style="color:var(--text-mid);margin-bottom:32px;font-size:.95rem">Have questions about packages, availability, or want a custom quote? Reach us on any channel.</p>
        <div class="contact-item"><div class="contact-item-icon">&#128386;</div><div><p style="font-size:.82rem;color:var(--text-light)">Email</p><strong><a href="mailto:pixelpod.ph@gmail.com">pixelpod.ph@gmail.com</a></strong></div></div>
        <div class="contact-item"><div class="contact-item-icon">&#128222;</div><div><p style="font-size:.82rem;color:var(--text-light)">Phone / Viber</p><strong><a href="tel:0917 130 4683">0917 130 4683</a></strong></div></div>
        <div class="contact-item"><div class="contact-item-icon">&#128077;</div><div><p style="font-size:.82rem;color:var(--text-light)">Facebook</p><strong><a href="https://facebook.com/pxlpod.ph" target="_blank">pxlpod.ph</a></strong></div></div>
        <div class="contact-item"><div class="contact-item-icon">&#128248;</div><div><p style="font-size:.82rem;color:var(--text-light)">Instagram</p><strong><a href="https://instagram.com/pixelpod.ph" target="_blank">@pixelpod.ph</a></strong></div></div>
        <div style="margin-top:32px;padding:20px;background:rgba(107,15,15,.05);border-radius:16px;border:1px solid rgba(107,15,15,.1)">
          <h4 style="font-family:var(--font-display);color:var(--maroon-deep);margin-bottom:8px">Response Hours</h4>
          <p style="font-size:.9rem;color:var(--text-mid);line-height:1.7">Mon–Sat: 9:00 AM – 8:00 PM<br>Sun: 10:00 AM – 6:00 PM</p>
        </div>
      </div>
      <div class="form-wrapper" style="max-width:none;padding:40px">
        <h2 class="form-title" style="font-size:1.6rem;text-align:left">Send a Message</h2>
        <?php if($error): ?><div class="alert alert-error" data-dismiss><?= $error ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?= $success ?></div>
        <?php else: ?>
        <form method="POST">
          <div class="form-row">
            <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required placeholder="Your full name" value="<?= htmlspecialchars($_POST['full_name']??'') ?>"></div>
            <div class="form-group"><label>Email *</label><input type="email" name="email" required placeholder="you@email.com" value="<?= htmlspecialchars($_POST['email']??'') ?>"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX" value="<?= htmlspecialchars($_POST['phone']??'') ?>"></div>
            <div class="form-group"><label>Subject</label>
              <select name="subject">
                <option value="">— Select topic —</option>
                <?php foreach(['Package Inquiry','Booking / Availability','Pricing & Quotation','Corporate Event','Other'] as $t): ?>
                <option value="<?= $t ?>" <?= ($_POST['subject']??'')===$t?'selected':'' ?>><?= $t ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Message * <span id="char_count" style="color:var(--text-light);font-weight:400">0</span> chars</label>
            <textarea id="message" name="message" required style="min-height:160px" placeholder="Tell us about your event, date, number of guests…"><?= htmlspecialchars($_POST['message']??'') ?></textarea>
          </div>
          <button type="submit" class="form-submit">Send Message →</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
