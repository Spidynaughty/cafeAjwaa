<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

/**
 * Photos are read from /assets/images/gallery. Add them either through
 * Admin -> Gallery (upload form), or by dropping files into the folder
 * directly (FTP / file manager) - either way they appear here automatically,
 * the owner never edits code to add a picture.
 *
 * Optional captions live in assets/images/gallery/captions.php as
 * ['filename.jpg' => 'Alt text describing the photo']. Admin -> Gallery
 * reads and rewrites this file too, so it stays in sync either way.
 */
$dir      = APP_ROOT . '/assets/images/gallery';
$files    = glob($dir . '/*.{jpg,jpeg,png,webp,avif}', GLOB_BRACE) ?: [];
sort($files);

$captions = is_file($dir . '/captions.php') ? (require $dir . '/captions.php') : [];

$pageTitle       = 'Gallery — ' . SITE_NAME;
$pageDescription = 'Photographs of the food and the restaurant at ' . SITE_NAME
                 . ', Malwani, Malad West.';
$canonicalPath   = '/gallery.php';
$pageScripts     = ['gallery.js'];

require __DIR__ . '/includes/header.php';
?>

<div class="shell section">
  <h1 style="font-size:var(--step-4)">Gallery</h1>

  <?php if (!$files): ?>

    <div class="empty-state">
      <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
        <rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="m21 16-5-5L5 19"/>
      </svg>
      <h2>No photographs yet</h2>
      <p>The restaurant has not supplied photographs. Once images are added to
         <code>/assets/images/gallery</code> they will appear here automatically,
         with no code change.</p>
      <p style="margin-top:1.5rem"><a class="btn" href="<?= e(url('/menu.php')) ?>">See the menu instead</a></p>
    </div>

  <?php else: ?>

    <p class="text-mute" style="margin-bottom:1.5rem"><?= count($files) ?> photographs. Tap any image to enlarge it.</p>

    <div class="gallery-grid">
      <?php foreach ($files as $i => $path):
          $name = basename($path);
          $alt  = $captions[$name] ?? ('Photograph ' . ($i + 1) . ' from ' . SITE_NAME);
          $src  = url('/assets/images/gallery/' . rawurlencode($name));
      ?>
        <button class="gallery-cell" type="button"
                data-lightbox data-full="<?= e($src) ?>" data-alt="<?= e($alt) ?>"
                aria-label="Enlarge: <?= e($alt) ?>">
          <img src="<?= e($src) ?>" alt="<?= e($alt) ?>"
               width="600" height="600"
               loading="<?= $i < 4 ? 'eager' : 'lazy' ?>" decoding="async">
        </button>
      <?php endforeach; ?>
    </div>

    <div class="lightbox" data-lightbox-root hidden role="dialog" aria-modal="true" aria-label="Photograph viewer">
      <button class="lightbox-btn lightbox-close" type="button" aria-label="Close viewer">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
      <button class="lightbox-btn lightbox-prev" type="button" aria-label="Previous photograph">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
      </button>
      <button class="lightbox-btn lightbox-next" type="button" aria-label="Next photograph">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
      </button>
      <div>
        <img data-lightbox-img alt="">
        <p class="lightbox-cap" data-lightbox-cap></p>
      </div>
    </div>

  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
