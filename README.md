# Cafe Ajwaa — website

Production PHP/MySQL website for Cafe Ajwaa, Gate 8, Gaikwad Nagar Bus Depot,
Malwani Colony, Malad West, Mumbai.

No framework, no build step, no npm. Upload the folder to a PHP host and it runs.

---

## 1. Requirements

- PHP 8.1 or newer (uses `match`, enums in types, `str_contains`)
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite`, `mod_headers`, `mod_deflate` (the `.htaccess` files
  degrade quietly if a module is missing)

## 2. Install

```bash
# 1. Upload the contents of cafe-ajwaa/ to your web root.

# 2. Create the database and load the schema, then the 97 menu items.
mysql -u root -p -e "CREATE DATABASE cafe_ajwaa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p cafe_ajwaa < sql/schema.sql
mysql -u root -p cafe_ajwaa < sql/seed.sql

# 3. Point the app at the database.
cp config/database.sample.php config/database.php
#    then edit config/database.php with the real credentials

# 4. Create the owner's admin login. The password is hashed as you type it,
#    so it never lands in a file or in shell history.
php sql/create-admin.php

# 5. Make the upload folders writable by the web server.
#    (assets/images/gallery, not uploads/gallery - that's where Admin -> Gallery
#    and gallery.php actually read/write photographs and captions.php)
chmod 755 uploads uploads/food assets/images/gallery
```

Then log in at `/admin/login.php`.

**The site works before step 2.** With no database, the menu is read from
`data/menu-data.php` and every page renders. Orders and bookings cannot be
saved, and the pages say so plainly rather than pretending they succeeded.

## 2a. Running it on XAMPP

Put the `cafe-ajwaa` folder directly inside `xampp/htdocs`, so the path is:

```
xampp/htdocs/cafe-ajwaa/index.php        <- correct
xampp/htdocs/cafe-ajwaa/cafe-ajwaa/...   <- wrong (one folder too deep)
```

Unzipping usually creates the nested version. If `index.php` is not directly
inside `htdocs/cafe-ajwaa`, move it up one level.

Then start Apache and MySQL, create the database in phpMyAdmin, import
`sql/schema.sql` and then `sql/seed.sql`, and open:

```
http://localhost/cafe-ajwaa/health.php
```

That page checks PHP version, the PDO driver, the database connection, whether
the 97 dishes imported, and which folder the site thinks it is installed in.
It names the specific thing that is wrong instead of leaving you with a blank
page. Delete `health.php` before going live.

**Sub-folder support.** The site detects that it lives in `/cafe-ajwaa` and
prefixes every link and asset automatically, so the same files run unchanged
at `http://localhost/cafe-ajwaa/` and at `https://yourdomain.com/`. If the
detection ever guesses wrong, set `BASE_PATH_OVERRIDE` in `config/config.php`
to `'/cafe-ajwaa'`.

**Admin login on Windows.** `php sql/create-admin.php` needs XAMPP's PHP on
your PATH. From the project folder:

```
C:\xampp\php\php.exe sql\create-admin.php
```

## 3. What to change first

Open `/setup-check.php` in a browser. It lists everything still missing, where
each item goes, and what visitors currently see in its place.

Everything an owner would want to change lives in **`config/config.php`**:

| Constant | What it does |
|---|---|
| `SITE_NAME` | The shop board reads "Cafe Ajwa"; Zomato reads "Cafe Ajwaa". Change this one line to switch the whole site. |
| `SAMPLE_PRICING` | `true` while prices are placeholders. Set to `false` once the real prices are in and every "sample prices" notice disappears. |
| `RESTAURANT_PHONE` | Empty hides all call buttons. |
| `RESTAURANT_WHATSAPP` | Empty hides all WhatsApp buttons. |
| `OPENING_HOURS` | Empty array shows "to be confirmed" and omits hours from the Google markup. |
| `RESTAURANT_MAPS_URL` | Empty hides the directions buttons. |
| `ORDERING_ENABLED` | `false` removes the cart and every Add button. |
| `BOOKING_ENABLED` | `false` removes the booking page and nav link. |
| `DELIVERY_CHARGE` | Rupees added at checkout for delivery orders. |
| `ASSET_VERSION` | Bump after a CSS or JS change to clear browser caches. |

## 4. Menu data

`data/menu-data.php` holds all 97 dishes. It was generated from the
restaurant's own Zomato listing and is the source for `sql/seed.sql`.

Each item carries:

- `name` — corrected display spelling
- `source_name` — the exact string from the listing, kept so every correction
  is traceable (`Chicken Manchow Suop` → `Chicken Manchow Soup`)
- `food_type` — `veg`, `nonveg` or `egg`
- `description` — deliberately empty; the source listing had none and none
  was invented
- `variants` — one row per sellable portion, so Half/Full works per dish and a
  roti simply has one "Regular" row

Once MySQL is connected the site reads the menu from the database instead and
this file becomes a seed source only.

## 5. Decisions taken

These were open questions with no answer at build time. Each was resolved to
the safest option and each is a one-line change.

1. **Name** — `SITE_NAME` is set to "Cafe Ajwaa" (the brief and the Zomato
   listing). The shop board reads "Cafe Ajwa".
2. **Categories** — the listing's 11 inconsistent categories were regrouped
   into 10 clean ones. All 97 items are preserved; none added, none dropped.
   The old "Barbeque" category held only paneer starters, and "Indian Main
   Course" was entirely chicken while "Main Course" was entirely vegetarian.
3. **Spelling** — corrected for display (`Suop`→`Soup`, `Arbian`→`Arabian`,
   `Tripple`→`Triple`, `Nan`→`Naan`, `Lollypop`→`Lollipop`). Originals kept in
   `source_name`.
4. **Mix Pulao** — marked vegetarian, though the listing filed it under the
   chicken rice section. Needs the kitchen to confirm.
5. **Caramel Custard** — marked as containing egg. Needs the kitchen to confirm.
6. **Photography** — the site is designed to look finished without food
   photographs, using printed-menu typography and dotted price leaders. Photos
   drop in later with no redesign.
7. **Prices** — `SAMPLE_PRICING` is `true`, so the ordering flow is fully
   demonstrable while every price is visibly labelled as a placeholder.

## 5a. Colour palette

Deep plum, saffron and warm sand.

Green and red are deliberately kept out of the brand palette. On an Indian
menu those two colours already mean "vegetarian" and "non-vegetarian", and
that is the single most scanned signal on the page — using them for buttons
and headings as well would blunt it. Plum carries the warmth a navy could not,
and saffron handles the tandoor without the orange-and-red that every other
restaurant site reaches for.

All colours live as custom properties at the top of `assets/css/site.css`.
Changing the eleven values in `:root` re-skins the whole site.

Saffron is a light accent, so buttons and badges put dark ink on it rather
than white — white on saffron measures about 2.3:1 and fails contrast, dark
ink on the same saffron is about 7:1.

## 6. Security

- All SQL goes through prepared statements. No query concatenates user input.
- Admin passwords are bcrypt (cost 12). The login runs a dummy hash check on
  unknown usernames so response time does not reveal which accounts exist.
- Login is throttled to 5 attempts per minute per session.
- Every state-changing POST carries a CSRF token compared with `hash_equals`.
- Session cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` over HTTPS.
  The session id is regenerated on login and logout.
- **Cart prices are never accepted from the browser.** The server re-reads
  every price from the menu on each request, so a tampered form cannot change
  a total.
- `order_items` stores a snapshot of the name and price, so raising a price
  later does not rewrite order history.
- Uploads are type-checked with `getimagesize()`, renamed, and stored in a
  directory whose `.htaccess` disables PHP execution.
- `/config`, `/includes`, `/data` and `/sql` return 404 over HTTP.
- `config/database.php` is git-ignored; only the `.sample` is committed.
- `DEBUG` is `false`, so PHP errors go to the error log, never to the browser.

## 7. Performance

- One stylesheet, one core script. No framework, no jQuery.
- ~23 KB of JavaScript unminified across four files, zero dependencies.
- `menu.js`, `gallery.js` and `forms.js` load only on the pages that need them.
- Every image has explicit `width`/`height` and `loading="lazy"` below the
  fold, so there is no layout shift.
- The hero uses an inline SVG pattern (under 1 KB) instead of a photograph.
- Search is debounced at 120 ms and filters the DOM directly — no re-render.

## 8. Accessibility

- Veg / non-veg / egg markers use **shape and a text label**, never colour
  alone: a round dot for veg, a triangle for non-veg, a round outline for egg,
  each with screen-reader text.
- Skip link, landmark elements, one `h1` per page, ordered headings.
- Visible focus rings; the mobile drawer, portion sheet and lightbox all trap
  focus and close on `Escape`.
- Form errors are tied to their inputs with `aria-describedby` and
  `aria-invalid`; the first invalid field receives focus on submit.
- Filter results announce through `aria-live`.
- `prefers-reduced-motion` disables the single hero animation and smooth
  scrolling.

## 9. Pages

| Path | Purpose |
|---|---|
| `/` | Home |
| `/menu.php` | Full menu, search, veg/non-veg/egg filters, category nav |
| `/cart.php` | Cart with quantity controls and totals |
| `/checkout.php` | Name, mobile, delivery or pickup, address |
| `/order-confirmation.php` | Order reference; states clearly that the restaurant confirms by phone |
| `/booking.php` | Table request; never claims a confirmed booking |
| `/contact.php` | Address, call, WhatsApp, directions, message form |
| `/about.php` | Factual only |
| `/gallery.php` | Reads `/assets/images/gallery`; empty state until photos exist |
| `/setup-check.php` | Owner checklist of what is still missing |
| `/404.php` | Not found |
| `/admin/*` | Dashboard, orders, bookings, menu, gallery, offers, reviews, settings |

## 10. Adding photographs

**Admin → Gallery** has an upload form (JPG/PNG/WebP, up to 6 MB) with an
optional caption field - the normal way to add a photo. Photos appear on the
public gallery page immediately, and can be deleted or re-captioned from the
same screen.

Files can also be dropped straight into `assets/images/gallery/` via FTP or
a file manager, useful for adding many at once. Either method writes to the
same folder, so both show up together, sorted by filename. No code change
either way.

For alt text without using the admin form, edit
`assets/images/gallery/captions.php` directly:

```php
<?php return [
    'tandoor-01.jpg' => 'Chicken tikka coming out of the charcoal tandoor',
    'interior-01.jpg' => 'The dining room seen from the entrance',
];
```

Per-dish photos are uploaded through **Admin → Menu**.

## 11. Still needed from the restaurant

Blocking go-live:

- Real prices for all 97 dishes, and which dishes have Half/Full portions
- Phone number and WhatsApp number
- Opening hours
- PIN code and the Google Maps link
- Food and interior photography

Non-blocking but worth having:

- Which dishes are bestsellers, and which are spicy
- Whether beverages are served (the supplied menu had no drinks at all)
- Confirmation of the name spelling, and of Mix Pulao / Caramel Custard
- Any real offers, and a way to collect real customer reviews
