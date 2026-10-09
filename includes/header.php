<?php
/**
 * Page head + site header.
 *
 * Each page sets these before requiring this file:
 *   $pageTitle, $pageDescription, $canonicalPath, (optional) $bodyClass
 */
declare(strict_types=1);

if (!defined('APP_ROOT')) {
    require_once dirname(__DIR__) . '/includes/bootstrap.php';
}

$pageTitle       = $pageTitle       ?? SITE_NAME;
$pageDescription = $pageDescription ?? SITE_TAGLINE;
$canonicalPath   = $canonicalPath   ?? '/';
$bodyClass       = $bodyClass       ?? '';
$canonical       = rtrim(SITE_URL, '/') . $canonicalPath;

$navLinks = [
    '/'            => 'Home',
    '/menu.php'    => 'Menu',
    '/about.php'   => 'About',
    '/gallery.php' => 'Gallery',
    '/contact.php' => 'Contact',
];
if (BOOKING_ENABLED) {
    $navLinks['/booking.php'] = 'Book a table';
}

$currentPath = '/' . ltrim(basename($_SERVER['SCRIPT_NAME'] ?? 'index.php'), '/');
if ($currentPath === '/index.php') {
    $currentPath = '/';
}

$cartCount = ORDERING_ENABLED ? cart_count() : 0;
$pending   = pending_setup();
$settings  = site_settings();

/* schema.org Restaurant - only verified fields are emitted. */
$schema = [
    '@context' => 'https://schema.org',
    '@type'    => 'Restaurant',
    'name'     => SITE_NAME,
    'url'      => rtrim(SITE_URL, '/') . '/',
    'servesCuisine' => ['North Indian', 'Mughlai', 'Chinese', 'Tandoori'],
    'address'  => array_filter([
        '@type'           => 'PostalAddress',
        'streetAddress'   => RESTAURANT_ADDRESS_LINE,
        'addressLocality' => RESTAURANT_LOCALITY . ', ' . RESTAURANT_CITY,
        'addressRegion'   => RESTAURANT_REGION,
        'postalCode'      => $settings['postcode'],
        'addressCountry'  => RESTAURANT_COUNTRY,
    ]),
    'hasMenu'  => rtrim(SITE_URL, '/') . '/menu.php',
];
if ($settings['phone'] !== '')    $schema['telephone'] = $settings['phone'];
if ($settings['maps_url'] !== '') $schema['hasMap']    = $settings['maps_url'];
if (RESTAURANT_LAT !== '' && RESTAURANT_LNG !== '') {
    $schema['geo'] = ['@type' => 'GeoCoordinates',
                      'latitude' => RESTAURANT_LAT, 'longitude' => RESTAURANT_LNG];
}
if (!empty($settings['opening_hours'])) {
    $spec = [];
    foreach ($settings['opening_hours'] as $day => $range) {
        if ($range === 'closed') {
            continue;
        }
        $spec[] = ['@type' => 'OpeningHoursSpecification',
                   'dayOfWeek' => $day, 'opens' => $range[0], 'closes' => $range[1]];
    }
    if ($spec) {
        $schema['openingHoursSpecification'] = $spec;
    }
}
$social = array_values(array_filter([$settings['instagram_url'], $settings['facebook_url']]));
if ($social) {
    $schema['sameAs'] = $social;
}
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDescription) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="theme-color" content="#1b0f17">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDescription) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:locale" content="en_IN">
<meta name="twitter:card" content="summary_large_image">

<link rel="icon" href="<?= e(url('/assets/images/brand/favicon.svg')) ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= e(url('/assets/images/brand/apple-touch-icon.png')) ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Marcellus&family=Mukta:wght@400;600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">

<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body class="<?= e($bodyClass) ?>"
      data-base="<?= e(BASE_PATH) ?>"
      data-ordering="<?= ORDERING_ENABLED ? '1' : '0' ?>">

<a class="skip-link" href="#main">Skip to main content</a>

<?php if ($pending): ?>
<div class="setup-note" role="status">
  <div class="shell">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
      <path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
    </svg>
    <p>
      Preview build. <?= (int) count($pending) ?> items still need the restaurant's real
      information — including <?= e(strtolower($pending[0])) ?>.
      <a href="<?= e(url('/setup-check.php')) ?>">See what's pending</a>
    </p>
  </div>
</div>
<?php endif; ?>

<header class="site-header">
  <div class="shell header-bar">

    <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e(SITE_NAME) ?> — home">
      <svg class="brand-mark" viewBox="0 0 48 48" role="img" aria-hidden="true" fill="none">
        <circle cx="24" cy="24" r="23" stroke="#ff7a1a" stroke-width="1.5"/>
        <!-- date palm, echoing the shop board -->
        <path d="M24 38V25" stroke="#ffa64d" stroke-width="2" stroke-linecap="round"/>
        <path d="M24 25c-4-5-9-6-13-4 3-4 9-4 13 1 0-6 3-10 7-11-2 4-3 7-3 11 4-4 9-5 13-3-4 1-8 3-10 6-2 3-4 4-7 0Z"
              fill="#2f7d51"/>
        <circle cx="24" cy="22" r="2" fill="#ff7a1a"/>
      </svg>
      <span class="brand-text">
        <span class="brand-name"><?= e(SITE_NAME) ?></span>
        <span class="brand-sub">Malad West</span>
      </span>
    </a>

    <nav class="nav-desktop" aria-label="Primary">
      <?php foreach ($navLinks as $href => $label): ?>
        <a href="<?= e(url($href)) ?>"<?= $href === $currentPath ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <?php if (ORDERING_ENABLED): ?>
        <a class="btn btn--saffron btn--sm" href="<?= e(url('/menu.php')) ?>">Order now</a>
      <?php endif; ?>
    </nav>

    <div class="header-actions">
      <?php if (ORDERING_ENABLED): ?>
      <a class="icon-btn" href="<?= e(url('/track-order.php')) ?>" aria-label="Track your order"
         title="Track your order"<?= $currentPath === '/track-order.php' ? ' aria-current="page"' : '' ?>>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
          <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
          <path d="M3.3 7 12 12l8.7-5M12 22V12"/>
        </svg>
      </a>
      <a class="icon-btn" href="<?= e(url('/cart.php')) ?>" aria-label="View cart">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
          <path d="M3 4h2l2.4 11.4a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6"/>
          <circle cx="10" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/>
        </svg>
        <span class="cart-badge" data-cart-badge<?= $cartCount ? '' : ' hidden' ?>><?= (int) $cartCount ?></span>
      </a>
      <?php endif; ?>

      <button class="icon-btn nav-toggle" type="button"
              aria-label="Open menu" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <path d="M3 6h18M3 12h18M3 18h18"/>
        </svg>
      </button>
    </div>

  </div>
</header>

<div class="nav-scrim" data-nav-scrim hidden></div>
<nav class="nav-mobile" id="site-nav" aria-label="Mobile" data-nav-panel>
  <ul>
    <?php foreach ($navLinks as $href => $label): ?>
      <li><a href="<?= e(url($href)) ?>"<?= $href === $currentPath ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <?php if ($tel = phone_href()): ?>
    <a class="btn btn--block" href="<?= e($tel) ?>">Call the restaurant</a>
  <?php endif; ?>
  <?php if ($wa = whatsapp_href('Hello, I would like to order from ' . SITE_NAME . '.')): ?>
    <a class="btn btn--ghost btn--block" style="margin-top:.75rem" href="<?= e($wa) ?>" rel="noopener">WhatsApp</a>
  <?php endif; ?>
</nav>

<main id="main">
