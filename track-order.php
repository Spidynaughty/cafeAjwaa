<?php
/**
 * Order tracking. Public, but a lookup requires both the order number and
 * the mobile number the order was placed with, so one number alone can't be
 * used to browse other people's orders.
 */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$pageTitle       = 'Track your order — ' . SITE_NAME;
$pageDescription = 'Check the current status of an order placed with ' . SITE_NAME . '.';
$canonicalPath   = '/track-order.php';

require __DIR__ . '/includes/header.php';

$STATUS_FLOW = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered'];
$STATUS_LABEL = [
    'pending'          => 'Received',
    'confirmed'        => 'Confirmed',
    'preparing'        => 'Preparing',
    'ready'            => 'Ready',
    'out_for_delivery' => 'Out for delivery',
    'delivered'        => 'Delivered',
];

$orderNumber = clean($_GET['order'] ?? '', 20);
$phoneRaw    = (string) ($_GET['phone'] ?? '');
$looked      = ($orderNumber !== '' && $phoneRaw !== '');

$order = null;
$error = '';

if ($looked) {
    if (!valid_mobile($phoneRaw)) {
        $error = 'Enter the 10-digit mobile number the order was placed with.';
    } elseif (!db_available()) {
        $error = 'Order tracking is temporarily unavailable. Please call the restaurant.';
    } else {
        $order = db_one(
            'SELECT o.order_number, o.order_type, o.status, o.grand_total, o.created_at
               FROM orders o JOIN customers c ON c.id = o.customer_id
              WHERE o.order_number = ? AND c.phone = ?
              LIMIT 1',
            [$orderNumber, normalise_mobile($phoneRaw)]
        );
        if (!$order) {
            $error = 'No order matches that order number and mobile number. Double-check both.';
        }
    }
}
?>

<div class="shell section">
  <h1 style="font-size:var(--step-4)">Track your order</h1>
  <p class="text-mute" style="max-width:34rem">
    Enter your order number (sent when you ordered) and the mobile number you
    used, to see the current status.
  </p>

  <form method="get" action="<?= e(url('/track-order.php')) ?>" class="form-card" style="max-width:26rem;margin:1.5rem 0">
    <div class="field">
      <label for="tr-order">Order number</label>
      <input id="tr-order" name="order" type="text" maxlength="20" required
             autocapitalize="characters" value="<?= e($orderNumber) ?>" placeholder="e.g. AJ2609171A2B">
    </div>
    <div class="field">
      <label for="tr-phone">Mobile number</label>
      <input id="tr-phone" name="phone" type="tel" maxlength="15" required
             value="<?= e($phoneRaw) ?>" placeholder="10-digit mobile number">
    </div>
    <button class="btn btn--saffron btn--block" type="submit">Check status</button>
  </form>

  <?php if ($error): ?>
    <div class="alert alert--error" role="alert"><p><?= e($error) ?></p></div>
  <?php endif; ?>

  <?php if ($order): ?>
    <?php if ($order['status'] === 'cancelled'): ?>
      <div class="alert alert--error" role="alert">
        <p><strong>Order <?= e($order['order_number']) ?> was cancelled.</strong>
           Call the restaurant if you weren't expecting that.</p>
      </div>
    <?php else:
        $flow = $STATUS_FLOW;
        if ($order['order_type'] !== 'delivery') {
            // Pickup orders skip the delivery leg entirely.
            $flow = array_values(array_diff($flow, ['out_for_delivery']));
        }
        $currentIdx = array_search($order['status'], $flow, true);
        $currentIdx = $currentIdx === false ? 0 : $currentIdx;
    ?>
      <div class="alert alert--ok" role="status">
        <p>Order <strong><?= e($order['order_number']) ?></strong> —
           <?= e(money((float) $order['grand_total'])) ?>,
           placed <?= e(date('j M, g:i A', strtotime($order['created_at']))) ?></p>
      </div>

      <ol class="steps" style="flex-wrap:wrap;margin:1.5rem 0">
        <?php foreach ($flow as $i => $s): ?>
          <li class="<?= $i < $currentIdx ? 'is-done' : ($i === $currentIdx ? 'is-current' : '') ?>">
            <?= e($STATUS_LABEL[$s]) ?>
          </li>
        <?php endforeach; ?>
      </ol>

      <p class="text-sm text-mute">
        This page doesn't refresh by itself — reload it any time for the latest status.
      </p>

      <div style="margin-top:1.5rem;display:grid;gap:.75rem;max-width:20rem">
        <?php if ($tel = phone_href()): ?>
          <a class="btn btn--ghost btn--block" href="<?= e($tel) ?>">Call the restaurant</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
