<?php
/**
 * Admin account management. Owner-only: staff and managers can sign in but
 * cannot create or remove other admin accounts.
 */
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_owner();

$adminTitle = 'Admins';
require __DIR__ . '/includes/admin-header.php';

$notice = '';
$error  = '';
$me     = admin_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (!db_available()) {
        $error = 'Changes cannot be saved without a database connection.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        try {
            switch ($action) {

                case 'add_admin':
                    $username = clean($_POST['username'] ?? '', 60);
                    $display  = clean($_POST['display_name'] ?? '', 120);
                    $role     = in_array($_POST['role'] ?? '', ['owner', 'manager', 'staff'], true)
                                ? $_POST['role'] : 'staff';
                    $password = (string) ($_POST['password'] ?? '');
                    $confirm  = (string) ($_POST['password_confirm'] ?? '');

                    if (mb_strlen($username) < 3) {
                        throw new RuntimeException('Username must be at least 3 characters.');
                    }
                    if (!preg_match('/^[a-z0-9._-]+$/i', $username)) {
                        throw new RuntimeException('Use only letters, numbers, dots, dashes and underscores.');
                    }
                    if (db_one('SELECT id FROM admins WHERE username = ?', [$username])) {
                        throw new RuntimeException('That username is already taken.');
                    }
                    if (mb_strlen($password) < 12) {
                        throw new RuntimeException('Password must be at least 12 characters.');
                    }
                    if ($password !== $confirm) {
                        throw new RuntimeException('The passwords did not match.');
                    }

                    db_run(
                        'INSERT INTO admins (username, password_hash, display_name, role, created_at)
                         VALUES (?, ?, ?, ?, NOW())',
                        [$username, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                         $display ?: $username, $role]
                    );
                    $notice = "Admin '{$username}' created.";
                    break;

                case 'remove_admin':
                    $id = (int) ($_POST['admin_id'] ?? 0);
                    if ($id === (int) $me['id']) {
                        throw new RuntimeException('You cannot remove your own account.');
                    }
                    $ownerCount = db_one("SELECT COUNT(*) n FROM admins WHERE role = 'owner'");
                    $target     = db_one('SELECT role FROM admins WHERE id = ?', [$id]);
                    if ($target && $target['role'] === 'owner' && (int) ($ownerCount['n'] ?? 0) <= 1) {
                        throw new RuntimeException('At least one owner account must remain.');
                    }
                    db_run('DELETE FROM admins WHERE id = ?', [$id]);
                    $notice = 'Admin removed.';
                    break;

                default:
                    throw new RuntimeException('Unknown action.');
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

$admins = db_available()
    ? db_all('SELECT id, username, display_name, role, last_login, created_at FROM admins ORDER BY created_at')
    : [];
?>

<?php if ($notice): ?><div class="admin-alert admin-alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error):  ?><div class="admin-alert admin-alert--error"><?= e($error) ?></div><?php endif; ?>
<?php if (!db_available()): ?>
  <div class="admin-alert admin-alert--warn">Connect the database to manage admin accounts.</div>
<?php endif; ?>

<div class="admin-card">
  <h2>Current admins</h2>
  <table class="admin-table">
    <thead><tr><th>Username</th><th>Name</th><th>Role</th><th>Last login</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($admins as $a): ?>
        <tr>
          <td><?= e($a['username']) ?><?= (int) $a['id'] === (int) $me['id'] ? ' <span class="admin-muted">(you)</span>' : '' ?></td>
          <td><?= e($a['display_name']) ?></td>
          <td><span class="admin-pill"><?= e(ucfirst($a['role'])) ?></span></td>
          <td class="admin-muted"><?= $a['last_login'] ? e(date('j M Y, H:i', strtotime($a['last_login']))) : 'Never' ?></td>
          <td>
            <?php if ((int) $a['id'] !== (int) $me['id']): ?>
              <form method="post" action="<?= e(url('/admin/admins.php')) ?>"
                    onsubmit="return confirm('Remove admin &quot;<?= e($a['username']) ?>&quot;? This cannot be undone.');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="remove_admin">
                <input type="hidden" name="admin_id" value="<?= (int) $a['id'] ?>">
                <button class="admin-link-btn" type="submit">Remove</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="admin-card">
  <h2>Add an admin</h2>
  <form method="post" action="<?= e(url('/admin/admins.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_admin">

    <div class="admin-field">
      <label for="ad-user">Username</label>
      <input id="ad-user" name="username" type="text" maxlength="60" required autocomplete="off">
    </div>
    <div class="admin-field">
      <label for="ad-name">Display name</label>
      <input id="ad-name" name="display_name" type="text" maxlength="120">
    </div>
    <div class="admin-field">
      <label for="ad-role">Role</label>
      <select id="ad-role" name="role">
        <option value="staff">Staff</option>
        <option value="manager">Manager</option>
        <option value="owner">Owner</option>
      </select>
    </div>
    <div class="admin-field">
      <label for="ad-pass">Password (minimum 12 characters)</label>
      <input id="ad-pass" name="password" type="password" minlength="12" required autocomplete="new-password">
    </div>
    <div class="admin-field">
      <label for="ad-pass2">Confirm password</label>
      <input id="ad-pass2" name="password_confirm" type="password" minlength="12" required autocomplete="new-password">
    </div>
    <button class="admin-btn admin-btn--primary" type="submit"<?= db_available() ? '' : ' disabled' ?>>Create admin</button>
  </form>
  <p class="admin-muted" style="font-size:.8125rem">
    Owners can manage other admins here; managers and staff can sign in but
    won't see this page.
  </p>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
