<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
http_response_code(404);

$pageTitle       = 'Page not found — ' . SITE_NAME;
$pageDescription = 'That page does not exist.';
$canonicalPath   = '/404.php';
require __DIR__ . '/includes/header.php';
?>
<div class="shell section">
  <div class="empty-state">
    <h1 style="font-size:var(--step-4)">We could not find that page</h1>
    <p>The link may be old, or the address may have a typo in it.</p>
    <div class="btn-row" style="justify-content:center;margin-top:1.5rem">
      <a class="btn" href="<?= e(url('/')) ?>">Go to the home page</a>
      <a class="btn btn--ghost" href="<?= e(url('/menu.php')) ?>">See the menu</a>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
