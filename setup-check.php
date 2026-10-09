<?php
/**
 * A plain checklist of everything still missing before the site can go live.
 * Built for the owner, not for developers: each row says what is missing,
 * where it goes, and what the visitor currently sees instead.
 */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$menu     = load_menu();
$settings = site_settings();

$checks = [
    [
        'label'   => 'Menu prices',
        'ok'      => !SAMPLE_PRICING,
        'detail'  => count($menu['items']) . ' dishes currently show sample prices.',
        'where'   => 'Admin → Menu, or data/menu-data.php',
        'fallback'=> 'Prices display with a "sample prices" notice on every page that shows a total.',
    ],
    [
        'label'   => 'Phone number',
        'ok'      => $settings['phone'] !== '',
        'detail'  => 'Used for click-to-call across the site.',
        'where'   => 'Admin → Settings, or config/config.php → RESTAURANT_PHONE',
        'fallback'=> 'Call buttons are hidden and the footer reads "Phone number to be confirmed".',
    ],
    [
        'label'   => 'WhatsApp number',
        'ok'      => $settings['whatsapp'] !== '',
        'detail'  => 'Used for order and price enquiries.',
        'where'   => 'Admin → Settings, or config/config.php → RESTAURANT_WHATSAPP',
        'fallback'=> 'WhatsApp buttons are hidden.',
    ],
    [
        'label'   => 'Opening hours',
        'ok'      => !empty($settings['opening_hours']),
        'detail'  => 'Also feeds the Google structured data for the listing.',
        'where'   => 'Admin → Settings, or config/config.php → OPENING_HOURS',
        'fallback'=> 'Hours read "to be confirmed" and are left out of the schema.org markup.',
    ],
    [
        'label'   => 'PIN code',
        'ok'      => $settings['postcode'] !== '',
        'detail'  => 'Needed for a complete postal address in search results.',
        'where'   => 'Admin → Settings, or config/config.php → RESTAURANT_POSTCODE',
        'fallback'=> 'The address prints without a PIN code.',
    ],
    [
        'label'   => 'Google Maps link',
        'ok'      => $settings['maps_url'] !== '',
        'detail'  => 'Powers the directions button.',
        'where'   => 'Admin → Settings, or config/config.php → RESTAURANT_MAPS_URL',
        'fallback'=> 'Directions buttons are hidden and the contact page says so.',
    ],
    [
        'label'   => 'Photographs',
        'ok'      => (bool) glob(APP_ROOT . '/assets/images/gallery/*.{jpg,jpeg,png,webp,avif}', GLOB_BRACE),
        'detail'  => 'Food and interior photographs for the gallery and menu cards.',
        'where'   => 'assets/images/gallery/ and assets/images/food/',
        'fallback'=> 'The gallery shows an empty state; menu items show a monogram tile.',
    ],
    [
        'label'   => 'Bestseller and spicy flags',
        'ok'      => has_bestsellers($menu),
        'detail'  => 'Drives the home page section and the bestseller filter.',
        'where'   => 'Admin → Menu',
        'fallback'=> 'The home page shows a spread of dishes instead of claiming popularity, and the bestseller filter is hidden.',
    ],
    [
        'label'   => 'Database connection',
        'ok'      => db_available(),
        'detail'  => 'Needed for orders, bookings and the admin panel.',
        'where'   => 'config/database.php',
        'fallback'=> 'The site reads the menu from a flat file. Orders and bookings cannot be saved.',
    ],
    [
        'label'   => 'Live domain',
        'ok'      => !str_contains(SITE_URL, 'example'),
        'detail'  => 'Used for canonical links and social sharing previews.',
        'where'   => 'config/config.php → SITE_URL',
        'fallback'=> 'Canonical and Open Graph URLs point at a placeholder domain.',
    ],
];

$done = count(array_filter($checks, static fn($c) => $c['ok']));

$pageTitle       = 'Setup checklist — ' . SITE_NAME;
$pageDescription = 'What still needs to be supplied before this website goes live.';
$canonicalPath   = '/setup-check.php';

require __DIR__ . '/includes/header.php';
?>
<meta name="robots" content="noindex">

<div class="shell section">
  <h1 style="font-size:var(--step-4)">Setup checklist</h1>
  <p style="max-width:60ch">
    <?= $done ?> of <?= count($checks) ?> items are ready. Everything below is
    information only the restaurant can supply. Nothing on this site has been
    invented to fill a gap — where something is missing, the page says so.
  </p>

  <?php foreach ($checks as $c): ?>
    <div class="form-card" style="margin-bottom:1rem;border-left:3px solid <?= $c['ok'] ? 'var(--veg)' : 'var(--saffron)' ?>">
      <h2 style="font-size:var(--step-1);display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem">
        <span aria-hidden="true" style="color:<?= $c['ok'] ? 'var(--veg)' : 'var(--saffron)' ?>">
          <?= $c['ok'] ? '✓' : '○' ?>
        </span>
        <?= e($c['label']) ?>
        <span class="sr-only"><?= $c['ok'] ? '— ready' : '— still needed' ?></span>
      </h2>
      <p class="text-sm" style="margin-bottom:.5rem"><?= e($c['detail']) ?></p>
      <p class="text-sm text-mute" style="margin-bottom:.5rem">
        Goes in: <code><?= e($c['where']) ?></code>
      </p>
      <?php if (!$c['ok']): ?>
        <p class="text-sm text-mute" style="margin:0">
          Until then: <?= e($c['fallback']) ?>
        </p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <div class="alert alert--info" style="margin-top:2rem">
    <p><strong>Two decisions also need an answer.</strong></p>
    <ul style="margin:0">
      <li>The shop board reads <strong>Cafe Ajwa</strong>; the Zomato listing reads
          <strong>Cafe Ajwaa</strong>. The site currently uses
          "<?= e(SITE_NAME) ?>" — change <code>SITE_NAME</code> to switch it everywhere.</li>
      <li>Two dishes were classified by inference, not by the menu:
          <strong>Mix Pulao</strong> is marked vegetarian, and
          <strong>Caramel Custard</strong> is marked as containing egg.
          Both need the kitchen to confirm.</li>
    </ul>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
