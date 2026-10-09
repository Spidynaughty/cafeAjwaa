<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$menu    = load_menu();
$hasBest = has_bestsellers($menu);

$pageTitle       = 'Menu — ' . SITE_NAME . ', Malad West';
$pageDescription = 'Full menu for ' . SITE_NAME . ': tandoori and barbeque, chicken and '
                 . 'paneer main course, biryani, pulao, fried rice, noodles, roti and naan. '
                 . count($menu['items']) . ' dishes with prices.';
$canonicalPath   = '/menu.php';
$pageScripts     = ['menu.js'];

require __DIR__ . '/includes/header.php';
?>

<div class="shell" style="padding-top:2rem" id="order">
  <h1 style="font-size:var(--step-4);margin-bottom:.5rem">Menu</h1>
  <p class="text-mute" style="margin-bottom:1.25rem">
    <?= count($menu['items']) ?> dishes across <?= count($menu['categories']) ?> sections.
    <?php if (SAMPLE_PRICING): ?>
      <br><span class="badge badge--sample">Sample prices</span>
      Prices shown are placeholders pending confirmation from the restaurant.
    <?php endif; ?>
  </p>
</div>

<div data-menu-root
     data-admin-phrase="<?= e(ADMIN_ENTRY_PHRASE) ?>"
     data-admin-url="<?= e(url('/admin/login.php')) ?>">

  <!-- ------------------------------------------------------ filter bar -->
  <div class="filter-bar">
    <div class="shell">
      <div class="filter-row">

        <div class="search-field">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
          </svg>
          <label class="sr-only" for="menu-search">Search the menu</label>
          <input id="menu-search" type="search" data-search
                 placeholder="Search dishes" autocomplete="off"
                 enterkeyhint="search" inputmode="search">
          <button class="search-clear" type="button" data-search-clear aria-label="Clear search" hidden>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="diet-filter" role="group" aria-label="Filter by food type">
          <button type="button" data-diet="veg" aria-pressed="false">
            <span class="ftype is-veg" aria-hidden="true"></span>
            <span class="label-long">Veg</span><span class="sr-only">Show vegetarian only</span>
          </button>
          <button type="button" data-diet="nonveg" aria-pressed="false">
            <span class="ftype is-nonveg" aria-hidden="true"></span>
            <span class="label-long">Non-veg</span><span class="sr-only">Show non-vegetarian only</span>
          </button>
          <button type="button" data-diet="egg" aria-pressed="false">
            <span class="ftype is-egg" aria-hidden="true"></span>
            <span class="label-long">Egg</span><span class="sr-only">Show egg dishes only</span>
          </button>
          <?php if ($hasBest): ?>
            <button type="button" data-best-filter aria-pressed="false">
              <span class="label-long">Bestsellers</span><span class="sr-only">Show bestsellers only</span>
            </button>
          <?php endif; ?>
        </div>

      </div>

      <!-- Mobile category chips. On desktop the sidebar below takes over. -->
      <div class="cat-scroller" aria-label="Jump to a section">
        <?php foreach ($menu['categories'] as $c): ?>
          <a class="cat-chip" data-cat-link href="#cat-<?= e($c['slug']) ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="shell section">
    <div class="menu-layout">

      <!-- --------------------------------------------- desktop sidebar -->
      <nav class="cat-sidebar" aria-label="Menu sections">
        <ul>
          <?php foreach ($menu['categories'] as $c): ?>
            <li>
              <a data-cat-link href="#cat-<?= e($c['slug']) ?>">
                <span><?= e($c['name']) ?></span>
                <span class="count"><?= count(items_in($menu, (int) $c['id'])) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <div>
        <p class="result-count" data-result-count aria-live="polite"><?= count($menu['items']) ?> dishes</p>

        <?php foreach ($menu['categories'] as $c):
            $rows = items_in($menu, (int) $c['id']);
            if (!$rows) continue;
            $categoryName = $c['name'];
        ?>
          <section class="menu-group" data-group id="cat-<?= e($c['slug']) ?>">
            <div class="menu-group-head">
              <h2><?= e($c['name']) ?></h2>
              <span class="count"><?= count($rows) ?> dishes</span>
            </div>
            <?php if (!empty($c['blurb'])): ?>
              <p class="menu-group-blurb"><?= e($c['blurb']) ?></p>
            <?php endif; ?>

            <ul class="menu-list is-grid">
              <?php foreach ($rows as $item) {
                  include __DIR__ . '/includes/partial-menu-item.php';
              } ?>
            </ul>
          </section>
        <?php endforeach; ?>

        <!-- Empty state: shown by menu.js when a filter matches nothing. -->
        <div class="empty-state" data-empty hidden>
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
          </svg>
          <h3>No dishes found</h3>
          <p>Try another search word, or clear the veg and non-veg filters.</p>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
