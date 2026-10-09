<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$adminTitle = 'Dashboard';
require __DIR__ . '/includes/admin-header.php';

$menu = load_menu();

/** COUNT(*) helper that returns 0 rather than erroring when there is no DB. */
function count_of(string $sql): int
{
    $row = db_one($sql);
    return $row ? (int) $row['c'] : 0;
}

$stats = [
    'orders_today'   => count_of("SELECT COUNT(*) c FROM orders WHERE DATE(created_at) = CURDATE()"),
    'orders_pending' => count_of("SELECT COUNT(*) c FROM orders WHERE status = 'pending'"),
    'bookings_open'  => count_of("SELECT COUNT(*) c FROM table_bookings WHERE status = 'requested' AND booking_date >= CURDATE()"),
    'messages_new'   => count_of("SELECT COUNT(*) c FROM contact_messages WHERE is_read = 0"),
];

$noPrice = 0;
foreach ($menu['items'] as $i) {
    if (item_from_price($i) === null) $noPrice++;
}

$recent = db_all(
    'SELECT o.order_number, o.grand_total, o.status, o.created_at, c.name, c.phone
       FROM orders o JOIN customers c ON c.id = o.customer_id
      ORDER BY o.created_at DESC LIMIT 8'
);
?>

<?php if (!db_available()): ?>
  <div class="admin-alert admin-alert--warn">
    No database connection. The menu below is read from <code>data/menu-data.php</code>.
    Orders, bookings and edits cannot be saved until <code>config/database.php</code> is set up.
  </div>
<?php endif; ?>

<?php if (SAMPLE_PRICING): ?>
  <div class="admin-alert admin-alert--warn">
    <strong>Sample pricing is switched on.</strong>
    Every price on the public site is a placeholder and is labelled as such.
    Enter the real prices under <a href="<?= e(url('/admin/menu.php')) ?>">Menu</a>, then set
    <code>SAMPLE_PRICING</code> to <code>false</code> in <code>config/config.php</code>.
  </div>
<?php endif; ?>

<div class="admin-stats">
  <div class="admin-stat"><span class="n"><?= (int) $stats['orders_today'] ?></span><span class="l">Orders today</span></div>
  <div class="admin-stat"><span class="n"><?= (int) $stats['orders_pending'] ?></span><span class="l">Awaiting confirmation</span></div>
  <div class="admin-stat"><span class="n"><?= (int) $stats['bookings_open'] ?></span><span class="l">Booking requests</span></div>
  <div class="admin-stat"><span class="n"><?= (int) $stats['messages_new'] ?></span><span class="l">Unread messages</span></div>
</div>

<div class="admin-card">
  <h2>Menu at a glance</h2>
  <p class="admin-muted">
    <?= count($menu['items']) ?> dishes in <?= count($menu['categories']) ?> categories.
    <?= $noPrice > 0 ? $noPrice . ' have no price set.' : 'All dishes have a price.' ?>
  </p>
  <p><a class="admin-btn" href="<?= e(url('/admin/menu.php')) ?>">Manage the menu</a></p>
</div>

<div class="admin-card">
  <h2>Recent orders</h2>
  <?php if (!$recent): ?>
    <p class="admin-muted">No orders yet.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Placed</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><a href="<?= e(url('/admin/orders.php')) ?>?q=<?= e(urlencode($r['order_number'])) ?>"><?= e($r['order_number']) ?></a></td>
            <td><?= e($r['name']) ?><br><span class="admin-muted"><?= e($r['phone']) ?></span></td>
            <td><?= e(money((float) $r['grand_total'])) ?></td>
            <td><span class="admin-pill"><?= e(str_replace('_', ' ', $r['status'])) ?></span></td>
            <td class="admin-muted"><?= e(date('j M, H:i', strtotime($r['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
