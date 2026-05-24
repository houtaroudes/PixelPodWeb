<?php
$pageTitle = 'Home';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/image_helper.php';
$pdo = getDB();
$services = $pdo->query("SELECT * FROM services WHERE is_active=1 ORDER BY price ASC LIMIT 6")->fetchAll();
$grads = ['135deg,#6b0f0f,#8b1a1a', '135deg,#4a0808,#c9a84c', '135deg,#8b1a1a,#c94f4f', '135deg,#c9a84c,#e8c97a', '135deg,#4a0808,#8b1a1a', '135deg,#6b0f0f,#c9a84c'];
?>

<section class="hero">
  <div class="hero-container">
    <div class="hero-content">
      <div class="hero-badge">&#10025;Philippines' One of the Best Photobooth Experience</div>
      <h1 class="hero-title">Capture Every <em>Magical</em> Moment</h1>
      <p class="hero-desc">Pixel Pod Photobooth transforms your events into lasting memories. Premium booths, instant prints, and unforgettable experiences for weddings, debuts, and corporate events.</p>
      <div class="hero-cta">
        <a href="<?= SITE_URL ?>/public/booking.php" class="btn-gold">Book Your Event →</a>
        <a href="<?= SITE_URL ?>/public/services.php" class="btn-outline">View Packages</a>
      </div>
      <div class="hero-stats">
        <div>
          <div class="hero-stat-num" data-count="500" data-suffix="+">0+</div>
          <div class="hero-stat-label">Events Done</div>
        </div>
        <div>
          <div class="hero-stat-num" data-count="6">0</div>
          <div class="hero-stat-label">Booth Types</div>
        </div>
        <div>
          <div class="hero-stat-num" data-count="5" data-suffix="★">0★</div>
          <div class="hero-stat-label">Avg Rating</div>
        </div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="hero-photo-stack">
        <div class="hero-photo-card">
          <img
            src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=400&q=80"
            alt="Instant Prints">
          <span class="photo-card-text">Instant Prints</span>
        </div>
        <div class="hero-photo-card">
          <img
            src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=400&q=80"
            alt="360° Booth">
          <span class="photo-card-text">360° Booth</span>
        </div>
        <div class="floating-badge">&#10004; Premium Quality</div>
        <div class="floating-badge">&#128241; Digital Gallery</div>
      </div>
    </div>
</section>

<section style="padding:90px 5%;background:var(--cream)">
  <div class="container">
    <div class="section-header">
      <div class="section-tag">Our Packages</div>
      <h2 class="section-title">Find Your Perfect Booth</h2>
      <p class="section-sub">From intimate gatherings to grand celebrations — we have a photobooth for every occasion.</p>
    </div>
    <div class="services-grid">
      <?php foreach ($services as $i => $s):
        $imgSrc = getServiceImageSrc($s['image'] ?? null, $s['image_type'] ?? null);
      ?>
        <div class="service-card">
          <div class="service-card-img" style="background:linear-gradient(<?= $grads[$i % count($grads)] ?>);padding:0;overflow:hidden">
            <?php if ($imgSrc): ?>
              <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($s['name']) ?>"
                style="width:100%;height:100%;object-fit:cover"
                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <?php endif; ?>
          </div>
          <div class="service-card-body">
            <h3 class="service-card-name"><?= htmlspecialchars($s['name']) ?></h3>
            <p class="service-card-desc"><?= htmlspecialchars(mb_strimwidth($s['description'], 0, 110, '…')) ?></p>
            <div class="service-card-meta">
              <div class="service-price">₱<?= number_format($s['price'], 0) ?><span>/event</span></div>
              <span class="service-duration">⏱ <?= $s['duration_hours'] ?>hrs</span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:40px">
      <a href="<?= SITE_URL ?>/public/services.php" class="btn-primary">View All Packages →</a>
    </div>
  </div>
</section>

<section class="features-section">
  <div class="container">
    <div class="section-header">
      <div class="section-tag" style="color:var(--gold-light);border-color:rgba(201,168,76,.3)">Why Choose Us</div>
      <h2 class="section-title" style="color:#fff">The Pixel Pod Difference</h2>
    </div>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">&#128199;</div>
        <h3>Instant Prints</h3>
        <p>High-quality prints in under 10 seconds so guests take home memories right away.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">&#127912;</div>
        <h3>Custom Designs</h3>
        <p>Personalized templates and backdrops tailored to your event's theme and branding.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">&#128100;</div>
        <h3>Dedicated Attendant</h3>
        <p>Every booking includes a friendly professional attendant to guide your guests.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">&#128241;</div>
        <h3>Digital Gallery</h3>
        <p>All photos uploaded to a private online gallery accessible for 30 days after the event.</p>
      </div>
    </div>
  </div>
</section>

<section class="cta-banner">
  <div class="container">
    <div class="section-tag">Limited Slots Available</div>
    <h2>Ready to Make Memories?</h2>
    <p>Don't miss your date — our calendar fills up fast. Book your Pixel Pod experience today.</p>
    <a href="<?= SITE_URL ?>/public/booking.php" class="btn-primary" style="font-size:1.05rem;padding:16px 44px">Book Now — It's Easy!</a>
  </div>
</section>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>