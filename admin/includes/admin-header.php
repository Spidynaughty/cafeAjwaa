<?php
/** Admin chrome. Every admin page except login.php requires a session. */
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
}
require_once APP_ROOT . '/includes/auth.php';
require_admin();

$adminTitle = $adminTitle ?? 'Dashboard';
$user       = admin_user();

$adminNav = [
    '/admin/dashboard.php' => 'Dashboard',
    '/admin/orders.php'    => 'Orders',
    '/admin/bookings.php'  => 'Bookings',
    '/admin/menu.php'      => 'Menu',
    '/admin/gallery.php'   => 'Gallery',
    '/admin/offers.php'    => 'Offers',
    '/admin/reviews.php'   => 'Reviews',
    '/admin/settings.php'  => 'Settings',
];
if (admin_is_owner()) {
    $adminNav['/admin/admins.php'] = 'Admins';
}
$current = '/admin/' . basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($adminTitle) ?> — <?= e(SITE_NAME) ?> admin</title>
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">

<header class="admin-top">
  <a class="admin-brand" href="<?= e(url('/admin/dashboard.php')) ?>"><?= e(SITE_NAME) ?> <span>admin</span></a>
  <div class="admin-top-right">
    <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View site</a>
    <span class="admin-user"><?= e($user['username']) ?></span>
    <form method="post" action="<?= e(url('/admin/logout.php')) ?>" style="display:inline">
      <?= csrf_field() ?>
      <button class="admin-link-btn" type="submit">Log out</button>
    </form>
  </div>
</header>

<div class="admin-shell">
  <nav class="admin-nav" aria-label="Admin sections">
    <ul>
      <?php foreach ($adminNav as $href => $label): ?>
        <li><a href="<?= e(url($href)) ?>"<?= $href === $current ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>
  <main class="admin-main">
    <h1><?= e($adminTitle) ?></h1>
