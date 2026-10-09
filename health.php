<?php
/**
 * Install check. Open http://localhost/cafe-ajwaa/health.php
 *
 * Answers the only question that matters when a fresh install misbehaves:
 * which specific thing is wrong. Delete this file before going live.
 */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$checks = [];

$checks[] = [
    'PHP version',
    version_compare(PHP_VERSION, '8.0.0', '>='),
    PHP_VERSION,
    'Needs PHP 8.0 or newer. In XAMPP this is set by the version you installed.',
];

$checks[] = [
    'PDO MySQL driver',
    extension_loaded('pdo_mysql'),
    extension_loaded('pdo_mysql') ? 'loaded' : 'missing',
    'Enable extension=pdo_mysql in php.ini, then restart Apache from the XAMPP panel.',
];

$checks[] = [
    'config/database.php',
    is_file(APP_ROOT . '/config/database.php'),
    is_file(APP_ROOT . '/config/database.php') ? 'present' : 'not found',
    'Copy config/database.sample.php to config/database.php.',
];

$dbOk  = db_available();
$dbMsg = 'connected';
if (!$dbOk) {
    $dbMsg = is_file(APP_ROOT . '/config/database.php')
        ? 'could not connect - check the database name, user and password'
        : 'no config file';
}
$checks[] = [
    'Database connection',
    $dbOk,
    $dbMsg,
    'Confirm MySQL is running in XAMPP and that the database in config/database.php exists.',
];

$tables = [];
if ($dbOk) {
    foreach (db_all('SHOW TABLES') as $row) {
        $tables[] = (string) reset($row);
    }
}
$wanted = ['categories', 'menu_items', 'menu_item_variants', 'orders',
           'order_items', 'customers', 'table_bookings', 'admins'];
$missingTables = array_diff($wanted, $tables);
$checks[] = [
    'Schema imported',
    $dbOk && !$missingTables,
    $dbOk ? (count($tables) . ' tables' . ($missingTables ? ' — missing: ' . implode(', ', $missingTables) : '')) : 'not checked',
    'Import sql/schema.sql in phpMyAdmin.',
];

$itemCount = 0;
if ($dbOk && !in_array('menu_items', $missingTables, true)) {
    $row = db_one('SELECT COUNT(*) c FROM menu_items');
    $itemCount = $row ? (int) $row['c'] : 0;
}
$checks[] = [
    'Menu data seeded',
    $itemCount === 97,
    $dbOk ? ($itemCount . ' of 97 dishes') : 'not checked',
    'Import sql/seed.sql after schema.sql.',
];

$adminCount = 0;
if ($dbOk && !in_array('admins', $missingTables, true)) {
    $row = db_one('SELECT COUNT(*) c FROM admins');
    $adminCount = $row ? (int) $row['c'] : 0;
}
$checks[] = [
    'Admin account',
    $adminCount > 0,
    $adminCount . ' account' . ($adminCount === 1 ? '' : 's'),
    'Run: php sql/create-admin.php  (from the project folder, using XAMPP\'s php.exe)',
];

$checks[] = [
    'Uploads writable',
    is_writable(APP_ROOT . '/uploads/food'),
    is_writable(APP_ROOT . '/uploads/food') ? 'writable' : 'read-only',
    'Only needed for uploading dish photos through the admin panel.',
];

$rewrite = function_exists('apache_get_modules')
    ? in_array('mod_rewrite', apache_get_modules(), true)
    : null;
$checks[] = [
    'mod_rewrite',
    $rewrite !== false,
    $rewrite === null ? 'could not detect' : ($rewrite ? 'enabled' : 'disabled'),
    'Only affects the custom 404 page. Everything else works without it.',
];

$passed = count(array_filter($checks, static fn($c) => $c[1] === true));
$total  = count($checks);
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Install check — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">
<header class="admin-top">
  <span class="admin-brand"><?= e(SITE_NAME) ?> <span>install check</span></span>
  <div class="admin-top-right"><a href="<?= e(url('/')) ?>">Open the site</a></div>
</header>

<main class="admin-main">
  <h1><?= $passed ?> of <?= $total ?> checks passed</h1>

  <div class="admin-card">
    <h2>Where the site thinks it lives</h2>
    <table class="admin-table">
      <tbody>
        <tr><td>Detected base path</td><td><code><?= e(BASE_PATH === '' ? '(web root)' : BASE_PATH) ?></code></td></tr>
        <tr><td>A link will look like</td><td><code><?= e(url('/menu.php')) ?></code></td></tr>
        <tr><td>Stylesheet URL</td><td><code><?= e(asset('css/site.css')) ?></code></td></tr>
        <tr><td>Project folder</td><td><code><?= e(APP_ROOT) ?></code></td></tr>
        <tr><td>Document root</td><td><code><?= e((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')) ?></code></td></tr>
      </tbody>
    </table>
    <p class="admin-muted" style="margin-bottom:0">
      If the base path is wrong, set <code>BASE_PATH_OVERRIDE</code> in
      <code>config/config.php</code> to the folder name, for example
      <code>'/cafe-ajwaa'</code>.
    </p>
  </div>

  <div class="admin-card">
    <h2>Checks</h2>
    <table class="admin-table">
      <thead><tr><th>Check</th><th>Result</th><th>If it fails</th></tr></thead>
      <tbody>
        <?php foreach ($checks as [$label, $ok, $value, $help]): ?>
          <tr>
            <td><strong><?= e($label) ?></strong></td>
            <td>
              <span class="admin-pill" style="background:<?= $ok ? '#e4f1e9' : '#fbeaea' ?>;color:<?= $ok ? '#1d5537' : '#7d1f1f' ?>">
                <?= $ok ? 'ok' : 'check' ?>
              </span>
              <br><span class="admin-muted"><?= e($value) ?></span>
            </td>
            <td class="admin-muted"><?= $ok ? '—' : e($help) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="admin-card">
    <p class="admin-muted" style="margin:0">
      Delete <code>health.php</code> before putting the site on a live server.
    </p>
  </div>
</main>
</body>
</html>
