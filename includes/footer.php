<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-brand">
      <div class="footer-logo">
        <span class="logo-icon">&#128248;</span>
        <span class="logo-text">Pixel <em>Pod</em></span>
      </div>
      <p>Creating unforgettable memories, one snapshot at a time. The Philippines' premier photobooth experience.</p>
    </div>
    <div class="footer-links">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="<?= SITE_URL ?>/public/index.php">Home</a></li>
        <li><a href="<?= SITE_URL ?>/public/services.php">Services</a></li>
        <li><a href="<?= SITE_URL ?>/public/booking.php">Book Now</a></li>
        <li><a href="<?= SITE_URL ?>/public/contact.php">Contact</a></li>
      </ul>
    </div>
    <div class="footer-contact">
      <h4>Get in Touch</h4>
      <p>&#128386; pixelpod.ph@gmail.com</p>
      <p>&#9990; +63 917 130 4683</p>
      <p><i class="fa-regular fa-camera"></i> Instagram: @pixelpod.ph</p>
      <p><i class="fa-brands fa-facebook-f"></i> Facebook: pxlpod.ph</p>
      <div class="footer-social">
        <a href="https://facebook.com/pxlpod.ph" target="_blank" class="social-btn">FB</a>
        <a href="https://instagram.com/pixelpod.ph" target="_blank" class="social-btn">IG</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> Pixel Pod Photobooth. All rights reserved.</p>
  </div>
</footer>
<script src="<?= SITE_URL ?>/public/js/main.js"></script>
<?= $extraScript ?? '' ?>
</body></html>
