<?php
/**
 * Menu management. The owner changes a price here, never in source code.
 */
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$adminTitle = 'Menu';
require __DIR__ . '/includes/admin-header.php';

$notice = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (!db_available()) {
        $error = 'Changes cannot be saved without a database connection.';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        try {
            switch ($action) {

                /* Bulk price update: the fastest way to enter all 97 prices. */
                case 'save_prices':
                    $prices = $_POST['price'] ?? [];
                    $n = 0;
                    foreach ($prices as $variantId => $raw) {
                        $raw = trim((string) $raw);
                        $value = ($raw === '') ? null : round((float) $raw, 2);
                        if ($value !== null && ($value < 0 || $value > 99999)) {
                            continue;
                        }
                        db_run('UPDATE menu_item_variants SET price = ? WHERE id = ?',
                               [$value, (int) $variantId]);
                        $n++;
                    }
                    $notice = "{$n} prices saved.";
                    break;

                case 'toggle_flag':
                    $id    = (int) ($_POST['item_id'] ?? 0);
                    $field = (string) ($_POST['field'] ?? '');
                    // Whitelist: the column name can never come from the request.
                    $allowed = ['is_bestseller', 'is_spicy', 'is_active'];
                    if (!in_array($field, $allowed, true)) {
                        throw new RuntimeException('Unknown field');
                    }
                    db_run("UPDATE menu_items SET {$field} = 1 - {$field}, updated_at = NOW() WHERE id = ?", [$id]);
                    $notice = 'Item updated.';
                    break;

                case 'save_item':
                    $id = (int) ($_POST['item_id'] ?? 0);
                    $name = clean($_POST['name'] ?? '', 160);
                    $desc = clean($_POST['description'] ?? '', 400);
                    $type = in_array($_POST['food_type'] ?? '', ['veg', 'nonveg', 'egg'], true)
                            ? $_POST['food_type'] : 'veg';
                    if ($name === '') {
                        throw new RuntimeException('The dish needs a name.');
                    }
                    db_run('UPDATE menu_items SET name = ?, description = ?, food_type = ?, updated_at = NOW()
                             WHERE id = ?', [$name, $desc, $type, $id]);
                    $notice = 'Dish updated.';
                    break;

                case 'add_category':
                    $name = clean($_POST['cat_name'] ?? '', 120);
                    if ($name === '') {
                        throw new RuntimeException('The category needs a name.');
                    }
                    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
                    $maxRow = db_one('SELECT COALESCE(MAX(display_order), 0) m FROM categories');
                    $max = $maxRow ? (int) $maxRow['m'] : 0;
                    db_run('INSERT INTO categories (name, slug, display_order, is_active) VALUES (?, ?, ?, 1)',
                           [$name, $slug, $max + 1]);
                    $notice = 'Category added.';
                    break;

                /* Create a brand-new dish inside an existing category. */
                case 'add_item':
                    $catId = (int) ($_POST['category_id'] ?? 0);
                    $name  = clean($_POST['name'] ?? '', 160);
                    $desc  = clean($_POST['description'] ?? '', 400);
                    $type  = in_array($_POST['food_type'] ?? '', ['veg', 'nonveg', 'egg'], true)
                             ? $_POST['food_type'] : 'veg';
                    $priceRaw = trim((string) ($_POST['price'] ?? ''));
                    $price    = ($priceRaw === '') ? null : round((float) $priceRaw, 2);

                    if ($name === '') {
                        throw new RuntimeException('The dish needs a name.');
                    }
                    if (!db_one('SELECT id FROM categories WHERE id = ?', [$catId])) {
                        throw new RuntimeException('Choose a category for the dish.');
                    }
                    if ($price !== null && ($price < 0 || $price > 99999)) {
                        throw new RuntimeException('That price does not look right.');
                    }

                    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
                    if ($slug === '') {
                        $slug = 'dish';
                    }
                    // Slugs are unique across all items, so a repeated name gets a
                    // short random tail rather than failing the save.
                    if (db_one('SELECT id FROM menu_items WHERE slug = ?', [$slug])) {
                        $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
                    }

                    $maxRow = db_one(
                        'SELECT COALESCE(MAX(display_order), 0) m FROM menu_items WHERE category_id = ?',
                        [$catId]
                    );
                    $max = $maxRow ? (int) $maxRow['m'] : 0;

                    db_run(
                        'INSERT INTO menu_items (category_id, name, slug, description, food_type,
                                                  is_active, display_order, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())',
                        [$catId, $name, $slug, $desc, $type, $max + 1]
                    );
                    $newItemId = (int) db()->lastInsertId();

                    // Every dish needs at least one variant to show a price row;
                    // "Regular" with no price yet is fine - it renders as "On request".
                    db_run(
                        'INSERT INTO menu_item_variants (menu_item_id, label, price, is_default, display_order)
                         VALUES (?, ?, ?, 1, 0)',
                        [$newItemId, 'Regular', $price]
                    );
                    $notice = 'Dish added. Set its price above if you did not already.';
                    break;

                case 'upload_image':
                    $id = (int) ($_POST['item_id'] ?? 0);
                    $notice = handle_food_image($id);
                    break;

                default:
                    throw new RuntimeException('Unknown action.');
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

/**
 * Store an uploaded food photograph.
 * Checks the real image type rather than trusting the filename or the
 * browser-supplied MIME type, and always writes a generated filename.
 */
function handle_food_image(int $itemId): string
{
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No file was uploaded.');
    }
    $tmp  = $_FILES['photo']['tmp_name'];
    $size = (int) $_FILES['photo']['size'];

    if ($size > 4 * 1024 * 1024) {
        throw new RuntimeException('Keep images under 4 MB.');
    }
    $info = @getimagesize($tmp);
    if ($info === false) {
        throw new RuntimeException('That file is not an image.');
    }
    $ext = match ($info[2]) {
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        default        => throw new RuntimeException('Use a JPG, PNG or WebP image.'),
    };

    $filename = 'item-' . $itemId . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest     = APP_ROOT . '/uploads/food/' . $filename;

    if (!move_uploaded_file($tmp, $dest)) {
        throw new RuntimeException('The file could not be saved.');
    }
    @chmod($dest, 0644);

    $item = db_one('SELECT name FROM menu_items WHERE id = ?', [$itemId]);
    db_run('UPDATE menu_item_images SET is_primary = 0 WHERE menu_item_id = ?', [$itemId]);
    db_run('INSERT INTO menu_item_images (menu_item_id, file_path, alt_text, is_primary, uploaded_at)
            VALUES (?, ?, ?, 1, NOW())',
           [$itemId, '/uploads/food/' . $filename,
            ($item['name'] ?? 'Dish') . ' at ' . SITE_NAME]);

    return 'Photograph uploaded.';
}

$menu   = load_menu();
$filter = clean($_GET['cat'] ?? '', 120);

// The dish form always needs the real categories table, not load_menu()'s
// sample-data fallback (which kicks in whenever menu_items is still empty -
// i.e. exactly the situation someone adding their first dish is in).
$dbCategories = db_available()
    ? db_all('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY display_order, name')
    : [];
?>

<?php if ($notice): ?><div class="admin-alert admin-alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error):  ?><div class="admin-alert admin-alert--error"><?= e($error) ?></div><?php endif; ?>

<?php if (!db_available()): ?>
  <div class="admin-alert admin-alert--warn">
    Read-only. Connect the database to edit prices and dishes.
  </div>
<?php endif; ?>

<div class="admin-card">
  <h2>Prices</h2>
  <p class="admin-muted">
    Type the price for each portion and press Save. Leave a box empty to show
    "On request" on the website instead of a number.
  </p>

  <form method="post" action="<?= e(url('/admin/menu.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_prices">

    <?php foreach ($menu['categories'] as $c):
        $rows = items_in($menu, (int) $c['id']);
        if (!$rows) continue;
        if ($filter && $filter !== $c['slug']) continue; ?>

      <h3 class="admin-subhead"><?= e($c['name']) ?> <span class="admin-muted">(<?= count($rows) ?>)</span></h3>
      <div class="admin-table-scroll">
      <table class="admin-table admin-table--prices">
        <thead>
          <tr><th>Photo</th><th>Dish</th><th>Type</th><th>Portion</th><th>Price (<?= e(APP_CURRENCY_SYMBOL) ?>)</th><th>Flags</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $item):
            $ft  = food_type_meta($item['food_type']);
            $vs  = $item['variants'];
            $iid = (int) $item['id'];
            $rowspan = max(1, count($vs)); ?>
          <?php foreach ($vs as $vi => $v): ?>
            <tr>
              <?php if ($vi === 0): ?>
                <td rowspan="<?= $rowspan ?>" class="admin-photo-cell">
                  <?php if (!empty($item['image'])): ?>
                    <img class="admin-thumb" src="<?= e(url($item['image'])) ?>"
                         alt="<?= e($item['image_alt'] ?: $item['name']) ?>"
                         width="48" height="48" loading="lazy" decoding="async">
                  <?php else: ?>
                    <div class="admin-thumb admin-thumb--empty" aria-hidden="true">No photo</div>
                  <?php endif; ?>
                  <div class="admin-upload-row">
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                           form="photo-form-<?= $iid ?>"
                           aria-label="Upload photo for <?= e($item['name']) ?>"
                           <?= db_available() ? '' : 'disabled' ?>>
                    <button class="admin-btn admin-btn--small" type="submit" form="photo-form-<?= $iid ?>"
                            <?= db_available() ? '' : 'disabled' ?>>Upload</button>
                  </div>
                </td>
                <td rowspan="<?= $rowspan ?>">
                  <strong><?= e($item['name']) ?></strong>
                  <?php if (!empty($item['source_name']) && $item['source_name'] !== $item['name']): ?>
                    <br><span class="admin-muted" style="font-size:.75rem">listed as: <?= e($item['source_name']) ?></span>
                  <?php endif; ?>
                </td>
                <td rowspan="<?= $rowspan ?>"><span class="admin-pill"><?= e($ft['short']) ?></span></td>
              <?php endif; ?>
              <td><?= e($v['label']) ?></td>
              <td>
                <input class="admin-price" type="number" step="1" min="0" max="99999"
                       name="price[<?= (int) $v['id'] ?>]"
                       value="<?= $v['price'] === null ? '' : e((string) (int) $v['price']) ?>"
                       aria-label="Price for <?= e($item['name'] . ' ' . $v['label']) ?>">
              </td>
              <?php if ($vi === 0): ?>
                <td rowspan="<?= $rowspan ?>" class="admin-flags-cell">
                  <button type="submit" form="flag-best-form-<?= $iid ?>"
                          class="admin-flag-btn<?= !empty($item['is_bestseller']) ? ' is-on' : '' ?>"
                          <?= db_available() ? '' : 'disabled' ?>>
                    <?= !empty($item['is_bestseller']) ? '★ Bestseller' : '☆ Mark bestseller' ?>
                  </button>
                  <button type="submit" form="flag-spicy-form-<?= $iid ?>"
                          class="admin-flag-btn<?= !empty($item['is_spicy']) ? ' is-on' : '' ?>"
                          <?= db_available() ? '' : 'disabled' ?>>
                    <?= !empty($item['is_spicy']) ? '🌶 Spicy' : '🌶 Mark spicy' ?>
                  </button>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endforeach; ?>

    <div class="admin-sticky-save">
      <button class="admin-btn admin-btn--primary" type="submit"<?= db_available() ? '' : ' disabled' ?>>
        Save all prices
      </button>
    </div>
  </form>

  <?php // One tiny form per dish for photo uploads and flag toggles. Kept outside
        // the big price-save form above (forms cannot nest) and wired to the
        // buttons in the table via the HTML "form" attribute. ?>
  <?php foreach ($menu['items'] as $it): $iid = (int) $it['id']; ?>
    <form id="photo-form-<?= $iid ?>" class="admin-hidden-form" method="post"
          enctype="multipart/form-data" action="<?= e(url('/admin/menu.php')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="upload_image">
      <input type="hidden" name="item_id" value="<?= $iid ?>">
    </form>
    <form id="flag-best-form-<?= $iid ?>" class="admin-hidden-form" method="post"
          action="<?= e(url('/admin/menu.php')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="toggle_flag">
      <input type="hidden" name="item_id" value="<?= $iid ?>">
      <input type="hidden" name="field" value="is_bestseller">
    </form>
    <form id="flag-spicy-form-<?= $iid ?>" class="admin-hidden-form" method="post"
          action="<?= e(url('/admin/menu.php')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="toggle_flag">
      <input type="hidden" name="item_id" value="<?= $iid ?>">
      <input type="hidden" name="field" value="is_spicy">
    </form>
  <?php endforeach; ?>
</div>

<div class="admin-card">
  <h2>Add a dish</h2>
  <?php if (!$dbCategories): ?>
    <p class="admin-muted">Add a category first (below), then come back here to add dishes to it.</p>
  <?php else: ?>
  <form method="post" action="<?= e(url('/admin/menu.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_item">

    <div class="admin-field">
      <label for="it-cat">Category</label>
      <select id="it-cat" name="category_id" required>
        <?php foreach ($dbCategories as $c): ?>
          <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="admin-field">
      <label for="it-name">Dish name</label>
      <input id="it-name" name="name" type="text" maxlength="160" required>
    </div>
    <div class="admin-field">
      <label for="it-desc">Description (optional)</label>
      <input id="it-desc" name="description" type="text" maxlength="400">
    </div>
    <div class="admin-field">
      <label for="it-type">Type</label>
      <select id="it-type" name="food_type">
        <option value="veg">Vegetarian</option>
        <option value="nonveg">Non-vegetarian</option>
        <option value="egg">Contains egg</option>
      </select>
    </div>
    <div class="admin-field">
      <label for="it-price">Price (<?= e(APP_CURRENCY_SYMBOL) ?>, optional)</label>
      <input id="it-price" name="price" type="number" step="1" min="0" max="99999"
             placeholder="Leave blank for &quot;On request&quot;">
    </div>
    <button class="admin-btn admin-btn--primary" type="submit"<?= db_available() ? '' : ' disabled' ?>>Add dish</button>
  </form>
  <p class="admin-muted" style="font-size:.8125rem">
    New dishes get one "Regular" portion to start. Add more portions (Half/Full,
    sizes, etc.) straight in the database, or ask your developer to add a
    portions editor here.
  </p>
  <?php endif; ?>
</div>

<div class="admin-card">
  <h2>Add a category</h2>
  <form method="post" action="<?= e(url('/admin/menu.php')) ?>" class="admin-inline-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_category">
    <div class="admin-field">
      <label for="cat-name">Category name</label>
      <input id="cat-name" name="cat_name" type="text" maxlength="120" required>
    </div>
    <button class="admin-btn" type="submit"<?= db_available() ? '' : ' disabled' ?>>Add</button>
  </form>
  <p class="admin-muted" style="font-size:.8125rem">
    The website has no Beverages category because the supplied menu had none.
    Add it here if the restaurant serves drinks.
  </p>
</div>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
