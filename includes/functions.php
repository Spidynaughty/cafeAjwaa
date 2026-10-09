<?php
/**
 * Shared helpers. Loaded on every request via includes/bootstrap.php.
 */
declare(strict_types=1);

/* ------------------------------------------------------------- escaping */

/** Escape for HTML text and attribute context. Use on every dynamic value. */
function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape a value for use inside a JS string literal in a data-* attribute. */
function ejson($v): string
{
    return e((string) json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

/* --------------------------------------------------------------- assets */

/**
 * Build a site URL. Always pass a root-relative path starting with "/" and
 * this adds the folder the site is installed in.
 *
 *   url('/menu.php')  ->  /cafe-ajwaa/menu.php   (XAMPP)
 *                     ->  /menu.php              (live domain root)
 */
function url(string $path = '/'): string
{
    if ($path === '' || $path === '/') {
        return BASE_PATH . '/';
    }
    /* Leave anything already absolute alone: tel:, https:, mailto:, //cdn */
    if (preg_match('#^([a-z][a-z0-9+.-]*:|//)#i', $path)) {
        return $path;
    }
    return BASE_PATH . '/' . ltrim($path, '/');
}

/** Cache-busted asset URL. */
function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/')) . '?v=' . ASSET_VERSION;
}

/** True if $page matches the current script, for nav highlighting. */
function is_page(string $page): bool
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '') === $page;
}

/* --------------------------------------------------------------- money */

function money(?float $amount): string
{
    if ($amount === null) {
        return '—';
    }
    return APP_CURRENCY_SYMBOL . number_format($amount, ($amount == (int) $amount) ? 0 : 2);
}

/* ---------------------------------------------------------------- menu */

/**
 * Load the menu. Reads MySQL when it is configured, otherwise falls back to
 * data/menu-data.php so the site is fully browsable before any DB exists.
 *
 * Returns ['categories' => [...], 'items' => [...]] with each item carrying a
 * nested 'variants' array.
 */
function load_menu(): array
{
    static $menu = null;
    if ($menu !== null) {
        return $menu;
    }

    if (db_available()) {
        $cats = db_all(
            'SELECT id, name, slug, blurb, display_order
               FROM categories
              WHERE is_active = 1
              ORDER BY display_order, name'
        );
        $items = db_all(
            'SELECT id, category_id, name, source_name, slug, description,
                    food_type, is_bestseller, is_spicy, display_order
               FROM menu_items
              WHERE is_active = 1
              ORDER BY category_id, display_order, name'
        );
        $variants = db_all(
            'SELECT v.id, v.menu_item_id, v.label, v.price, v.is_default
               FROM menu_item_variants v
               JOIN menu_items i ON i.id = v.menu_item_id
              WHERE i.is_active = 1
              ORDER BY v.menu_item_id, v.display_order'
        );
        $images = db_all(
            'SELECT menu_item_id, file_path, alt_text
               FROM menu_item_images
              WHERE is_primary = 1'
        );

        $byItem = [];
        foreach ($variants as $v) {
            $byItem[(int) $v['menu_item_id']][] = [
                'id'         => (int) $v['id'],
                'label'      => $v['label'],
                'price'      => $v['price'] === null ? null : (float) $v['price'],
                'is_default' => (bool) $v['is_default'],
            ];
        }
        $imgByItem = [];
        foreach ($images as $im) {
            $imgByItem[(int) $im['menu_item_id']] = $im;
        }
        foreach ($items as &$it) {
            $it['id']            = (int) $it['id'];
            $it['category_id']   = (int) $it['category_id'];
            $it['is_bestseller'] = (bool) $it['is_bestseller'];
            $it['is_spicy']      = (bool) $it['is_spicy'];
            $it['variants']      = $byItem[$it['id']] ?? [];
            $it['image']         = $imgByItem[$it['id']]['file_path'] ?? null;
            $it['image_alt']     = $imgByItem[$it['id']]['alt_text']  ?? null;
        }
        unset($it);

        if ($cats && $items) {
            return $menu = ['categories' => $cats, 'items' => $items];
        }
    }

    // Fallback: flat file.
    $menu = require APP_ROOT . '/data/menu-data.php';
    foreach ($menu['items'] as &$it) {
        $it['image']     = $it['image']     ?? null;
        $it['image_alt'] = $it['image_alt'] ?? null;
    }
    unset($it);

    return $menu;
}

/** Items belonging to one category id. */
function items_in(array $menu, int $categoryId): array
{
    return array_values(array_filter(
        $menu['items'],
        static fn(array $i): bool => (int) $i['category_id'] === $categoryId
    ));
}

/** Look up a single item by id. */
function find_item(array $menu, int $id): ?array
{
    foreach ($menu['items'] as $i) {
        if ((int) $i['id'] === $id) {
            return $i;
        }
    }
    return null;
}

/** Look up a variant on an item by id. */
function find_variant(array $item, int $variantId): ?array
{
    foreach ($item['variants'] as $v) {
        if ((int) $v['id'] === $variantId) {
            return $v;
        }
    }
    return null;
}

/** Lowest price across an item's variants; null when no price is set. */
function item_from_price(array $item): ?float
{
    $prices = array_filter(array_column($item['variants'], 'price'),
        static fn($p): bool => $p !== null);
    return $prices ? (float) min($prices) : null;
}

/**
 * Items shown in the home page preview.
 * Uses whatever the owner has flagged as a bestseller. If nothing is flagged
 * yet, it shows a spread across categories rather than claiming popularity.
 */
function featured_items(array $menu, int $limit = 6): array
{
    $flagged = array_values(array_filter(
        $menu['items'], static fn(array $i): bool => !empty($i['is_bestseller'])
    ));
    if (count($flagged) >= 3) {
        return array_slice($flagged, 0, $limit);
    }

    $picked = [];
    $seen   = [];
    foreach ($menu['items'] as $i) {
        $c = (int) $i['category_id'];
        if (!isset($seen[$c])) {
            $seen[$c] = true;
            $picked[] = $i;
        }
        if (count($picked) >= $limit) {
            break;
        }
    }
    return $picked;
}

/** True when the owner has flagged at least one bestseller. */
function has_bestsellers(array $menu): bool
{
    foreach ($menu['items'] as $i) {
        if (!empty($i['is_bestseller'])) {
            return true;
        }
    }
    return false;
}

/* --------------------------------------------------- veg / non-veg / egg */

/**
 * Presentation data for a food-type marker.
 * Shape and text label are carried alongside colour so the marker never
 * depends on colour alone.
 */
function food_type_meta(string $type): array
{
    return match ($type) {
        'veg'    => ['label' => 'Vegetarian',     'short' => 'Veg',     'class' => 'is-veg'],
        'egg'    => ['label' => 'Contains egg',   'short' => 'Egg',     'class' => 'is-egg'],
        default  => ['label' => 'Non-vegetarian', 'short' => 'Non-veg', 'class' => 'is-nonveg'],
    };
}

/* ------------------------------------------------------------- contact */

/**
 * Restaurant contact/ops settings, normalised regardless of source.
 *
 * admin/settings.php writes to the restaurant_settings + opening_hours
 * tables. Previously nothing on the public site read those tables back, so
 * saving a setting in the admin panel had no visible effect anywhere -
 * every public page kept reading the constants in config/config.php.
 *
 * This reads the DB first (same priority as load_menu()) and only falls
 * back to the config.php constants for a field that is still blank there,
 * so the site works before the DB is set up and updates live once it is.
 */
function site_settings(): array
{
    static $s = null;
    if ($s !== null) {
        return $s;
    }

    $s = [
        'phone'                 => RESTAURANT_PHONE,
        'whatsapp'              => RESTAURANT_WHATSAPP,
        'email'                 => RESTAURANT_EMAIL,
        'postcode'              => RESTAURANT_POSTCODE,
        'maps_url'              => RESTAURANT_MAPS_URL,
        'instagram_url'         => SOCIAL_INSTAGRAM,
        'facebook_url'          => SOCIAL_FACEBOOK,
        'delivery_charge'       => (float) DELIVERY_CHARGE,
        'free_delivery_over'    => (float) FREE_DELIVERY_OVER,
        'min_order_value'       => (float) MIN_ORDER_VALUE,
        'is_accepting_orders'   => true,
        'is_accepting_bookings' => true,
        'opening_hours'         => OPENING_HOURS, // ['Monday' => ['12:00','23:30'], ...]
    ];

    if (!db_available()) {
        return $s;
    }

    $row = db_one('SELECT * FROM restaurant_settings WHERE id = 1');
    if ($row) {
        foreach (['phone', 'whatsapp', 'email', 'postcode', 'maps_url',
                  'instagram_url', 'facebook_url'] as $f) {
            if (($row[$f] ?? null) !== null && $row[$f] !== '') {
                $s[$f] = $row[$f];
            }
        }
        foreach (['delivery_charge', 'free_delivery_over', 'min_order_value'] as $f) {
            if (($row[$f] ?? null) !== null) {
                $s[$f] = (float) $row[$f];
            }
        }
        if (isset($row['is_accepting_orders'])) {
            $s['is_accepting_orders'] = (bool) $row['is_accepting_orders'];
        }
        if (isset($row['is_accepting_bookings'])) {
            $s['is_accepting_bookings'] = (bool) $row['is_accepting_bookings'];
        }
    }

    $hoursRows = db_all('SELECT * FROM opening_hours');
    if ($hoursRows) {
        // admin/settings.php always rewrites all 7 rows on save, so any row
        // at all means the owner has actually visited and saved that form -
        // an empty result (table never touched) correctly keeps the
        // config.php fallback instead of showing "closed every day".
        $hours = [];
        foreach ($hoursRows as $h) {
            if (!empty($h['is_closed'])) {
                $hours[$h['day_of_week']] = 'closed';
            } elseif (!empty($h['open_time']) && !empty($h['close_time'])) {
                $hours[$h['day_of_week']] = [
                    substr($h['open_time'], 0, 5),
                    substr($h['close_time'], 0, 5),
                ];
            }
        }
        $s['opening_hours'] = $hours;
    }

    return $s;
}

/**
 * Active, currently-valid promotional offers for the public site.
 * admin/offers.php has written to the `offers` table for a while with no
 * page anywhere selecting from it, so a saved offer never actually appeared
 * on the website despite the admin success message saying it would.
 */
function active_offers(): array
{
    if (!db_available()) {
        return [];
    }
    return db_all(
        "SELECT id, title, description, terms, valid_from, valid_to, cta_label, cta_url
           FROM offers
          WHERE is_active = 1
            AND (valid_from IS NULL OR valid_from <= CURDATE())
            AND (valid_to   IS NULL OR valid_to   >= CURDATE())
          ORDER BY created_at DESC"
    );
}

/** tel: href, or null when the number has not been supplied. */
function phone_href(): ?string
{
    $p = preg_replace('/[^0-9+]/', '', site_settings()['phone']);
    return $p !== '' ? 'tel:' . $p : null;
}

/** wa.me href with a prefilled message, or null when not supplied. */
function whatsapp_href(string $message = ''): ?string
{
    $n = preg_replace('/[^0-9]/', '', site_settings()['whatsapp']);
    if ($n === '') {
        return null;
    }
    $url = 'https://wa.me/' . $n;
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

function maps_href(): ?string
{
    $url = site_settings()['maps_url'];
    return $url !== '' ? $url : null;
}

function full_address(): string
{
    $postcode = site_settings()['postcode'];
    $parts = array_filter([
        RESTAURANT_ADDRESS_LINE,
        RESTAURANT_LOCALITY,
        RESTAURANT_CITY,
        RESTAURANT_REGION . ($postcode !== '' ? ' ' . $postcode : ''),
    ]);
    return implode(', ', $parts);
}

/* --------------------------------------------------------- setup state */

/**
 * Everything the restaurant still has to supply. Drives the amber setup
 * banner and /setup-check.php. Returns [] when the site is launch-ready.
 */
function pending_setup(): array
{
    $s = site_settings();
    $missing = [];
    if (SAMPLE_PRICING)                 $missing[] = 'Real menu prices (97 items)';
    if ($s['phone'] === '')             $missing[] = 'Phone number';
    if ($s['whatsapp'] === '')          $missing[] = 'WhatsApp number';
    if (empty($s['opening_hours']))     $missing[] = 'Opening hours';
    if ($s['postcode'] === '')          $missing[] = 'PIN code';
    if ($s['maps_url'] === '')          $missing[] = 'Google Maps link';
    if (!glob(APP_ROOT . '/assets/images/gallery/*.{jpg,jpeg,png,webp,avif}', GLOB_BRACE))
        $missing[] = 'Photographs';
    return $missing;
}

function is_launch_ready(): bool
{
    return pending_setup() === [];
}

/* --------------------------------------------------------------- misc */

/**
 * Redirect. A path starting with "/" is treated as site-relative and gets the
 * install folder added, so callers never have to think about it.
 *
 * Return type is void rather than never so the project still runs on PHP 8.0
 * (XAMPP ships a range of versions).
 */
function redirect(string $to): void
{
    header('Location: ' . url($to), true, 302);
    exit;
}

/** Human-readable opening hours, grouped. Empty array when unknown. */
function opening_hours_lines(): array
{
    $hours = site_settings()['opening_hours'];
    if (empty($hours)) {
        return [];
    }
    $lines = [];
    foreach ($hours as $day => $range) {
        $lines[] = $range === 'closed' ? "{$day}  Closed" : "{$day}  {$range[0]} – {$range[1]}";
    }
    return $lines;
}
