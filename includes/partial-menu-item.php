<?php
/**
 * One menu item row. Used by index.php and menu.php so the markup exists once.
 *
 * Expects: $item (from load_menu()). Optional: $categoryName.
 */
declare(strict_types=1);

$ft        = food_type_meta($item['food_type']);
$variants  = $item['variants'] ?? [];
$hasPrice  = item_from_price($item) !== null;
$firstVar  = $variants[0] ?? null;
$searchKey = strtolower($item['name'] . ' ' . ($categoryName ?? '') . ' ' . ($item['description'] ?? ''));
?>
<li class="menu-item"
    data-menu-item
    data-name="<?= e($searchKey) ?>"
    data-type="<?= e($item['food_type']) ?>"
    data-best="<?= !empty($item['is_bestseller']) ? '1' : '0' ?>">

  <div class="menu-item-info">
    <h3 class="menu-item-name"><?= e($item['name']) ?></h3>

    <div class="menu-item-tags">
      <span class="ftype-line">
        <span class="ftype <?= e($ft['class']) ?>" aria-hidden="true"></span>
        <span class="sr-only"><?= e($ft['label']) ?>.</span>
        <span class="ftype-text"><?= e($ft['short']) ?></span>
      </span>
      <?php if (!empty($item['is_bestseller'])): ?>
        <span class="badge badge--best">Bestseller</span>
      <?php endif; ?>
      <?php if (!empty($item['is_spicy'])): ?>
        <span class="badge badge--spicy">Spicy</span>
      <?php endif; ?>
    </div>

    <?php if (!empty($item['description'])): ?>
      <p class="menu-item-desc"><?= e($item['description']) ?></p>
    <?php endif; ?>
  </div>

  <div class="menu-item-media">
    <?php if (!empty($item['image'])): ?>
      <img class="menu-item-thumb"
           src="<?= e(url($item['image'])) ?>"
           alt="<?= e($item['image_alt'] ?: $item['name'] . ' at ' . SITE_NAME) ?>"
           width="120" height="120" loading="lazy" decoding="async">
    <?php else: ?>
      <div class="thumb-fallback" aria-hidden="true">CA</div>
    <?php endif; ?>
  </div>

  <div class="menu-item-foot">
    <ul class="price-list">
      <?php foreach ($variants as $v): ?>
        <li class="price-row">
          <?php if (count($variants) > 1): ?>
            <span class="price-label"><?= e($v['label']) ?></span>
            <span class="price-lead" aria-hidden="true"></span>
          <?php endif; ?>
          <span class="price-value<?= $v['price'] === null ? ' is-empty' : '' ?>">
            <?php if ($v['price'] === null): ?>
              On request
            <?php else: ?>
              <?= e(money((float) $v['price'])) ?><?php if (SAMPLE_PRICING): ?><span class="sr-only"> (sample price)</span><?php endif; ?>
            <?php endif; ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if (ORDERING_ENABLED && $hasPrice && $firstVar): ?>
      <div class="menu-item-actions">
        <?php if (count($variants) > 1): ?>
          <button class="add-btn" type="button"
                  data-open-variants="<?= (int) $item['id'] ?>"
                  data-variants="<?= ejson($variants) ?>"
                  data-item-name="<?= e($item['name']) ?>">Add</button>
        <?php else: ?>
          <button class="add-btn" type="button"
                  data-add
                  data-item="<?= (int) $item['id'] ?>"
                  data-variant="<?= (int) $firstVar['id'] ?>"
                  data-price="<?= e((string) $firstVar['price']) ?>"
                  data-item-name="<?= e($item['name']) ?>">Add</button>
        <?php endif; ?>
      </div>
    <?php elseif (ORDERING_ENABLED && !$hasPrice && ($wa = whatsapp_href('Hello, what is the price of ' . $item['name'] . '?'))): ?>
      <div class="menu-item-actions">
        <a class="add-btn" href="<?= e($wa) ?>" rel="noopener">Ask</a>
      </div>
    <?php endif; ?>
  </div>
</li>
