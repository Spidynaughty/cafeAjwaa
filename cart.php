<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (!ORDERING_ENABLED) {
    redirect('/menu.php');
}

/* Clearing the cart is a state change, so it goes through POST + CSRF. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear') {
    csrf_check();
    cart_clear();
    redirect('/cart.php');
}

$summary = cart_summary('delivery');

$pageTitle       = 'Your cart — ' . SITE_NAME;
$pageDescription = 'Review your order from ' . SITE_NAME . ' before checkout.';
$canonicalPath   = '/cart.php';
$pageScripts     = [];

require __DIR__ . '/includes/header.php';
?>

<div class="shell section">
  <h1 style="font-size:var(--step-4)">Your cart</h1>

  <?php if (!$summary['lines']): ?>

    <div class="empty-state" style="margin-top:2rem">
      <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
        <path d="M3 4h2l2.4 11.4a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6"/>
        <circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/>
      </svg>
      <h3>Your cart is empty</h3>
      <p>Add something from the menu and it will show up here.</p>
      <p style="margin-top:1.5rem"><a class="btn" href="<?= e(url('/menu.php')) ?>">Browse the menu</a></p>
    </div>

  <?php else: ?>

    <?php if (SAMPLE_PRICING): ?>
      <div class="alert alert--info" style="margin-top:1.5rem">
        <p><strong>Sample prices.</strong> These totals use placeholder figures
        while the restaurant confirms its real prices. Do not treat them as
        the amount payable.</p>
      </div>
    <?php endif; ?>

    <div class="cart-grid" style="margin-top:1.5rem">

      <div>
        <ul class="cart-lines">
          <?php foreach ($summary['lines'] as $line):
              $ft = food_type_meta($line['food_type']); ?>
            <li class="cart-line">
              <?php if ($line['image']): ?>
                <img class="cart-line-thumb" src="<?= e(url($line['image'])) ?>"
                     alt="" width="56" height="56" loading="lazy" decoding="async">
              <?php else: ?>
                <div class="thumb-fallback" aria-hidden="true">CA</div>
              <?php endif; ?>

              <div>
                <h2 class="cart-line-name"><?= e($line['name']) ?></h2>
                <p class="cart-line-meta" style="margin:0">
                  <span class="ftype <?= e($ft['class']) ?>" aria-hidden="true"
                        style="vertical-align:-2px"></span>
                  <span class="sr-only"><?= e($ft['label']) ?>.</span>
                  <?php if ($line['variant'] !== 'Regular'): ?><?= e($line['variant']) ?> · <?php endif; ?>
                  <?= e(money($line['price'])) ?> each
                </p>
              </div>

              <div class="cart-line-right">
                <div class="qty" data-qty
                     data-item="<?= (int) $line['item_id'] ?>"
                     data-variant="<?= (int) $line['variant_id'] ?>">
                  <button type="button" data-step="-1"
                          aria-label="Decrease quantity of <?= e($line['name']) ?>">−</button>
                  <output aria-label="Quantity"><?= (int) $line['qty'] ?></output>
                  <button type="button" data-step="1"
                          aria-label="Increase quantity of <?= e($line['name']) ?>">+</button>
                </div>
                <span class="cart-line-total"><?= e(money($line['line_total'])) ?></span>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>

        <form method="post" action="<?= e(url('/cart.php')) ?>" style="margin-top:1rem">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="clear">
          <button class="link-remove" type="submit">Clear the cart</button>
        </form>
      </div>

      <aside class="cart-aside">
        <div class="totals">
          <h2 style="font-size:var(--step-2);margin-bottom:1rem">Order total</h2>
          <dl>
            <dt>Subtotal (<?= (int) $summary['count'] ?> items)</dt>
            <dd><?= e(money($summary['subtotal'])) ?></dd>

            <?php if (DELIVERY_ENABLED): ?>
              <dt>Delivery</dt>
              <dd><?= $summary['delivery'] > 0 ? e(money($summary['delivery'])) : 'Free' ?></dd>
            <?php endif; ?>

            <div class="grand" style="display:contents">
              <dt class="grand">Total</dt>
              <dd class="grand"><?= e(money($summary['total'])) ?></dd>
            </div>
          </dl>

          <?php if (!$summary['meets_minimum']): ?>
            <p class="alert alert--info text-sm">
              Add <?= e(money($summary['min_order_value'] - $summary['subtotal'])) ?> more to reach
              the <?= e(money($summary['min_order_value'])) ?> minimum for delivery.
            </p>
          <?php endif; ?>

          <a class="btn btn--saffron btn--block" href="<?= e(url('/checkout.php')) ?>">Continue to details</a>
          <p class="form-note" style="margin:1rem 0 0">
            Delivery charge is applied at checkout once you choose delivery or pickup.
          </p>
        </div>

        <p style="margin-top:1rem"><a href="<?= e(url('/menu.php')) ?>">Add more dishes</a></p>
      </aside>

    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
