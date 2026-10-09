<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$adminTitle = 'Offers';
require __DIR__ . '/includes/admin-header.php';

$notice = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!db_available()) {
        $error = 'Connect the database first.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'create') {
            $title = clean($_POST['title'] ?? '', 160);
            if ($title === '') {
                $error = 'An offer needs a title.';
            } else {
                db_run('INSERT INTO offers (title, description, terms, valid_from, valid_to,
                                            cta_label, cta_url, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())',
                       [$title,
                        clean($_POST['description'] ?? '', 500),
                        clean($_POST['terms'] ?? '', 600),
                        ($_POST['valid_from'] ?? '') ?: null,
                        ($_POST['valid_to'] ?? '') ?: null,
                        clean($_POST['cta_label'] ?? '', 60) ?: null,
                        clean($_POST['cta_url'] ?? '', 255) ?: null]);
                $notice = 'Offer created. It is now visible on the website.';
            }
        } elseif ($action === 'toggle') {
            db_run('UPDATE offers SET is_active = 1 - is_active WHERE id = ?',
                   [(int) ($_POST['offer_id'] ?? 0)]);
            $notice = 'Offer updated.';
        } elseif ($action === 'delete') {
            db_run('DELETE FROM offers WHERE id = ?', [(int) ($_POST['offer_id'] ?? 0)]);
            $notice = 'Offer deleted.';
        }
    }
}

$offers = db_all('SELECT * FROM offers ORDER BY created_at DESC');
?>

<?php if ($notice): ?><div class="admin-alert admin-alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error):  ?><div class="admin-alert admin-alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
  <p class="admin-muted">
    There are no offers on the website because the restaurant supplied none.
    Nothing was invented. Add a real offer below and an Offers link appears in
    the site navigation automatically.
  </p>
</div>

<?php if ($offers): ?>
  <div class="admin-card">
    <h2>Current offers</h2>
    <table class="admin-table">
      <thead><tr><th>Title</th><th>Valid</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($offers as $o): ?>
          <tr>
            <td><strong><?= e($o['title']) ?></strong><br><span class="admin-muted"><?= e($o['description']) ?></span></td>
            <td class="admin-muted">
              <?= $o['valid_from'] ? e(date('j M', strtotime($o['valid_from']))) : 'now' ?> –
              <?= $o['valid_to'] ? e(date('j M Y', strtotime($o['valid_to']))) : 'open' ?>
            </td>
            <td><span class="admin-pill"><?= $o['is_active'] ? 'live' : 'hidden' ?></span></td>
            <td>
              <form method="post" action="<?= e(url('/admin/offers.php')) ?>" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="offer_id" value="<?= (int) $o['id'] ?>">
                <button class="admin-link-btn" type="submit"><?= $o['is_active'] ? 'Hide' : 'Show' ?></button>
              </form>
              <form method="post" action="<?= e(url('/admin/offers.php')) ?>" style="display:inline"
                    onsubmit="return confirm('Delete this offer?')">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="offer_id" value="<?= (int) $o['id'] ?>">
                <button class="admin-link-btn" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<div class="admin-card">
  <h2>Add an offer</h2>
  <form method="post" action="<?= e(url('/admin/offers.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div class="admin-field"><label for="of-title">Title</label>
      <input id="of-title" name="title" type="text" maxlength="160" required></div>
    <div class="admin-field"><label for="of-desc">Description</label>
      <textarea id="of-desc" name="description" rows="2" maxlength="500"></textarea></div>
    <div class="admin-field"><label for="of-terms">Terms</label>
      <textarea id="of-terms" name="terms" rows="2" maxlength="600"></textarea></div>
    <div class="admin-field"><label for="of-from">Valid from</label>
      <input id="of-from" name="valid_from" type="date"></div>
    <div class="admin-field"><label for="of-to">Valid to</label>
      <input id="of-to" name="valid_to" type="date"></div>
    <button class="admin-btn admin-btn--primary" type="submit"<?= db_available() ? '' : ' disabled' ?>>Create offer</button>
  </form>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
