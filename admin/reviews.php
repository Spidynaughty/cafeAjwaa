<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$adminTitle = 'Reviews';
require __DIR__ . '/includes/admin-header.php';

$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['review_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if (db_available() && $id) {
        if ($action === 'approve') {
            db_run('UPDATE reviews SET is_approved = 1, approved_at = NOW() WHERE id = ?', [$id]);
            $notice = 'Review approved and now visible.';
        } elseif ($action === 'hide') {
            db_run('UPDATE reviews SET is_approved = 0 WHERE id = ?', [$id]);
            $notice = 'Review hidden.';
        } elseif ($action === 'delete') {
            db_run('DELETE FROM reviews WHERE id = ?', [$id]);
            $notice = 'Review deleted.';
        }
    }
}

$reviews = db_all('SELECT * FROM reviews ORDER BY is_approved, created_at DESC LIMIT 200');
?>

<?php if ($notice): ?><div class="admin-alert admin-alert--ok"><?= e($notice) ?></div><?php endif; ?>

<div class="admin-card">
  <p class="admin-muted">
    No reviews were written for this website. Everything here comes from real
    customers and stays hidden until you approve it.
  </p>
</div>

<div class="admin-card">
  <?php if (!$reviews): ?>
    <p class="admin-muted">No reviews yet.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead><tr><th>Customer</th><th>Rating</th><th>Review</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($reviews as $r): ?>
          <tr>
            <td><?= e($r['customer_name']) ?><br><span class="admin-muted"><?= e(date('j M Y', strtotime($r['created_at']))) ?></span></td>
            <td><?= str_repeat('★', (int) $r['rating']) ?><span class="admin-muted"><?= str_repeat('☆', 5 - (int) $r['rating']) ?></span></td>
            <td><?= e($r['review_text']) ?></td>
            <td><span class="admin-pill"><?= $r['is_approved'] ? 'live' : 'pending' ?></span></td>
            <td>
              <form method="post" action="<?= e(url('/admin/reviews.php')) ?>" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="review_id" value="<?= (int) $r['id'] ?>">
                <input type="hidden" name="action" value="<?= $r['is_approved'] ? 'hide' : 'approve' ?>">
                <button class="admin-link-btn" type="submit"><?= $r['is_approved'] ? 'Hide' : 'Approve' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
