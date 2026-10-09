<?php
/** Site footer, sticky mobile cart bar and scripts. */
declare(strict_types=1);

$settings = site_settings();
$tel   = phone_href();
$wa    = whatsapp_href('Hello, I would like to order from ' . SITE_NAME . '.');
$maps  = maps_href();
$hours = opening_hours_lines();
$summary = ORDERING_ENABLED ? cart_summary() : ['count' => 0, 'subtotal' => 0.0];
?>
</main>

<?php if (ORDERING_ENABLED): ?>
<!-- Sticky mobile cart summary. Hidden until the cart has something in it. -->
<div class="cart-bar" data-cart-bar data-visible="<?= $summary['count'] > 0 ? 'true' : 'false' ?>" role="region" aria-label="Cart summary">
  <div class="shell">
    <span class="cart-bar-info">
      <span class="cart-bar-count"><span data-cart-bar-count><?= (int) $summary['count'] ?></span> items</span>
      <span class="cart-bar-total" data-cart-bar-total><?= e(money((float) $summary['subtotal'])) ?></span>
    </span>
    <a class="btn" href="<?= e(url('/cart.php')) ?>">View cart</a>
  </div>
</div>
<?php endif; ?>

<footer class="site-footer">
  <div class="shell">
    <div class="footer-grid">

      <div>
        <h3><?= e(SITE_NAME) ?></h3>
        <p><?= e(SITE_NAME_LOCAL) ?></p>
        <p>Tandoor, biryani, Indian main course and Chinese, cooked to order in
           Malwani, Malad West.</p>
        <?php if ($settings['instagram_url'] || $settings['facebook_url']): ?>
          <ul>
            <?php if ($settings['instagram_url']): ?><li><a href="<?= e($settings['instagram_url']) ?>" rel="noopener">Instagram</a></li><?php endif; ?>
            <?php if ($settings['facebook_url']): ?><li><a href="<?= e($settings['facebook_url']) ?>" rel="noopener">Facebook</a></li><?php endif; ?>
          </ul>
        <?php endif; ?>
      </div>

      <nav aria-label="Footer">
        <h3>Quick links</h3>
        <ul>
          <li><a href="<?= e(url('/')) ?>">Home</a></li>
          <li><a href="<?= e(url('/menu.php')) ?>">Menu</a></li>
          <li><a href="<?= e(url('/about.php')) ?>">About</a></li>
          <li><a href="<?= e(url('/gallery.php')) ?>">Gallery</a></li>
          <li><a href="<?= e(url('/contact.php')) ?>">Contact</a></li>
          <?php if (BOOKING_ENABLED): ?><li><a href="<?= e(url('/booking.php')) ?>">Book a table</a></li><?php endif; ?>
          <li><a href="<?= e(url('/track-order.php')) ?>">Track an order</a></li>
        </ul>
      </nav>

      <div>
        <h3>Find us</h3>
        <p><?= e(full_address()) ?></p>
        <ul>
          <li>
            <?php if ($tel): ?>
              <a href="<?= e($tel) ?>"><?= e($settings['phone']) ?></a>
            <?php else: ?>
              Phone number to be confirmed
            <?php endif; ?>
          </li>
          <li>
            <?php if ($wa): ?>
              <a href="<?= e($wa) ?>" rel="noopener">WhatsApp</a>
            <?php else: ?>
              WhatsApp number to be confirmed
            <?php endif; ?>
          </li>
          <?php if ($maps): ?><li><a href="<?= e($maps) ?>" rel="noopener">Get directions</a></li><?php endif; ?>
        </ul>
        <h3 style="margin-top:1.5rem">Opening hours</h3>
        <?php if ($hours): ?>
          <ul><?php foreach ($hours as $line): ?><li><?= e($line) ?></li><?php endforeach; ?></ul>
        <?php else: ?>
          <p>Opening hours to be confirmed.</p>
        <?php endif; ?>
      </div>

    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</span>
      <span>Malvani, Malad West, Mumbai</span>
    </div>
  </div>
</footer>

<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php foreach (($pageScripts ?? []) as $s): ?>
<script src="<?= e(asset('js/' . $s)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
