<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require APP_ROOT . '/includes/auth.php';

if (admin_logged_in()) {
    redirect('/admin/dashboard.php');
}

$error = '';
$wait  = login_throttle_wait();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($wait > 0) {
        $error = "Too many attempts. Try again in {$wait} seconds.";
    } elseif (!db_available()) {
        $error = 'The database is not configured, so logging in is not possible yet.';
    } else {
        $username = clean($_POST['username'] ?? '', 60);
        $password = (string) ($_POST['password'] ?? '');

        if (admin_attempt($username, $password)) {
            login_clear_failures();
            redirect('/admin/dashboard.php');
        }
        login_record_failure();
        // Deliberately vague: never reveal whether the username exists.
        $error = 'Those details did not match an account.';
    }
}

$pageTitle = 'Admin login';
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin login — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin admin-login-page">
  <main class="admin-login">
    <h1><?= e(SITE_NAME) ?></h1>
    <p class="admin-muted">Staff sign in</p>

    <?php if ($error): ?>
      <div class="admin-alert admin-alert--error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/admin/login.php')) ?>">
      <?= csrf_field() ?>
      <div class="admin-field">
        <label for="lg-user">Username</label>
        <input id="lg-user" name="username" type="text" required autocomplete="username" autofocus>
      </div>
      <div class="admin-field">
        <label for="lg-pass">Password</label>
        <input id="lg-pass" name="password" type="password" required autocomplete="current-password">
      </div>
      <button class="admin-btn admin-btn--primary" type="submit" style="width:100%">Sign in</button>
    </form>

    <p class="admin-muted" style="margin-top:1.5rem;font-size:.8125rem">
      No account yet? Run <code>php sql/create-admin.php</code> on the server.
    </p>
  </main>
</body>
</html>
