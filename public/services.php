<?php
$pageTitle = 'Our Services';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/image_helper.php';
$services = getDB()->query("SELECT * FROM services WHERE is_active=1 ORDER BY price ASC")->fetchAll();
$icons = ['📷','🌟','🚶','🌀','🪞','📦'];
$grads = ['135deg,#6b0f0f,#8b1a1a','135deg,#c9a84c,#e8c97a','135deg,#4a0808,#6b0f0f','135deg,#8b1a1a,#c94f4f','135deg,#c9a84c,#4a0808','135deg,#6b0f0f,#c9a84c'];
?>
<div class="page-hero">
  <div class="page-hero-content">
    <div class="section-tag" style="margin-bottom:12px">Our Packages</div>
    <h1>Photobooth Packages</h1>
    <p>Choose the perfect experience for your next event</p>
  </div>
</div>
<section style="padding:80px 5%;background:var(--cream)">
  <div class="container">
    <div class="services-grid">
      <?php foreach($services as $i => $s):
        $imgSrc = getServiceImageSrc($s['image'] ?? null, $s['image_type'] ?? null);
      ?>
      <div class="service-card">
        <div class="service-card-img" style="background:linear-gradient(<?= $grads[$i%count($grads)] ?>);padding:0;overflow:hidden">
          <?php if($imgSrc): ?>
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($s['name']) ?>"
                 style="width:100%;height:100%;object-fit:cover"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-size:3.5rem">
              <?= $icons[$i%count($icons)] ?>
            </div>
          <?php else: ?>
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3.5rem">
              <?= $icons[$i%count($icons)] ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="service-card-body">
          <h3 class="service-card-name"><?= htmlspecialchars($s['name']) ?></h3>
          <p class="service-card-desc"><?= htmlspecialchars($s['description']) ?></p>
          <div style="margin-bottom:16px">
            <?php if($s['max_guests']): ?><span style="font-size:.82rem;color:var(--text-mid)">👥 Up to <?= $s['max_guests'] ?> guests</span><?php endif; ?>
          </div>
          <div class="service-card-meta">
            <div class="service-price">₱<?= number_format($s['price'],0) ?><span>/event</span></div>
            <span class="service-duration">⏱ <?= $s['duration_hours'] ?>hrs</span>
          </div>
          <a href="<?= SITE_URL ?>/public/booking.php?service_id=<?= $s['id'] ?>" class="btn-primary" style="width:100%;justify-content:center;margin-top:16px">Book This Package →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="cta-banner">
  <div class="container">
    <div class="section-tag">Have Questions?</div>
    <h2>Not Sure Which Package?</h2>
    <p>Our team is happy to help you find the perfect booth for your event.</p>
    <a href="<?= SITE_URL ?>/public/contact.php" class="btn-primary" style="font-size:1rem;padding:14px 40px">Contact Us</a>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>