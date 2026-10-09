<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$adminTitle = 'Bookings';
require __DIR__ . '/includes/admin-header.php';

$notice = '';
$STATUSES = ['requested','confirmed','seated','cancelled','no_show'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id     = (int) ($_POST['booking_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');
    $note   = clean($_POST['admin_note'] ?? '', 400);
    if (in_array($status, $STATUSES, true) && db_available()) {
        db_run('UPDATE table_bookings SET status = ?, admin_note = ? WHERE id = ?',
               [$status, $note ?: null, $id]);
        $notice = 'Booking updated.';
    }
}

$bookings = db_all(
    'SELECT * FROM table_bookings
      WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
      ORDER BY booking_date, booking_time LIMIT 200'
);
?>

<?php if ($notice): ?><div class="admin-alert admin-alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!db_available()): ?>
  <div class="admin-alert admin-alert--warn">Connect the database to see bookings.</div>
<?php endif; ?>

<div class="admin-card">
  <p class="admin-muted">
    A request is not a confirmed table. The customer is told on the website that
    the restaurant confirms by phone, so call them before marking one confirmed.
  </p>
</div>

<?php if (!$bookings): ?>
  <div class="admin-card"><p class="admin-muted">No booking requests.</p></div>
<?php else: ?>
  <div class="admin-card">
    <table class="admin-table">
      <thead><tr><th>When</th><th>Guest</th><th>Party</th><th>Request</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?= e(date('D j M', strtotime($b['booking_date']))) ?><br>
                <span class="admin-muted"><?= e(substr((string) $b['booking_time'], 0, 5)) ?></span></td>
            <td><?= e($b['name']) ?><br><a class="admin-muted" href="tel:<?= e($b['phone']) ?>"><?= e($b['phone']) ?></a></td>
            <td><?= (int) $b['guests'] ?></td>
            <td class="admin-muted"><?= e((string) $b['special_request']) ?: '—' ?></td>
            <td>
              <form method="post" action="<?= e(url('/admin/bookings.php')) ?>" class="admin-inline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>">
                <select name="status" aria-label="Status for booking by <?= e($b['name']) ?>">
                  <?php foreach ($STATUSES as $s): ?>
                    <option value="<?= e($s) ?>"<?= $b['status'] === $s ? ' selected' : '' ?>><?= e(str_replace('_',' ',$s)) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="admin-btn" type="submit">Save</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
