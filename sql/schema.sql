-- =============================================================================
-- Cafe Ajwaa - database schema
--
-- MySQL 5.7+ / MariaDB 10.3+. InnoDB throughout for foreign keys and
-- transactions. utf8mb4 so Devanagari (कॅफे अजवा) and emoji both store safely.
--
-- Relationship spine:
--   categories -> menu_items -> menu_item_variants -> order_items -> orders
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Restaurant settings. One row, id = 1. Lets the owner change the phone number
-- or delivery charge from the admin panel instead of editing PHP.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS restaurant_settings;
CREATE TABLE restaurant_settings (
  id                  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  name                VARCHAR(120)  NOT NULL,
  tagline             VARCHAR(255)  DEFAULT NULL,
  address_line        VARCHAR(255)  NOT NULL,
  locality            VARCHAR(120)  NOT NULL,
  city                VARCHAR(120)  NOT NULL,
  region              VARCHAR(120)  NOT NULL,
  postcode            VARCHAR(12)   DEFAULT NULL,
  phone               VARCHAR(20)   DEFAULT NULL,
  whatsapp            VARCHAR(20)   DEFAULT NULL,
  email               VARCHAR(160)  DEFAULT NULL,
  maps_url            VARCHAR(500)  DEFAULT NULL,
  latitude            DECIMAL(10,7) DEFAULT NULL,
  longitude           DECIMAL(10,7) DEFAULT NULL,
  instagram_url       VARCHAR(255)  DEFAULT NULL,
  facebook_url        VARCHAR(255)  DEFAULT NULL,
  delivery_charge     DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  free_delivery_over  DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  min_order_value     DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  is_accepting_orders TINYINT(1)    NOT NULL DEFAULT 1,
  is_accepting_bookings TINYINT(1)  NOT NULL DEFAULT 1,
  updated_at          DATETIME      DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT chk_settings_single_row CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Opening hours, one row per day. Separate table because a day can be closed,
-- and because a split shift (lunch / dinner) may need two rows later.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS opening_hours;
CREATE TABLE opening_hours (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  open_time   TIME       DEFAULT NULL,
  close_time  TIME       DEFAULT NULL,
  is_closed   TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_hours_day (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Menu
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(120) NOT NULL,
  slug          VARCHAR(120) NOT NULL,
  blurb         VARCHAR(255) NOT NULL DEFAULT '',
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_category_slug (slug),
  KEY idx_category_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS menu_items;
CREATE TABLE menu_items (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id   INT UNSIGNED NOT NULL,
  name          VARCHAR(160) NOT NULL,
  -- The exact string as it appeared on the restaurant's existing listing.
  -- Kept so a display-spelling correction is always traceable to its source.
  source_name   VARCHAR(160) NOT NULL DEFAULT '',
  slug          VARCHAR(160) NOT NULL,
  description   VARCHAR(400) NOT NULL DEFAULT '',
  food_type     ENUM('veg','nonveg','egg') NOT NULL DEFAULT 'veg',
  is_bestseller TINYINT(1)   NOT NULL DEFAULT 0,
  is_spicy      TINYINT(1)   NOT NULL DEFAULT 0,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at    DATETIME     DEFAULT NULL,
  updated_at    DATETIME     DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_item_slug (slug),
  KEY idx_item_category (category_id, display_order),
  KEY idx_item_type (food_type),
  KEY idx_item_active (is_active),
  CONSTRAINT fk_item_category FOREIGN KEY (category_id)
    REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per sellable portion. A tandoori chicken has Half and Full; a roti
-- has one row labelled Regular. A NULL price means "price not set yet", which
-- the site renders as "On request" rather than as a zero.
DROP TABLE IF EXISTS menu_item_variants;
CREATE TABLE menu_item_variants (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  menu_item_id  INT UNSIGNED NOT NULL,
  label         VARCHAR(40)  NOT NULL DEFAULT 'Regular',
  price         DECIMAL(8,2) DEFAULT NULL,
  is_default    TINYINT(1)   NOT NULL DEFAULT 0,
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_variant_item (menu_item_id, display_order),
  CONSTRAINT fk_variant_item FOREIGN KEY (menu_item_id)
    REFERENCES menu_items (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS menu_item_images;
CREATE TABLE menu_item_images (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  menu_item_id INT UNSIGNED NOT NULL,
  file_path    VARCHAR(255) NOT NULL,
  alt_text     VARCHAR(255) NOT NULL DEFAULT '',
  is_primary   TINYINT(1)   NOT NULL DEFAULT 0,
  uploaded_at  DATETIME     DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_image_item (menu_item_id, is_primary),
  CONSTRAINT fk_image_item FOREIGN KEY (menu_item_id)
    REFERENCES menu_items (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Customers and orders
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS customers;
CREATE TABLE customers (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(120) NOT NULL,
  phone      VARCHAR(20)  NOT NULL,
  email      VARCHAR(160) DEFAULT NULL,
  created_at DATETIME     DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_customer_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id     INT UNSIGNED NOT NULL,
  order_number    VARCHAR(20)  NOT NULL,
  order_type      ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery',
  address_line    VARCHAR(255) DEFAULT NULL,
  landmark        VARCHAR(160) DEFAULT NULL,
  pincode         VARCHAR(10)  DEFAULT NULL,
  subtotal        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  delivery_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  grand_total     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status          ENUM('pending','confirmed','preparing','ready','out_for_delivery','delivered','cancelled')
                  NOT NULL DEFAULT 'pending',
  -- Payment is not implemented. The column exists so a gateway can be added
  -- later without a migration on a live orders table.
  payment_status  ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  payment_method  VARCHAR(40)  DEFAULT NULL,
  notes           VARCHAR(500) DEFAULT NULL,
  created_at      DATETIME     DEFAULT NULL,
  updated_at      DATETIME     DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_order_number (order_number),
  KEY idx_order_customer (customer_id),
  KEY idx_order_status (status, created_at),
  CONSTRAINT fk_order_customer FOREIGN KEY (customer_id)
    REFERENCES customers (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Name and price are SNAPSHOT columns. When the owner raises a price, past
-- orders must still show what the customer actually agreed to pay, so these
-- are copied at order time rather than joined live to menu_items.
DROP TABLE IF EXISTS order_items;
CREATE TABLE order_items (
  id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id               INT UNSIGNED NOT NULL,
  menu_item_id           INT UNSIGNED DEFAULT NULL,
  variant_id             INT UNSIGNED DEFAULT NULL,
  item_name_snapshot     VARCHAR(160) NOT NULL,
  variant_label_snapshot VARCHAR(40)  NOT NULL DEFAULT 'Regular',
  unit_price_snapshot    DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  quantity               SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  line_total             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id),
  KEY idx_orderitem_order (order_id),
  CONSTRAINT fk_orderitem_order FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_orderitem_item FOREIGN KEY (menu_item_id)
    REFERENCES menu_items (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_orderitem_variant FOREIGN KEY (variant_id)
    REFERENCES menu_item_variants (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Bookings, offers, reviews, messages
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS table_bookings;
CREATE TABLE table_bookings (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name            VARCHAR(120) NOT NULL,
  phone           VARCHAR(20)  NOT NULL,
  booking_date    DATE         NOT NULL,
  booking_time    TIME         NOT NULL,
  guests          SMALLINT UNSIGNED NOT NULL DEFAULT 2,
  special_request VARCHAR(500) DEFAULT NULL,
  status          ENUM('requested','confirmed','seated','cancelled','no_show')
                  NOT NULL DEFAULT 'requested',
  admin_note      VARCHAR(400) DEFAULT NULL,
  created_at      DATETIME     DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_booking_when (booking_date, booking_time),
  KEY idx_booking_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Empty by design. The restaurant supplied no offers, so none were invented.
DROP TABLE IF EXISTS offers;
CREATE TABLE offers (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title       VARCHAR(160) NOT NULL,
  description VARCHAR(500) NOT NULL DEFAULT '',
  terms       VARCHAR(600) NOT NULL DEFAULT '',
  valid_from  DATE         DEFAULT NULL,
  valid_to    DATE         DEFAULT NULL,
  cta_label   VARCHAR(60)  DEFAULT NULL,
  cta_url     VARCHAR(255) DEFAULT NULL,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_offer_active (is_active, valid_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Also empty by design. Reviews must be written by real customers and
-- approved by an admin before they appear.
DROP TABLE IF EXISTS reviews;
CREATE TABLE reviews (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_name VARCHAR(120) NOT NULL,
  rating        TINYINT UNSIGNED NOT NULL,
  review_text   VARCHAR(1000) NOT NULL,
  is_approved   TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    DATETIME     DEFAULT NULL,
  approved_at   DATETIME     DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_review_approved (is_approved, created_at),
  CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS contact_messages;
CREATE TABLE contact_messages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(120)  NOT NULL,
  phone      VARCHAR(20)   NOT NULL,
  email      VARCHAR(160)  DEFAULT NULL,
  message    VARCHAR(1000) NOT NULL,
  is_read    TINYINT(1)    NOT NULL DEFAULT 0,
  created_at DATETIME      DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_message_read (is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Admin accounts. password_hash holds a bcrypt digest, never a plain password.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS admins;
CREATE TABLE admins (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(60)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  display_name  VARCHAR(120) NOT NULL DEFAULT '',
  role          ENUM('owner','manager','staff') NOT NULL DEFAULT 'staff',
  last_login    DATETIME     DEFAULT NULL,
  created_at    DATETIME     DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Baseline settings row. Contact details are left NULL on purpose: the site
-- renders "to be confirmed" rather than a plausible-looking fake.
-- -----------------------------------------------------------------------------
INSERT INTO restaurant_settings
  (id, name, tagline, address_line, locality, city, region,
   delivery_charge, is_accepting_orders, is_accepting_bookings, updated_at)
VALUES
  (1, 'Cafe Ajwaa', 'Tandoor, biryani and Chinese in Malwani',
   'Gate 8, Gaikwad Nagar Bus Depot, Malwani Colony',
   'Malad West', 'Mumbai', 'Maharashtra',
   40.00, 1, 1, NOW());

-- No admin account is created here. Run sql/create-admin.php from the command
-- line so the password is hashed at creation time and never sits in a .sql file.
