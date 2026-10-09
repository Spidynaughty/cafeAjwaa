<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$menu   = load_menu();
$counts = ['veg' => 0, 'nonveg' => 0, 'egg' => 0];
foreach ($menu['items'] as $i) { $counts[$i['food_type']]++; }

$pageTitle       = 'About — ' . SITE_NAME . ', Malwani, Malad West';
$pageDescription = 'About ' . SITE_NAME . ': a neighbourhood restaurant in Malwani Colony, '
                 . 'Malad West, with a charcoal tandoor, Indian main course and a Chinese counter.';
$canonicalPath   = '/about.php';

require __DIR__ . '/includes/header.php';
?>

<div class="shell section">
  <h1 style="font-size:var(--step-4)">About <?= e(SITE_NAME) ?></h1>

  <div class="split" style="align-items:start">
    <div>
      <p>
        <?= e(SITE_NAME) ?> is a neighbourhood restaurant in Malwani Colony,
        Malad West. You will find us at Gate 8, by the Gaikwad Nagar bus depot.
      </p>
      <p>
        The kitchen runs three sections at once. A charcoal tandoor turns out
        tandoori chicken, barra and tikka along with roti, paratha and naan. An
        Indian main-course section cooks the gravies — butter chicken, kolhapuri,
        kadai, lucknowi, lahori, afghani, and the same range on the paneer and
        vegetable side. A Chinese counter handles soups, chilli and 65 gravies,
        fried rice and noodles.
      </p>
      <p>
        That means a table does not have to agree on one cuisine. Tandoori
        chicken, dal tadka and schezwan noodles can arrive together.
      </p>

      <h2 style="font-size:var(--step-2);margin-top:2rem">What we cook</h2>
      <p>
        The menu runs to <?= count($menu['items']) ?> dishes across
        <?= count($menu['categories']) ?> sections:
        <?= (int) $counts['nonveg'] ?> non-vegetarian,
        <?= (int) $counts['veg'] ?> vegetarian and
        <?= (int) $counts['egg'] ?> egg dishes. Every dish is marked on the menu
        page so you can filter to what you eat.
      </p>
      <p><a class="btn" href="<?= e(url('/menu.php')) ?>">See the full menu</a></p>
    </div>

    <div>
      <ul class="info-list">
        <li><span><span class="k">Cuisine</span><br><span class="v">North Indian, Mughlai, Tandoori, Chinese</span></span></li>
        <li><span><span class="k">Dining</span><br><span class="v">Casual family dining, takeaway<?= DELIVERY_ENABLED ? ', delivery' : '' ?></span></span></li>
        <li><span><span class="k">Area</span><br><span class="v">Malwani Colony, Malad West, Mumbai</span></span></li>
        <li><span><span class="k">Landmark</span><br><span class="v">Gate 8, Gaikwad Nagar Bus Depot</span></span></li>
      </ul>

      <div class="alert alert--info" style="margin-top:1.5rem">
        <p><strong>This page is deliberately short.</strong> We have not written a
           founding story or any awards, because none were supplied by the
           restaurant. Anything added here should come from the owner.</p>
      </div>
    </div>
  </div>
</div>

<section class="section section--dark">
  <div class="shell" style="text-align:center">
    <h2>Come and eat with us</h2>
    <p style="margin:0 auto 2rem">Malwani Colony, Malad West.</p>
    <div class="btn-row" style="justify-content:center">
      <a class="btn btn--saffron" href="<?= e(url('/menu.php')) ?>">View the menu</a>
      <?php if (BOOKING_ENABLED): ?><a class="btn btn--ghost" href="<?= e(url('/booking.php')) ?>">Book a table</a><?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
