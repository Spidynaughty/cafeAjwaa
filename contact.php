<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$errors = [];
$old    = [];
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = [
        'name'    => clean($_POST['name']    ?? '', 60),
        'mobile'  => clean($_POST['mobile']  ?? '', 20),
        'email'   => clean($_POST['email']   ?? '', 120),
        'message' => clean($_POST['message'] ?? '', 1000),
    ];
    $errors = validate_contact($old);

    if (!$errors) {
        $stored = false;
        if (db_available()) {
            try {
                db_run(
                    'INSERT INTO contact_messages (name, phone, email, message, created_at)
                     VALUES (?, ?, ?, ?, NOW())',
                    [$old['name'], normalise_mobile($old['mobile']),
                     $old['email'] ?: null, $old['message']]
                );
                $stored = true;
            } catch (Throwable $e) {
                error_log('[cafe-ajwaa] contact failed: ' . $e->getMessage());
            }
        }
        $_SESSION['contact_result'] = ['stored' => $stored, 'data' => $old];
        redirect('/contact.php?sent=1');
    }
}

if (isset($_GET['sent'])) {
    $result = $_SESSION['contact_result'] ?? null;
    unset($_SESSION['contact_result']);
    if (!$result) redirect('/contact.php');
}

$pageTitle       = 'Contact — ' . SITE_NAME . ', Malwani, Malad West';
$pageDescription = 'Address, phone, WhatsApp and directions for ' . SITE_NAME
                 . ' at Gate 8, Gaikwad Nagar Bus Depot, Malwani Colony, Malad West, Mumbai.';
$canonicalPath   = '/contact.php';
$pageScripts     = ['forms.js'];

require __DIR__ . '/includes/header.php';

$settings = site_settings();
$tel   = phone_href();
$wa    = whatsapp_href('Hello, I have a question about ' . SITE_NAME . '.');
$maps  = maps_href();
$hours = opening_hours_lines();
?>

<div class="shell section">
  <h1 style="font-size:var(--step-4)">Contact us</h1>

  <div class="split" style="align-items:start">

    <div>
      <h2 style="font-size:var(--step-2)"><?= e(SITE_NAME) ?></h2>
      <ul class="info-list">
        <li>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
          <span><span class="k">Address</span><br><span class="v"><?= e(full_address()) ?></span></span>
        </li>
        <li>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg>
          <span><span class="k">Phone</span><br><span class="v">
            <?= $tel ? '<a href="' . e($tel) . '">' . e($settings['phone']) . '</a>' : 'To be confirmed' ?>
          </span></span>
        </li>
        <li>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.5 8.5 0 0 1-3.9-.9L3 20.5l1.6-4.9A8.4 8.4 0 0 1 12.5 3a8.4 8.4 0 0 1 8.5 8.5Z"/></svg>
          <span><span class="k">WhatsApp</span><br><span class="v">
            <?= $wa ? '<a href="' . e($wa) . '" rel="noopener">Message us on WhatsApp</a>' : 'To be confirmed' ?>
          </span></span>
        </li>
        <li>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          <span><span class="k">Opening hours</span><br><span class="v">
            <?php if ($hours): ?>
              <?= e(implode(' · ', $hours)) ?>
            <?php else: ?>
              To be confirmed
            <?php endif; ?>
          </span></span>
        </li>
      </ul>

      <div class="btn-row" style="margin-top:1.5rem">
        <?php if ($tel): ?><a class="btn" href="<?= e($tel) ?>">Call now</a><?php endif; ?>
        <?php if ($wa): ?><a class="btn btn--ghost" href="<?= e($wa) ?>" rel="noopener">WhatsApp</a><?php endif; ?>
      </div>

      <h2 style="font-size:var(--step-2);margin-top:2.5rem">Getting here</h2>
      <?php if ($maps): ?>
        <p>We are at Gate 8 by the Gaikwad Nagar bus depot in Malwani Colony.</p>
        <p><a class="btn btn--ghost" href="<?= e($maps) ?>" rel="noopener">Open in Google Maps</a></p>
      <?php else: ?>
        <div class="alert alert--info">
          <p>The Google Maps link has not been supplied yet, so directions are
             not live on this page. The address above is correct.</p>
        </div>
      <?php endif; ?>
    </div>

    <div>
      <h2 style="font-size:var(--step-2)">Send a message</h2>

      <?php if ($result): ?>
        <?php if ($result['stored']): ?>
          <div class="alert alert--ok" role="status">
            <p><strong>Message sent.</strong> We will get back to you on
               <?= e($result['data']['mobile']) ?>.</p>
          </div>
        <?php else: ?>
          <div class="alert alert--error" role="alert">
            <p><strong>The message could not be saved.</strong> Please call or
               WhatsApp instead — those reach the restaurant directly.</p>
          </div>
        <?php endif; ?>
      <?php else: ?>

        <?php if ($errors): ?>
          <div class="alert alert--error" role="alert">
            <p><strong>Check the highlighted fields.</strong></p>
          </div>
        <?php endif; ?>

        <form class="form-card" method="post" action="<?= e(url('/contact.php')) ?>" data-validate novalidate>
          <?= csrf_field() ?>
          <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
            <label for="ct-name">Your name</label>
            <input id="ct-name" name="name" type="text" required autocomplete="name"
                   value="<?= e($old['name'] ?? '') ?>">
            <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
          </div>
          <div class="field<?= isset($errors['mobile']) ? ' has-error' : '' ?>">
            <label for="ct-mobile">Mobile number</label>
            <input id="ct-mobile" name="mobile" type="tel" required inputmode="numeric"
                   autocomplete="tel-national" value="<?= e($old['mobile'] ?? '') ?>">
            <?php if (isset($errors['mobile'])): ?><p class="field-error"><?= e($errors['mobile']) ?></p><?php endif; ?>
          </div>
          <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
            <label for="ct-email">Email <span class="hint">optional</span></label>
            <input id="ct-email" name="email" type="email" autocomplete="email"
                   value="<?= e($old['email'] ?? '') ?>">
            <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
          </div>
          <div class="field<?= isset($errors['message']) ? ' has-error' : '' ?>">
            <label for="ct-message">Message</label>
            <textarea id="ct-message" name="message" rows="4" required maxlength="1000"><?= e($old['message'] ?? '') ?></textarea>
            <?php if (isset($errors['message'])): ?><p class="field-error"><?= e($errors['message']) ?></p><?php endif; ?>
          </div>
          <button class="btn btn--saffron btn--block" type="submit">Send message</button>
        </form>

      <?php endif; ?>
    </div>

  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
