<?php
$pageTitle = 'Book Now';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$pdo = getDB();
$services = $pdo->query("SELECT * FROM services WHERE is_active=1 ORDER BY price ASC")->fetchAll();
$pre = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$error = $success = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $sid    = (int)$_POST['service_id'];
    $ename  = sanitize($_POST['event_name']??'');
    $edate  = sanitize($_POST['event_date']??'');
    $stime  = sanitize($_POST['start_time']??'');
    $etime  = sanitize($_POST['end_time']??'');
    $venue  = sanitize($_POST['venue']??'');
    $guests = max(1,(int)($_POST['guest_count']??1));
    $sreq   = sanitize($_POST['special_requests']??'');
    $paymethod = sanitize($_POST['payment_method']??'cod');
    $allowed_methods = ['gcash','maya','cod'];
    if (!in_array($paymethod, $allowed_methods)) $paymethod = 'cod';

    if (!$sid||!$ename||!$edate||!$stime||!$etime) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($edate)<strtotime(date('Y-m-d'))) {
        $error = 'Event date cannot be in the past.';
    } elseif ($etime<=$stime) {
        $error = 'End time must be after start time.';
    } else {
        $c = $pdo->prepare("SELECT id FROM bookings WHERE service_id=? AND event_date=? AND status IN ('pending','approved') AND NOT(end_time<=? OR start_time>=?)");
        $c->execute([$sid,$edate,$stime,$etime]);
        if ($c->fetch()) {
            $error = 'This service is already booked during that time. Please choose a different slot.';
        } else {
            $ref = generateRef();
            $pdo->prepare("INSERT INTO bookings (booking_ref,user_id,service_id,event_name,event_date,start_time,end_time,venue,guest_count,special_requests) VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([$ref,$_SESSION['user_id'],$sid,$ename,$edate,$stime,$etime,$venue,$guests,$sreq]);
            $bid = $pdo->lastInsertId();
            $sp  = $pdo->prepare("SELECT price FROM services WHERE id=?"); $sp->execute([$sid]);
            $pdo->prepare("INSERT INTO payments (booking_id,amount,payment_method,payment_status) VALUES (?,?,?,'pending')")->execute([$bid,$sp->fetchColumn(),$paymethod]);
            $success = "Booking <strong>$ref</strong> submitted! We will confirm within 24 hours.";
        }
    }
}
?>
<div class="page-hero">
  <div class="page-hero-content">
    <div class="section-tag" style="margin-bottom:12px">Reservations</div>
    <h1>Book Your Photobooth</h1>
    <p>Fill in the details below and we'll confirm your slot</p>
  </div>
</div>
<section style="padding:70px 5%;background:var(--cream)">
  <div class="form-wrapper" style="max-width:760px">
    <h2 class="form-title">Event Details</h2>
    <p class="form-sub">All fields marked * are required</p>
    <?php if($error): ?><div class="alert alert-error" data-dismiss><?= $error ?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?= $success ?><br><br><a href="<?= SITE_URL ?>/public/dashboard.php" class="btn-primary">View My Bookings →</a></div>
    <?php else: ?>
    <form method="POST">
      <div class="form-row">
        <div class="form-group">
          <label>Package *</label>
          <select id="service_id" name="service_id" required>
            <option value="">— Select a package —</option>
            <?php foreach($services as $s): ?>
            <option value="<?= $s['id'] ?>" data-duration="<?= $s['duration_hours'] ?>" data-price="<?= $s['price'] ?>" <?= $pre===$s['id']||((int)($_POST['service_id']??0)===$s['id'])?'selected':'' ?>>
              <?= htmlspecialchars($s['name']) ?> — ₱<?= number_format($s['price'],0) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Package Price</label>
          <input type="text" id="price_preview" value="Select a package" readonly style="opacity:.7;cursor:not-allowed">
        </div>
      </div>
      <div class="form-group">
        <label>Event Name *</label>
        <input type="text" name="event_name" required placeholder="e.g. Maria & Juan's Wedding" value="<?= htmlspecialchars($_POST['event_name']??'') ?>">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Event Date *</label>
          <input type="date" id="event_date" name="event_date" required value="<?= htmlspecialchars($_POST['event_date']??'') ?>">
        </div>
        <div class="form-group">
          <label>Expected Guests</label>
          <input type="number" name="guest_count" min="1" max="500" value="<?= (int)($_POST['guest_count']??50) ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Start Time *</label>
          <input type="time" id="start_time" name="start_time" required value="<?= htmlspecialchars($_POST['start_time']??'') ?>">
        </div>
        <div class="form-group">
          <label>End Time *</label>
          <input type="time" id="end_time" name="end_time" required value="<?= htmlspecialchars($_POST['end_time']??'') ?>">
          <small style="color:var(--text-light);font-size:.78rem">Auto-fills based on package duration</small>
        </div>
      </div>
      <div class="form-group">
        <label>Venue / Location</label>
        <input type="text" name="venue" placeholder="e.g. The Grand Ballroom, Makati" value="<?= htmlspecialchars($_POST['venue']??'') ?>">
      </div>
      <div class="form-group">
        <label>Preferred Payment Method *</label>
        <select name="payment_method" required id="payment_method" onchange="showPayInfo(this.value)">
          <option value="">— Select payment method —</option>
          <option value="gcash"  <?= ($_POST['payment_method']??'')==='gcash' ?'selected':'' ?>>💙 GCash</option>
          <option value="maya"   <?= ($_POST['payment_method']??'')==='maya'  ?'selected':'' ?>>💚 Maya</option>
          <option value="cod"    <?= ($_POST['payment_method']??'')==='cod'   ?'selected':'' ?>>💵 Cash on Delivery (COD)</option>
        </select>
      </div>

      <!-- Payment info cards -->
      <div id="pay-info-gcash" class="pay-info-box" style="display:none;background:#e8f4ff;border:1px solid #90cdf4;border-radius:10px;padding:14px 18px;margin-bottom:16px;font-size:.88rem;color:#1e40af">
        💙 <strong>GCash:</strong> Send payment to <strong>0917 130 4683</strong> (Pixel Pod Photobooth). Screenshot your receipt and send to our Facebook or email after booking.
      </div>
      <div id="pay-info-maya" class="pay-info-box" style="display:none;background:#e6fff2;border:1px solid #86efac;border-radius:10px;padding:14px 18px;margin-bottom:16px;font-size:.88rem;color:#166534">
        💚 <strong>Maya:</strong> Send payment to <strong>0917 130 4683</strong> (Pixel Pod Photobooth). Screenshot your receipt and send to our Facebook or email after booking.
      </div>
      <div id="pay-info-cod" class="pay-info-box" style="display:none;background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:14px 18px;margin-bottom:16px;font-size:.88rem;color:#92400e">
        💵 <strong>Cash on Delivery (COD):</strong> Full payment is collected on the day of the event before the booth setup begins.
      </div>
      <div class="form-group">
        <label>Special Requests</label>
        <textarea name="special_requests" placeholder="Custom backdrop, props, themes, etc."><?= htmlspecialchars($_POST['special_requests']??'') ?></textarea>
      </div>
      <button type="submit" class="form-submit">Submit Booking Request →</button>
      <div style="text-align:center;margin-top:16px;font-size:.85rem;color:var(--text-light)">📋 Confirmed within 24 hours via email or phone.</div>
    </form>
    <script>
      
// Auto-fill price when package is selected
document.addEventListener('DOMContentLoaded', function() {

    const packageSelect = document.querySelector('select[name="service_id"]');
    const priceField    = document.getElementById('price_preview');
    const startTime     = document.querySelector('input[name="start_time"]');
    const endTime       = document.querySelector('input[name="end_time"]');

    if (packageSelect) {
        packageSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const price    = selected.getAttribute('data-price');
            const duration = selected.getAttribute('data-duration');

            // Fill price
            if (priceField) {
                if (price && price > 0) {
                    priceField.value = '₱' + parseFloat(price).toLocaleString('en-PH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else {
                    priceField.value = '';
                    priceField.placeholder = 'Select a package first';
                }
            }

            // Auto-fill end time based on duration
            if (startTime && endTime && duration) {
                startTime.addEventListener('change', function() {
                    if (this.value) {
                        const [h, m]  = this.value.split(':').map(Number);
                        const end     = new Date(0, 0, 0, h + parseInt(duration), m);
                        const hh      = String(end.getHours()).padStart(2, '0');
                        const mm      = String(end.getMinutes()).padStart(2, '0');
                        endTime.value = hh + ':' + mm;
                    }
                });
            }
        });
    }
});
</script>
    <?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
