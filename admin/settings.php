<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$adminTitle = 'Settings';
require __DIR__ . '/includes/admin-header.php';

$notice = ''; $error = '';
$DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!db_available()) {
        $error = 'Connect the database first.';
    } else {
        try {
            db_run(
                'UPDATE restaurant_settings SET
                   phone = ?, whatsapp = ?, email = ?, postcode = ?, maps_url = ?,
                   instagram_url = ?, facebook_url = ?,
                   delivery_charge = ?, free_delivery_over = ?, min_order_value = ?,
                   is_accepting_orders = ?, is_accepting_bookings = ?, updated_at = NOW()
                 WHERE id = 1',
                [
                    clean($_POST['phone'] ?? '', 20) ?: null,
                    clean($_POST['whatsapp'] ?? '', 20) ?: null,
                    clean($_POST['email'] ?? '', 160) ?: null,
                    clean($_POST['postcode'] ?? '', 12) ?: null,
                    clean($_POST['maps_url'] ?? '', 500) ?: null,
                    clean($_POST['instagram_url'] ?? '', 255) ?: null,
                    clean($_POST['facebook_url'] ?? '', 255) ?: null,
                    (float) ($_POST['delivery_charge'] ?? 0),
                    (float) ($_POST['free_delivery_over'] ?? 0),
                    (float) ($_POST['min_order_value'] ?? 0),
                    isset($_POST['is_accepting_orders']) ? 1 : 0,
                    isset($_POST['is_accepting_bookings']) ? 1 : 0,
                ]
            );

            db_run('DELETE FROM opening_hours');
            foreach ($DAYS as $day) {
                $closed = isset($_POST['closed'][$day]);
                db_run('INSERT INTO opening_hours (day_of_week, open_time, close_time, is_closed)
                        VALUES (?, ?, ?, ?)',
                       [$day,
                        $closed ? null : (($_POST['open'][$day]  ?? '') ?: null),
                        $closed ? null : (($_POST['close'][$day] ?? '') ?: null),
                        $closed ? 1 : 0]);
            }
            $notice = 'Settings saved.';
        } catch (Throwable $ex) {
            $error = 'Could not save the settings.';
        }
    }
}

$s     = db_one('SELECT * FROM restaurant_settings WHERE id = 1') ?? [];
$hours = [];
foreach (db_all('SELECT * FROM opening_hours') as $h) {
    $hours[$h['day_of_week']] = $h;
}
?>

<?php if ($notice): ?><div class="admin-alert admin-alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error):  ?><div class="admin-alert admin-alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-alert admin-alert--warn">
  These settings are stored in the database. Until the site is switched over to
  read them, <code>config/config.php</code> is still the source for the public
  pages. Change both, or ask your developer to flip the switch once.
</div>

<form method="post" action="<?= e(url('/admin/settings.php')) ?>">
  <?= csrf_field() ?>

  <div class="admin-card">
    <h2>Contact</h2>
    <div class="admin-field"><label for="st-phone">Phone (with country code, e.g. +912212345678)</label>
      <input id="st-phone" name="phone" type="tel" value="<?= e((string) ($s['phone'] ?? '')) ?>"></div>
    <div class="admin-field"><label for="st-wa">WhatsApp (digits only, e.g. 919812345678)</label>
      <input id="st-wa" name="whatsapp" type="tel" value="<?= e((string) ($s['whatsapp'] ?? '')) ?>"></div>
    <div class="admin-field"><label for="st-email">Email</label>
      <input id="st-email" name="email" type="email" value="<?= e((string) ($s['email'] ?? '')) ?>"></div>
    <div class="admin-field"><label for="st-pin">PIN code</label>
      <input id="st-pin" name="postcode" type="text" maxlength="6" value="<?= e((string) ($s['postcode'] ?? '')) ?>"></div>
    <div class="admin-field"><label for="st-maps">Google Maps link</label>
      <input id="st-maps" name="maps_url" type="url" value="<?= e((string) ($s['maps_url'] ?? '')) ?>"></div>
    <div class="admin-field"><label for="st-ig">Instagram URL</label>
      <input id="st-ig" name="instagram_url" type="url" value="<?= e((string) ($s['instagram_url'] ?? '')) ?>"></div>
    <div class="admin-field"><label for="st-fb">Facebook URL</label>
      <input id="st-fb" name="facebook_url" type="url" value="<?= e((string) ($s['facebook_url'] ?? '')) ?>"></div>
  </div>

  <div class="admin-card">
    <h2>Opening hours</h2>
    <table class="admin-table">
      <thead><tr><th>Day</th><th>Opens</th><th>Closes</th><th>Closed</th></tr></thead>
      <tbody>
        <?php foreach ($DAYS as $day):
            $h = $hours[$day] ?? []; ?>
          <tr>
            <td><?= e($day) ?></td>
            <td><input type="time" name="open[<?= e($day) ?>]"
                       value="<?= e(substr((string) ($h['open_time'] ?? ''), 0, 5)) ?>"
                       aria-label="<?= e($day) ?> opening time"></td>
            <td><input type="time" name="close[<?= e($day) ?>]"
                       value="<?= e(substr((string) ($h['close_time'] ?? ''), 0, 5)) ?>"
                       aria-label="<?= e($day) ?> closing time"></td>
            <td><input type="checkbox" name="closed[<?= e($day) ?>]" value="1"
                       <?= !empty($h['is_closed']) ? 'checked' : '' ?>
                       aria-label="<?= e($day) ?> closed all day"></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="admin-card">
    <h2>Ordering</h2>
    <div class="admin-field"><label for="st-dc">Delivery charge (<?= e(APP_CURRENCY_SYMBOL) ?>)</label>
      <input id="st-dc" name="delivery_charge" type="number" step="1" min="0"
             value="<?= e((string) (int) ($s['delivery_charge'] ?? 0)) ?>"></div>
    <div class="admin-field"><label for="st-fd">Free delivery over (<?= e(APP_CURRENCY_SYMBOL) ?>, 0 = off)</label>
      <input id="st-fd" name="free_delivery_over" type="number" step="1" min="0"
             value="<?= e((string) (int) ($s['free_delivery_over'] ?? 0)) ?>"></div>
    <div class="admin-field"><label for="st-min">Minimum order (<?= e(APP_CURRENCY_SYMBOL) ?>, 0 = off)</label>
      <input id="st-min" name="min_order_value" type="number" step="1" min="0"
             value="<?= e((string) (int) ($s['min_order_value'] ?? 0)) ?>"></div>
    <div class="admin-field admin-checkbox">
      <input id="st-orders" name="is_accepting_orders" type="checkbox" value="1"
             <?= !empty($s['is_accepting_orders']) ? 'checked' : '' ?>>
      <label for="st-orders">Accepting online orders</label>
    </div>
    <div class="admin-field admin-checkbox">
      <input id="st-book" name="is_accepting_bookings" type="checkbox" value="1"
             <?= !empty($s['is_accepting_bookings']) ? 'checked' : '' ?>>
      <label for="st-book">Accepting table bookings</label>
    </div>
  </div>

  <div class="admin-sticky-save">
    <button class="admin-btn admin-btn--primary" type="submit"<?= db_available() ? '' : ' disabled' ?>>Save settings</button>
  </div>
</form>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
