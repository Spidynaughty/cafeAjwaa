<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$order = $_SESSION['last_order'] ?? null;
if (!$order) {
    redirect('/menu.php');
}

$pageTitle       = 'Order placed — ' . SITE_NAME;
$pageDescription = 'Your order request has been sent to ' . SITE_NAME . '.';
$canonicalPath   = '/order-confirmation.php';

require __DIR__ . '/includes/header.php';

$waMsg = "Hello, I placed order {$order['number']} on the website.";
?>

<div class="shell section">
  <ol class="steps">
    <li class="is-done">Cart</li>
    <li class="is-done">Your details</li>
    <li class="is-done">Review</li>
    <li class="is-current">Confirmation</li>
  </ol>

  <?php if ($order['stored']): ?>
    <div class="alert alert--ok" role="status">
      <p><strong>Order received.</strong> Your reference is
         <strong><?= e($order['number']) ?></strong>. The restaurant will call you
         on <?= e($order['customer']['mobile']) ?> to confirm the order and the amount.
         It is not confirmed until they call. You can check its status any
         time on the <a href="<?= e(url('/track-order.php?order=' . urlencode($order['number']))) ?>">order tracking page</a>.</p>
    </div>
  <?php else: ?>
    <div class="alert alert--error" role="alert">
      <p><strong>We could not record your order.</strong> Nothing has been sent to
         the kitchen. Please call or message the restaurant directly with the list
         below, and it will be taken straight away.</p>
    </div>
  <?php endif; ?>

  <h1 style="font-size:var(--step-4)">
    <?= $order['stored'] ? 'Thank you' : 'Your order list' ?>
  </h1>

  <div class="cart-grid">
    <div class="form-card">
      <h2 style="font-size:var(--step-2)">What you ordered</h2>
      <ul style="list-style:none;margin:0 0 1.5rem;padding:0">
        <?php foreach ($order['summary']['lines'] as $l): ?>
          <li style="display:flex;justify-content:space-between;gap:1rem;padding-block:.4rem;border-bottom:1px solid var(--sand-dim)">
            <span><?= (int) $l['qty'] ?> × <?= e($l['name']) ?><?php if ($l['variant'] !== 'Regular'): ?> <span class="text-mute">(<?= e($l['variant']) ?>)</span><?php endif; ?></span>
            <span style="font-variant-numeric:tabular-nums"><?= e(money($l['line_total'])) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>

      <dl style="display:grid;grid-template-columns:1fr auto;gap:.5rem 1rem;margin:0">
        <dt>Subtotal</dt><dd style="margin:0;text-align:right"><?= e(money($order['summary']['subtotal'])) ?></dd>
        <dt><?= $order['type'] === 'delivery' ? 'Delivery' : 'Pickup' ?></dt>
        <dd style="margin:0;text-align:right"><?= $order['summary']['delivery'] > 0 ? e(money($order['summary']['delivery'])) : '—' ?></dd>
        <dt style="font-weight:700;font-size:var(--step-2)">Total</dt>
        <dd style="margin:0;text-align:right;font-weight:700;font-size:var(--step-2)"><?= e(money($order['summary']['total'])) ?></dd>
      </dl>

      <?php if (SAMPLE_PRICING): ?>
        <p class="text-sm text-mute" style="margin-top:1rem">
          <span class="badge badge--sample">Sample prices</span>
          The restaurant will confirm the real amount on the phone.
        </p>
      <?php endif; ?>
    </div>

    <aside class="cart-aside">
      <div class="totals">
        <h2 style="font-size:var(--step-2)">
          <?= $order['type'] === 'delivery' ? 'Delivering to' : 'Collecting from' ?>
        </h2>
        <?php if ($order['type'] === 'delivery'): ?>
          <p style="margin:0 0 .5rem"><?= e($order['customer']['name']) ?><br>
             <?= e($order['customer']['address']) ?><br>
             <?php if ($order['customer']['landmark']): ?><?= e($order['customer']['landmark']) ?><br><?php endif; ?>
             <?= e($order['customer']['pincode']) ?></p>
        <?php else: ?>
          <p style="margin:0 0 .5rem"><?= e(full_address()) ?></p>
        <?php endif; ?>
        <p class="text-sm text-mute" style="margin:0"><?= e($order['customer']['mobile']) ?></p>

        <div style="margin-top:1.5rem;display:grid;gap:.75rem">
          <?php if ($tel = phone_href()): ?>
            <a class="btn btn--block" href="<?= e($tel) ?>">Call the restaurant</a>
          <?php endif; ?>
          <?php if ($wa = whatsapp_href($waMsg)): ?>
            <a class="btn btn--ghost btn--block" href="<?= e($wa) ?>" rel="noopener">Send on WhatsApp</a>
          <?php endif; ?>
          <a class="btn btn--ghost btn--block" href="<?= e(url('/menu.php')) ?>">Order something else</a>
        </div>
      </div>
    </aside>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
