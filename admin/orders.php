<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$adminTitle = 'Orders';
require __DIR__ . '/includes/admin-header.php';

$notice = '';
$STATUSES = ['pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id     = (int) ($_POST['order_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');
    if (in_array($status, $STATUSES, true) && db_available()) {
        db_run('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $id]);
        $notice = 'Order status updated.';
    }
}

$q      = clean($_GET['q'] ?? '', 40);
$filter = in_array($_GET['status'] ?? '', $STATUSES, true) ? $_GET['status'] : '';

$sql = 'SELECT o.*, c.name AS customer_name, c.phone
          FROM orders o JOIN customers c ON c.id = o.customer_id
         WHERE 1 = 1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (o.order_number LIKE ? OR c.phone LIKE ? OR c.name LIKE ?)';
    array_push($params, "%{$q}%", "%{$q}%", "%{$q}%");
}
if ($filter !== '') {
    $sql .= ' AND o.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY o.created_at DESC LIMIT 100';

$orders = db_all($sql, $params);
?>

<?php if ($notice): ?><div class="admin-alert admin-alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!db_available()): ?>
  <div class="admin-alert admin-alert--warn">Connect the database to see orders.</div>
<?php endif; ?>

<div class="admin-card">
  <form method="get" action="<?= e(url('/admin/orders.php')) ?>" class="admin-inline-form">
    <div class="admin-field">
      <label for="o-q">Search</label>
      <input id="o-q" name="q" type="search" value="<?= e($q) ?>" placeholder="Order number, name or phone">
    </div>
    <div class="admin-field">
      <label for="o-status">Status</label>
      <select id="o-status" name="status">
        <option value="">All</option>
        <?php foreach ($STATUSES as $s): ?>
          <option value="<?= e($s) ?>"<?= $filter === $s ? ' selected' : '' ?>><?= e(str_replace('_',' ',$s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="admin-btn" type="submit">Filter</button>
  </form>
</div>

<?php if (!$orders): ?>
  <div class="admin-card"><p class="admin-muted">No orders match.</p></div>
<?php else: ?>
  <?php foreach ($orders as $o):
      $lines = db_all('SELECT item_name_snapshot, variant_label_snapshot, quantity, line_total
                         FROM order_items WHERE order_id = ? ORDER BY id', [(int) $o['id']]); ?>
    <div class="admin-card">
      <div class="admin-order-head">
        <div>
          <h2><?= e($o['order_number']) ?> <span class="admin-pill"><?= e(str_replace('_',' ',$o['status'])) ?></span></h2>
          <p class="admin-muted">
            <?= e($o['customer_name']) ?> · <a href="tel:<?= e($o['phone']) ?>"><?= e($o['phone']) ?></a>
            · <?= e(ucfirst($o['order_type'])) ?>
            · <?= e(date('j M Y, H:i', strtotime($o['created_at']))) ?>
          </p>
        </div>
        <form method="post" action="<?= e(url('/admin/orders.php')) ?>" class="admin-inline-form">
          <?= csrf_field() ?>
          <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
          <select name="status" aria-label="Change status of <?= e($o['order_number']) ?>">
            <?php foreach ($STATUSES as $s): ?>
              <option value="<?= e($s) ?>"<?= $o['status'] === $s ? ' selected' : '' ?>><?= e(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="admin-btn" type="submit">Update</button>
        </form>
      </div>

      <?php if ($o['order_type'] === 'delivery'): ?>
        <p class="admin-muted"><?= e((string) $o['address_line']) ?><?= $o['landmark'] ? ', ' . e($o['landmark']) : '' ?> — <?= e((string) $o['pincode']) ?></p>
      <?php endif; ?>
      <?php if ($o['notes']): ?>
        <p><strong>Note:</strong> <?= e($o['notes']) ?></p>
      <?php endif; ?>

      <table class="admin-table">
        <tbody>
          <?php foreach ($lines as $l): ?>
            <tr>
              <td><?= (int) $l['quantity'] ?> × <?= e($l['item_name_snapshot']) ?>
                  <?php if ($l['variant_label_snapshot'] !== 'Regular'): ?>
                    <span class="admin-muted">(<?= e($l['variant_label_snapshot']) ?>)</span>
                  <?php endif; ?></td>
              <td style="text-align:right"><?= e(money((float) $l['line_total'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <tr>
            <td><strong>Total</strong> <span class="admin-muted">(incl. <?= e(money((float) $o['delivery_charge'])) ?> delivery)</span></td>
            <td style="text-align:right"><strong><?= e(money((float) $o['grand_total'])) ?></strong></td>
          </tr>
        </tbody>
      </table>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
