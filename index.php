<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$menu     = load_menu();
$featured = featured_items($menu, 6);
$hasBest  = has_bestsellers($menu);
$offers   = active_offers();
$counts   = ['veg' => 0, 'nonveg' => 0, 'egg' => 0];
foreach ($menu['items'] as $i) {
    $counts[$i['food_type']]++;
}

$pageTitle       = SITE_NAME . ' — Tandoori, Biryani & Chinese in Malad West, Mumbai';
$pageDescription = 'Cafe Ajwaa in Malwani, Malad West serves tandoori and barbeque, '
                 . 'chicken and paneer main course, biryani, fried rice and noodles. '
                 . count($menu['items']) . ' dishes, cooked to order.';
$canonicalPath   = '/';

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="shell hero-inner">
    <p class="hero-eyebrow">Malvani, Malad West</p>
    <div>
      <h1><?= e(SITE_NAME) ?></h1>
      <p class="hero-local"><?= e(SITE_NAME_LOCAL) ?></p>
    </div>
    <p class="hero-lede">
      Charcoal tandoor, slow-cooked handi biryani and a Chinese counter,
      in one neighbourhood kitchen off the Gaikwad Nagar bus depot.
    </p>
    <ul class="hero-facts">
      <li>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
        <?= count($menu['items']) ?> dishes
      </li>
      <li>
        <span class="ftype is-veg" aria-hidden="true" style="color:#79c79d"></span>
        <?= (int) $counts['veg'] ?> vegetarian
      </li>
      <li>
        <span class="ftype is-nonveg" aria-hidden="true" style="color:#ef9b94"></span>
        <?= (int) $counts['nonveg'] ?> non-vegetarian
      </li>
    </ul>
    <div class="btn-row">
      <a class="btn btn--saffron" href="<?= e(url('/menu.php')) ?>">View menu</a>
      <?php if (ORDERING_ENABLED): ?>
        <a class="btn btn--ghost" href="<?= e(url('/menu.php#order')) ?>">Order now</a>
      <?php endif; ?>
      <?php if (BOOKING_ENABLED): ?>
        <a class="btn btn--ghost" href="<?= e(url('/booking.php')) ?>">Book a table</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($offers): ?>
<!-- --------------------------------------------------------------- offers -->
<section class="section section--paper">
  <div class="shell">
    <div class="section-head">
      <h2>Current offers</h2>
    </div>
    <ul class="offer-grid">
      <?php foreach ($offers as $offer): ?>
        <li class="offer-card">
          <h3><?= e($offer['title']) ?></h3>
          <?php if ($offer['description']): ?><p><?= e($offer['description']) ?></p><?php endif; ?>
          <?php if ($offer['valid_to']): ?>
            <p class="offer-valid">Valid till <?= e(date('d M Y', strtotime($offer['valid_to']))) ?></p>
          <?php endif; ?>
          <?php if ($offer['terms']): ?><p class="offer-terms"><?= e($offer['terms']) ?></p><?php endif; ?>
          <?php if ($offer['cta_url']): ?>
            <a class="btn btn--ghost btn--sm" href="<?= e($offer['cta_url']) ?>"><?= e($offer['cta_label'] ?: 'Learn more') ?></a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<!-- ------------------------------------------------------ menu preview -->
<section class="section section--paper">
  <div class="shell">
    <div class="section-head">
      <h2><?= $hasBest ? 'What people order most' : 'A few things we cook' ?></h2>
      <p>
        <?= $hasBest
            ? 'Marked by the kitchen as our most-ordered dishes.'
            : 'One dish from each part of the menu. The full list has '
              . count($menu['items']) . ' items.' ?>
      </p>
    </div>

    <ul class="menu-list is-grid">
      <?php foreach ($featured as $item) {
          include __DIR__ . '/includes/partial-menu-item.php';
      } ?>
    </ul>

    <p style="margin-top:2rem">
      <a class="btn" href="<?= e(url('/menu.php')) ?>">See the full menu</a>
    </p>
  </div>
</section>

<!-- --------------------------------------------------------- highlights -->
<section class="section section--dark">
  <div class="shell">
    <div class="section-head">
      <h2>What the kitchen does</h2>
      <p>Four counters, one kitchen. Everything below is on the menu today.</p>
    </div>
    <div class="tile-grid">
      <div class="tile">
        <h3><span aria-hidden="true">🔥</span> Charcoal tandoor</h3>
        <p>Tandoori chicken, barra, tikka in kalimiri, zafrani, pahadi and
           schezwan, plus roti, paratha and naan from the same oven.</p>
      </div>
      <div class="tile">
        <h3><span aria-hidden="true">🍛</span> Indian main course</h3>
        <p>Butter chicken, kolhapuri, kadai, lucknowi, lahori, afghani —
           and the same depth on the paneer and veg side.</p>
      </div>
      <div class="tile">
        <h3><span aria-hidden="true">🍚</span> Biryani &amp; pulao</h3>
        <p>Handi dum biryani and tikka biryani, with paneer and veg biryani,
           pulao, khichdi and jeera rice.</p>
      </div>
      <div class="tile">
        <h3><span aria-hidden="true">🥡</span> Chinese</h3>
        <p>Manchow and hot-and-sour soups, chilli and 65 gravies, hakka and
           schezwan noodles, and eight fried rices.</p>
      </div>
    </div>
  </div>
</section>

<!-- -------------------------------------------------------------- about -->
<section class="section section--paper">
  <div class="shell split">
    <div>
      <div class="section-head">
        <h2>About <?= e(SITE_NAME) ?></h2>
      </div>
      <p>
        <?= e(SITE_NAME) ?> is a neighbourhood restaurant in Malwani Colony,
        Malad West, at Gate 8 near the Gaikwad Nagar bus depot. The kitchen
        runs a charcoal tandoor, an Indian main-course section and a Chinese
        counter, so a table can order tandoori chicken, dal tadka and schezwan
        noodles in the same round.
      </p>
      <p>
        The menu runs to <?= count($menu['items']) ?> dishes across
        <?= count($menu['categories']) ?> sections, with
        <?= (int) $counts['veg'] ?> vegetarian dishes alongside the
        <?= (int) $counts['nonveg'] ?> non-vegetarian ones.
      </p>
      <p><a class="btn btn--ghost" href="<?= e(url('/about.php')) ?>">More about us</a></p>
    </div>
    <div>
      <ul class="info-list">
        <li>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
          <span><span class="k">Address</span><br><span class="v"><?= e(full_address()) ?></span></span>
        </li>
        <li>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
          <span>
            <span class="k">Opening hours</span><br>
            <span class="v">
              <?php $h = opening_hours_lines(); ?>
              <?= $h ? e(implode(' · ', $h)) : 'To be confirmed' ?>
            </span>
          </span>
        </li>
        <li>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg>
          <span>
            <span class="k">Phone</span><br>
            <span class="v">
              <?php if ($tel = phone_href()): ?>
                <a href="<?= e($tel) ?>"><?= e($settings['phone']) ?></a>
              <?php else: ?>To be confirmed<?php endif; ?>
            </span>
          </span>
        </li>
      </ul>
      <?php if ($maps = maps_href()): ?>
        <p style="margin-top:1.5rem"><a class="btn btn--ghost" href="<?= e($maps) ?>" rel="noopener">Get directions</a></p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ---------------------------------------------------------- final CTA -->
<section class="section section--dark">
  <div class="shell" style="text-align:center">
    <h2 style="margin-bottom:.75rem">Craving something from the tandoor?</h2>
    <p style="margin:0 auto 2rem">The full menu is <?= count($menu['items']) ?> dishes deep.</p>
    <div class="btn-row" style="justify-content:center">
      <a class="btn btn--saffron" href="<?= e(url('/menu.php')) ?>">Explore the menu</a>
      <?php if ($wa = whatsapp_href('Hello, I would like to order from ' . SITE_NAME . '.')): ?>
        <a class="btn btn--ghost" href="<?= e($wa) ?>" rel="noopener">Order on WhatsApp</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
