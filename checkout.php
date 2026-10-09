<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (!ORDERING_ENABLED) {
    redirect('/menu.php');
}

$errors = [];
$old    = $_SESSION['checkout_old'] ?? [];
unset($_SESSION['checkout_old']);

$orderType = $old['order_type'] ?? (DELIVERY_ENABLED ? 'delivery' : 'pickup');
$summary   = cart_summary($orderType);

if (!$summary['lines']) {
    redirect('/cart.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $input = [
        'name'       => clean($_POST['name']    ?? '', 60),
        'mobile'     => clean($_POST['mobile']  ?? '', 20),
        'email'      => clean($_POST['email']   ?? '', 120),
        'order_type' => in_array($_POST['order_type'] ?? '', ['delivery', 'pickup'], true)
                        ? $_POST['order_type'] : '',
        'address'    => clean($_POST['address'] ?? '', 255),
        'landmark'   => clean($_POST['landmark'] ?? '', 120),
        'pincode'    => clean($_POST['pincode'] ?? '', 10),
        'notes'      => clean($_POST['notes']   ?? '', 500),
    ];

    $errors  = validate_checkout($input);
    $summary = cart_summary($input['order_type'] ?: 'pickup');

    if ($input['order_type'] === 'delivery' && !$summary['meets_minimum']) {
        $errors['order_type'] = 'Add ' . money($summary['min_order_value'] - $summary['subtotal'])
            . ' more to reach the ' . money($summary['min_order_value']) . ' minimum for delivery.';
    }

    if (!$errors && $summary['lines']) {
        // Re-read every price from the menu; the browser never sets a total.
        $_SESSION['pending_order'] = ['customer' => $input, 'placed_at' => time()];
        $_SESSION['checkout_old']  = $input;
        redirect('/api/place-order.php');
    }

    $old = $input;
    $orderType = $input['order_type'] ?: $orderType;
}

$pageTitle       = 'Checkout — ' . SITE_NAME;
$pageDescription = 'Enter your details to place an order with ' . SITE_NAME . '.';
$canonicalPath   = '/checkout.php';
$pageScripts     = ['forms.js'];

require __DIR__ . '/includes/header.php';

function old_val(array $old, string $k): string
{
    return e((string) ($old[$k] ?? ''));
}
?>

<div class="shell section">

  <ol class="steps">
    <li class="is-done">Cart</li>
    <li class="is-current">Your details</li>
    <li>Review</li>
    <li>Confirmation</li>
  </ol>

  <h1 style="font-size:var(--step-4)">Your details</h1>

  <?php if ($errors): ?>
    <div class="alert alert--error" role="alert">
      <p><strong>Check the highlighted fields.</strong>
         <?= count($errors) ?> <?= count($errors) === 1 ? 'field needs' : 'fields need' ?> attention before we can place the order.</p>
    </div>
  <?php endif; ?>

  <div class="cart-grid">
    <form class="form-card" method="post" action="<?= e(url('/checkout.php')) ?>" data-validate novalidate>
      <?= csrf_field() ?>

      <div class="field-row">
        <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
          <label for="co-name">Your name</label>
          <input id="co-name" name="name" type="text" required autocomplete="name"
                 value="<?= old_val($old, 'name') ?>"
                 <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="co-name-error"' : '' ?>>
          <?php if (isset($errors['name'])): ?>
            <p class="field-error" id="co-name-error"><?= e($errors['name']) ?></p>
          <?php endif; ?>
        </div>

        <div class="field<?= isset($errors['mobile']) ? ' has-error' : '' ?>">
          <label for="co-mobile">Mobile number</label>
          <input id="co-mobile" name="mobile" type="tel" required inputmode="numeric"
                 autocomplete="tel-national" maxlength="15"
                 value="<?= old_val($old, 'mobile') ?>"
                 <?= isset($errors['mobile']) ? 'aria-invalid="true" aria-describedby="co-mobile-error"' : '' ?>>
          <?php if (isset($errors['mobile'])): ?>
            <p class="field-error" id="co-mobile-error"><?= e($errors['mobile']) ?></p>
          <?php endif; ?>
        </div>
      </div>

      <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
        <label for="co-email">Email <span class="hint">optional</span></label>
        <input id="co-email" name="email" type="email" autocomplete="email"
               value="<?= old_val($old, 'email') ?>">
        <?php if (isset($errors['email'])): ?>
          <p class="field-error"><?= e($errors['email']) ?></p>
        <?php endif; ?>
      </div>

      <fieldset style="border:0;padding:0;margin:0 0 1rem">
        <legend style="font-weight:600;margin-bottom:.5rem">How would you like it?</legend>
        <div class="choice-grid">
          <?php if (DELIVERY_ENABLED): ?>
            <label class="choice">
              <input type="radio" name="order_type" value="delivery"
                     <?= $orderType === 'delivery' ? 'checked' : '' ?>>
              <span class="choice-title">Delivery</span>
              <span class="choice-note"><?= $summary['delivery'] > 0 ? e(money($summary['delivery'])) . ' delivery charge' : 'No delivery charge' ?></span>
            </label>
          <?php endif; ?>
          <?php if (PICKUP_ENABLED): ?>
            <label class="choice">
              <input type="radio" name="order_type" value="pickup"
                     <?= $orderType === 'pickup' ? 'checked' : '' ?>>
              <span class="choice-title">Pickup</span>
              <span class="choice-note">Collect from the restaurant</span>
            </label>
          <?php endif; ?>
        </div>
        <?php if (isset($errors['order_type'])): ?>
          <p class="field-error"><?= e($errors['order_type']) ?></p>
        <?php endif; ?>
      </fieldset>

      <div data-address-block>
        <div class="field<?= isset($errors['address']) ? ' has-error' : '' ?>">
          <label for="co-address">Delivery address</label>
          <textarea id="co-address" name="address" rows="3" data-rule="address"
                    autocomplete="street-address"><?= old_val($old, 'address') ?></textarea>
          <?php if (isset($errors['address'])): ?>
            <p class="field-error"><?= e($errors['address']) ?></p>
          <?php endif; ?>
        </div>

        <div class="field-row">
          <div class="field">
            <label for="co-landmark">Landmark <span class="hint">optional</span></label>
            <input id="co-landmark" name="landmark" type="text" data-optional="1"
                   value="<?= old_val($old, 'landmark') ?>">
          </div>
          <div class="field<?= isset($errors['pincode']) ? ' has-error' : '' ?>">
            <label for="co-pincode">PIN code</label>
            <input id="co-pincode" name="pincode" type="text" inputmode="numeric"
                   maxlength="6" autocomplete="postal-code"
                   value="<?= old_val($old, 'pincode') ?>">
            <?php if (isset($errors['pincode'])): ?>
              <p class="field-error"><?= e($errors['pincode']) ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="field">
        <label for="co-notes">Note for the kitchen <span class="hint">optional</span></label>
        <textarea id="co-notes" name="notes" rows="2" maxlength="500"
                  placeholder="Less spicy, no onion, ring the bell…"><?= old_val($old, 'notes') ?></textarea>
      </div>

      <button class="btn btn--saffron btn--block" type="submit">Review and place order</button>

      <p class="form-note" style="margin-top:1rem">
        No payment is taken on this website. The restaurant will call you on the
        number above to confirm the order and the amount.
      </p>
    </form>

    <aside class="cart-aside">
      <div class="totals">
        <h2 style="font-size:var(--step-2);margin-bottom:1rem">Order summary</h2>
        <ul style="list-style:none;margin:0 0 1rem;padding:0">
          <?php foreach ($summary['lines'] as $l): ?>
            <li style="display:flex;justify-content:space-between;gap:1rem;padding-block:.25rem;font-size:var(--step--1)">
              <span><?= (int) $l['qty'] ?> × <?= e($l['name']) ?><?php if ($l['variant'] !== 'Regular'): ?> <span class="text-mute">(<?= e($l['variant']) ?>)</span><?php endif; ?></span>
              <span style="font-variant-numeric:tabular-nums"><?= e(money($l['line_total'])) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <dl>
          <dt>Subtotal</dt><dd><?= e(money($summary['subtotal'])) ?></dd>
          <dt>Delivery</dt><dd><?= $summary['delivery'] > 0 ? e(money($summary['delivery'])) : '—' ?></dd>
          <div style="display:contents">
            <dt class="grand">Total</dt><dd class="grand"><?= e(money($summary['total'])) ?></dd>
          </div>
        </dl>
        <?php if (SAMPLE_PRICING): ?>
          <p class="text-sm text-mute" style="margin:0">
            <span class="badge badge--sample">Sample prices</span>
          </p>
        <?php endif; ?>
      </div>
      <p style="margin-top:1rem"><a href="<?= e(url('/cart.php')) ?>">Edit the cart</a></p>
    </aside>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
