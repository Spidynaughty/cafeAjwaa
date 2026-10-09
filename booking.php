<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (!BOOKING_ENABLED) {
    redirect('/contact.php');
}

$errors = [];
$sent   = false;
$stored = false;
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $old = [
        'name'    => clean($_POST['name']    ?? '', 60),
        'mobile'  => clean($_POST['mobile']  ?? '', 20),
        'date'    => clean($_POST['date']    ?? '', 10),
        'time'    => clean($_POST['time']    ?? '', 5),
        'guests'  => clean($_POST['guests']  ?? '', 3),
        'request' => clean($_POST['request'] ?? '', 500),
    ];
    $errors = validate_booking($old);

    if (!$errors) {
        if (db_available()) {
            try {
                db_run(
                    'INSERT INTO table_bookings (name, phone, booking_date, booking_time,
                                                 guests, special_request, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                    [
                        $old['name'], normalise_mobile($old['mobile']),
                        $old['date'], $old['time'], (int) $old['guests'],
                        $old['request'] ?: null, 'requested',
                    ]
                );
                $stored = true;
            } catch (Throwable $e) {
                error_log('[cafe-ajwaa] booking failed: ' . $e->getMessage());
            }
        }
        $sent = true;
        $_SESSION['booking_result'] = ['stored' => $stored, 'data' => $old];
        redirect('/booking.php?sent=1');
    }
}

$result = null;
if (isset($_GET['sent'])) {
    $result = $_SESSION['booking_result'] ?? null;
    unset($_SESSION['booking_result']);
    if (!$result) {
        redirect('/booking.php');
    }
}

$pageTitle       = 'Book a table — ' . SITE_NAME;
$pageDescription = 'Request a table at ' . SITE_NAME . ' in Malwani, Malad West. '
                 . 'Tell us the date, time and number of guests.';
$canonicalPath   = '/booking.php';
$pageScripts     = ['forms.js'];

require __DIR__ . '/includes/header.php';

$today = date('Y-m-d');
$max   = date('Y-m-d', strtotime('+60 days'));
?>

<div class="shell section">
  <h1 style="font-size:var(--step-4)">Book a table</h1>

  <?php if ($result): ?>

    <?php if ($result['stored']): ?>
      <div class="alert alert--ok" role="status">
        <p><strong>Request sent.</strong> This is a request, not a confirmed
           booking. Someone from the restaurant will call
           <?= e($result['data']['mobile']) ?> to confirm the table.</p>
      </div>
    <?php else: ?>
      <div class="alert alert--error" role="alert">
        <p><strong>We could not save your request.</strong> Nothing has reached
           the restaurant. Please call or WhatsApp them instead — the details you
           entered are below.</p>
      </div>
    <?php endif; ?>

    <div class="form-card" style="max-width:520px">
      <h2 style="font-size:var(--step-2)">What you asked for</h2>
      <ul class="info-list">
        <li><span><span class="k">Name</span><br><span class="v"><?= e($result['data']['name']) ?></span></span></li>
        <li><span><span class="k">Mobile</span><br><span class="v"><?= e($result['data']['mobile']) ?></span></span></li>
        <li><span><span class="k">Date and time</span><br><span class="v"><?= e(date('D j M Y', strtotime($result['data']['date']))) ?> at <?= e($result['data']['time']) ?></span></span></li>
        <li><span><span class="k">Guests</span><br><span class="v"><?= (int) $result['data']['guests'] ?></span></span></li>
        <?php if ($result['data']['request']): ?>
          <li><span><span class="k">Special request</span><br><span class="v"><?= e($result['data']['request']) ?></span></span></li>
        <?php endif; ?>
      </ul>

      <div style="margin-top:1.5rem;display:grid;gap:.75rem">
        <?php if ($tel = phone_href()): ?>
          <a class="btn btn--block" href="<?= e($tel) ?>">Call the restaurant</a>
        <?php endif; ?>
        <?php
          $waMsg = sprintf(
              'Hello, I would like a table for %d on %s at %s. Name: %s.',
              (int) $result['data']['guests'],
              date('j M', strtotime($result['data']['date'])),
              $result['data']['time'],
              $result['data']['name']
          );
          if ($wa = whatsapp_href($waMsg)):
        ?>
          <a class="btn btn--ghost btn--block" href="<?= e($wa) ?>" rel="noopener">Send on WhatsApp</a>
        <?php endif; ?>
      </div>
    </div>

  <?php else: ?>

    <p style="max-width:52ch">
      Tell us when you would like to come and how many of you there are.
      This sends a request — the restaurant will call you back to confirm.
    </p>

    <?php if ($errors): ?>
      <div class="alert alert--error" role="alert">
        <p><strong>Check the highlighted fields.</strong>
           <?= count($errors) ?> <?= count($errors) === 1 ? 'field needs' : 'fields need' ?> attention.</p>
      </div>
    <?php endif; ?>

    <form class="form-card" style="max-width:600px" method="post" action="<?= e(url('/booking.php')) ?>" data-validate novalidate>
      <?= csrf_field() ?>

      <div class="field-row">
        <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
          <label for="bk-name">Your name</label>
          <input id="bk-name" name="name" type="text" required autocomplete="name"
                 value="<?= e($old['name'] ?? '') ?>">
          <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>
        <div class="field<?= isset($errors['mobile']) ? ' has-error' : '' ?>">
          <label for="bk-mobile">Mobile number</label>
          <input id="bk-mobile" name="mobile" type="tel" required inputmode="numeric"
                 autocomplete="tel-national" maxlength="15"
                 value="<?= e($old['mobile'] ?? '') ?>">
          <?php if (isset($errors['mobile'])): ?><p class="field-error"><?= e($errors['mobile']) ?></p><?php endif; ?>
        </div>
      </div>

      <div class="field-row">
        <div class="field<?= isset($errors['date']) ? ' has-error' : '' ?>">
          <label for="bk-date">Date</label>
          <input id="bk-date" name="date" type="date" required
                 min="<?= e($today) ?>" max="<?= e($max) ?>"
                 value="<?= e($old['date'] ?? '') ?>">
          <?php if (isset($errors['date'])): ?><p class="field-error"><?= e($errors['date']) ?></p><?php endif; ?>
        </div>
        <div class="field<?= isset($errors['time']) ? ' has-error' : '' ?>">
          <label for="bk-time">Time</label>
          <input id="bk-time" name="time" type="time" required
                 value="<?= e($old['time'] ?? '') ?>">
          <?php if (isset($errors['time'])): ?><p class="field-error"><?= e($errors['time']) ?></p><?php endif; ?>
        </div>
      </div>

      <div class="field<?= isset($errors['guests']) ? ' has-error' : '' ?>" style="max-width:200px">
        <label for="bk-guests">Number of guests</label>
        <input id="bk-guests" name="guests" type="number" required min="1" max="30"
               inputmode="numeric" value="<?= e($old['guests'] ?? '2') ?>">
        <?php if (isset($errors['guests'])): ?><p class="field-error"><?= e($errors['guests']) ?></p><?php endif; ?>
      </div>

      <div class="field">
        <label for="bk-request">Special request <span class="hint">optional</span></label>
        <textarea id="bk-request" name="request" rows="3" maxlength="500"
                  placeholder="Birthday, high chair needed, quiet corner…"><?= e($old['request'] ?? '') ?></textarea>
      </div>

      <button class="btn btn--saffron btn--block" type="submit">Send booking request</button>

      <p class="form-note" style="margin-top:1rem">
        Sending this does not confirm a table. The restaurant confirms by phone.
      </p>
    </form>

  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
