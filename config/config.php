<?php
/**
 * Cafe Ajwaa - site configuration.
 *
 * EVERY value the restaurant owner may want to change lives here or in the
 * admin panel. Nothing in this file is a secret; database credentials live in
 * config/database.php, which is git-ignored.
 *
 * Values marked NEEDS CONFIRMATION are placeholders. They render as visible
 * "to be confirmed" states in the UI rather than as plausible-looking fakes.
 */
declare(strict_types=1);

/* ---------------------------------------------------------------- identity */

/**
 * The shop board reads "CAFE AJWA"; the Zomato listing reads "Cafe Ajwaa".
 * Change this one constant to switch the whole site, including <title>,
 * schema.org markup and the logo lockup.
 */
const SITE_NAME       = 'Cafe Ajwaa';
const SITE_NAME_LOCAL = 'कॅफे अजवा';          // as painted on the shop board
const SITE_TAGLINE    = 'Tandoor, biryani and Chinese in Malwani';

/* Absolute base URL, no trailing slash. Used for canonical and og: tags. */
const SITE_URL = 'https://cafeajwaa.com';   // NEEDS CONFIRMATION (domain)

/* ---------------------------------------------------------------- contact */

const RESTAURANT_ADDRESS_LINE   = 'Gate 8, Gaikwad Nagar Bus Depot, Malwani Colony';
const RESTAURANT_LOCALITY       = 'Malad West';
const RESTAURANT_CITY           = 'Mumbai';
const RESTAURANT_REGION         = 'Maharashtra';
const RESTAURANT_POSTCODE       = '';           // NEEDS CONFIRMATION
const RESTAURANT_COUNTRY        = 'IN';

const RESTAURANT_PHONE          = '';           // NEEDS CONFIRMATION e.g. +912212345678
const RESTAURANT_WHATSAPP       = '';           // NEEDS CONFIRMATION e.g. 919812345678
const RESTAURANT_EMAIL          = '';           // optional

/* Paste the "Share > Embed a map" or plain share link from Google Maps. */
const RESTAURANT_MAPS_URL       = '';           // NEEDS CONFIRMATION
const RESTAURANT_LAT            = '';           // NEEDS CONFIRMATION
const RESTAURANT_LNG            = '';           // NEEDS CONFIRMATION

/**
 * Opening hours. Empty array = unknown, which renders as "Hours to be
 * confirmed" and omits openingHoursSpecification from the schema.org block.
 * Format: 'Mon' => ['12:00', '23:30'].
 */
const OPENING_HOURS = [];                       // NEEDS CONFIRMATION

/* Real social profiles only. An empty string hides the link entirely. */
const SOCIAL_INSTAGRAM = '';
const SOCIAL_FACEBOOK  = '';

/* --------------------------------------------------------------- ordering */

/**
 * SAMPLE_PRICING
 *   true  - menu prices are placeholder values. The UI shows a standing
 *           notice and tags every price, so nobody can mistake them for real.
 *   false - prices are the restaurant's own. All notices disappear.
 *
 * Set to false only after the owner has confirmed all 97 prices.
 */
const SAMPLE_PRICING = false;

const ORDERING_ENABLED   = true;   // false hides every Add button and the cart
const BOOKING_ENABLED    = true;
const DELIVERY_ENABLED   = true;
const PICKUP_ENABLED     = true;

const DELIVERY_CHARGE    = 40;     // NEEDS CONFIRMATION (rupees)
const FREE_DELIVERY_OVER = 0;      // 0 disables the free-delivery threshold
const MIN_ORDER_VALUE    = 0;      // 0 disables the minimum
const APP_CURRENCY_SYMBOL    = '₹';
const CURRENCY_CODE      = 'INR';

/* ---------------------------------------------------------------- runtime */

/**
 * Type this word into the "Search dishes" box on the menu page and it jumps
 * straight to /admin/login.php - nothing more. It is a shortcut to find the
 * door, not a key: whoever types it still needs a real username and password
 * from the admins table to actually get in. Change it to any word you like;
 * it is not case-sensitive.
 */
const ADMIN_ENTRY_PHRASE = 'ajwaaadmin';

const ASSET_VERSION = '1.1.0';     // bump to bust CSS/JS caches after a deploy
const TIMEZONE      = 'Asia/Kolkata';
const DEBUG         = false;       // true prints PHP errors; never true in production

date_default_timezone_set(TIMEZONE);

if (DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

define('APP_ROOT', dirname(__DIR__));

/* ------------------------------------------------------------ base path */

/**
 * Where the site lives relative to the web root.
 *
 *   http://localhost/cafe-ajwaa/   ->  '/cafe-ajwaa'
 *   https://cafeajwaa.com/         ->  ''
 *
 * Detected automatically by comparing this folder against Apache's document
 * root, so the same code runs under XAMPP and on a live domain with no edit.
 * If the detection ever guesses wrong (symlinked roots, odd vhosts), set
 * BASE_PATH_OVERRIDE below to the folder name, e.g. '/cafe-ajwaa'.
 */
const BASE_PATH_OVERRIDE = null;

$__docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
$__appRoot = str_replace('\\', '/', APP_ROOT);

/* realpath() normalises Windows drive letters and any ../ segments. */
if ($__docRoot !== '' && ($__real = realpath($__docRoot)) !== false) {
    $__docRoot = str_replace('\\', '/', rtrim($__real, '/'));
}
if (($__real = realpath(APP_ROOT)) !== false) {
    $__appRoot = str_replace('\\', '/', rtrim($__real, '/'));
}

$__base = '';
if ($__docRoot !== '' && strncasecmp($__appRoot, $__docRoot, strlen($__docRoot)) === 0) {
    $__base = rtrim(substr($__appRoot, strlen($__docRoot)), '/');
}

define('BASE_PATH', BASE_PATH_OVERRIDE ?? $__base);
unset($__docRoot, $__appRoot, $__base, $__real);
