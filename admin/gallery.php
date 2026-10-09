<?php
/**
 * Gallery management. Lets the owner add/remove photographs from the
 * public /gallery.php page without touching files by hand or code.
 *
 * Storage is deliberately still just files in /assets/images/gallery -
 * gallery.php already reads that folder directly, so this page is a thin,
 * validated front end onto the same folder rather than a new data source.
 * Captions are kept in assets/images/gallery/captions.php (an array file),
 * rewritten here whenever a caption changes.
 */
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$adminTitle = 'Gallery';
require __DIR__ . '/includes/admin-header.php';

$notice = '';
$error  = '';

$galleryDir   = APP_ROOT . '/assets/images/gallery';
$captionsFile = $galleryDir . '/captions.php';

/** Read the current caption map, or [] if none has been saved yet. */
function gallery_load_captions(string $captionsFile): array
{
    return is_file($captionsFile) ? (require $captionsFile) : [];
}

/** Rewrite captions.php from an in-memory array. */
function gallery_save_captions(string $captionsFile, array $captions): void
{
    $body = "<?php\n"
          . "/**\n"
          . " * Alt text for gallery photographs.\n"
          . " * Keys are the exact filenames inside assets/images/gallery/.\n"
          . " * Maintained from Admin -> Gallery - edits here are overwritten.\n"
          . " */\n"
          . 'return ' . var_export($captions, true) . ";\n";
    file_put_contents($captionsFile, $body, LOCK_EX);
}

/** A filename that came back from our own <input type="file">, never a path. */
function gallery_safe_filename(string $file): string
{
    $file = basename($file);
    if ($file === '' || $file === '.' || $file === '..') {
        throw new RuntimeException('Invalid file.');
    }
    return $file;
}

function handle_gallery_upload(string $dir, string $captionsFile): string
{
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Choose a photo to upload - it may also be too large for the server to accept.');
    }
    $tmp  = $_FILES['photo']['tmp_name'];
    $size = (int) $_FILES['photo']['size'];

    if ($size > 6 * 1024 * 1024) {
        throw new RuntimeException('Keep images under 6 MB.');
    }
    $info = @getimagesize($tmp);
    if ($info === false) {
        throw new RuntimeException('That file is not an image.');
    }
    // Real image type, not the filename or the browser-supplied MIME type.
    $ext = match ($info[2]) {
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        default        => throw new RuntimeException('Use a JPG, PNG or WebP image.'),
    };

    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('The gallery folder could not be created.');
    }

    $filename = 'gallery-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    $dest     = $dir . '/' . $filename;

    if (!move_uploaded_file($tmp, $dest)) {
        throw new RuntimeException('The file could not be saved.');
    }
    @chmod($dest, 0644);

    $caption = clean($_POST['caption'] ?? '', 200);
    if ($caption !== '') {
        $captions = gallery_load_captions($captionsFile);
        $captions[$filename] = $caption;
        gallery_save_captions($captionsFile, $captions);
    }

    return 'Photograph uploaded.';
}

function handle_gallery_caption(string $captionsFile): string
{
    $file    = gallery_safe_filename((string) ($_POST['file'] ?? ''));
    $caption = clean($_POST['caption'] ?? '', 200);

    $captions = gallery_load_captions($captionsFile);
    if ($caption === '') {
        unset($captions[$file]);
    } else {
        $captions[$file] = $caption;
    }
    gallery_save_captions($captionsFile, $captions);

    return 'Caption saved.';
}

function handle_gallery_delete(string $dir, string $captionsFile): string
{
    $file = gallery_safe_filename((string) ($_POST['file'] ?? ''));
    $path = $dir . '/' . $file;

    if (!is_file($path)) {
        throw new RuntimeException('That photograph no longer exists.');
    }
    if (!unlink($path)) {
        throw new RuntimeException('The file could not be deleted.');
    }

    $captions = gallery_load_captions($captionsFile);
    unset($captions[$file]);
    gallery_save_captions($captionsFile, $captions);

    return 'Photograph removed.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    try {
        switch ($action) {
            case 'upload':
                $notice = handle_gallery_upload($galleryDir, $captionsFile);
                break;
            case 'update_caption':
                $notice = handle_gallery_caption($captionsFile);
                break;
            case 'delete':
                $notice = handle_gallery_delete($galleryDir, $captionsFile);
                break;
            default:
                throw new RuntimeException('Unknown action.');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$files = glob($galleryDir . '/*.{jpg,jpeg,png,webp,avif}', GLOB_BRACE) ?: [];
sort($files);
$captions = gallery_load_captions($captionsFile);
?>

<?php if ($notice): ?><div class="admin-alert admin-alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error):  ?><div class="admin-alert admin-alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="admin-card">
  <h2>Add a photograph</h2>
  <p class="admin-muted">
    Appears on the public
    <a href="<?= e(url('/gallery.php')) ?>" target="_blank" rel="noopener">gallery page</a>
    right after upload. JPG, PNG or WebP, up to 6 MB.
  </p>
  <form method="post" action="<?= e(url('/admin/gallery.php')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="upload">
    <div class="admin-field">
      <label for="gal-photo">Photo</label>
      <input id="gal-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>
    </div>
    <div class="admin-field">
      <label for="gal-caption">Caption (optional)</label>
      <input id="gal-caption" name="caption" type="text" maxlength="200"
             placeholder="e.g. Chicken tikka fresh off the tandoor">
    </div>
    <button class="admin-btn admin-btn--primary" type="submit">Upload photograph</button>
  </form>
</div>

<div class="admin-card">
  <h2>Current photographs <span class="admin-muted">(<?= count($files) ?>)</span></h2>

  <?php if (!$files): ?>
    <p class="admin-muted">No photographs yet. Upload one above and it will appear here and on the gallery page.</p>
  <?php else: ?>
    <div class="admin-gallery-grid">
      <?php foreach ($files as $path): $name = basename($path); ?>
        <div class="admin-gallery-item">
          <img src="<?= e(url('/assets/images/gallery/' . rawurlencode($name))) ?>"
               alt="<?= e($captions[$name] ?? $name) ?>" loading="lazy" decoding="async">

          <form method="post" action="<?= e(url('/admin/gallery.php')) ?>" class="admin-gallery-caption-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_caption">
            <input type="hidden" name="file" value="<?= e($name) ?>">
            <label class="sr-only" for="cap-<?= e($name) ?>">Caption for <?= e($name) ?></label>
            <input id="cap-<?= e($name) ?>" type="text" name="caption" maxlength="200"
                   value="<?= e($captions[$name] ?? '') ?>" placeholder="Caption / alt text">
            <button class="admin-btn admin-btn--small" type="submit">Save</button>
          </form>

          <form method="post" action="<?= e(url('/admin/gallery.php')) ?>"
                onsubmit="return confirm('Remove this photograph?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="file" value="<?= e($name) ?>">
            <button class="admin-link-btn" type="submit">Delete</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="admin-card">
  <h2>Bulk / FTP method</h2>
  <p class="admin-muted" style="font-size:.8125rem">
    Photos can also be dropped straight into <code>/assets/images/gallery</code>
    via file manager or FTP - useful for adding many at once. Anything placed
    there shows up here and on the gallery page automatically; captions can
    still be added or edited above.
  </p>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
