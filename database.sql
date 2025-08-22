-- -- Create a DB for Matchy Matchy (change name if you already have one)
-- CREATE DATABASE IF NOT EXISTS matchy_matchy
--   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE matchy_matchy;
--
-- -- Users table (compatible with local signup + Google later)
-- CREATE TABLE IF NOT EXISTS users (
--                                      id                     INT AUTO_INCREMENT PRIMARY KEY,
--                                      first_name             VARCHAR(50)  NOT NULL,
--     last_name              VARCHAR(50)  NOT NULL,
--   --  username               VARCHAR(50)  NOT NULL UNIQUE,
--     email                  VARCHAR(255) NOT NULL UNIQUE,
--     password               VARCHAR(255) NULL,         -- stores hash for local signups; NULL for social
--     avatar                 VARCHAR(255) NULL,
--     provider               ENUM('local','google') NOT NULL DEFAULT 'local',
--     google_id              VARCHAR(64)  NULL UNIQUE,
--     email_verified_at      DATETIME NULL,
--     created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP
--     ) ENGINE=InnoDB;
--
--
-- USE matchy_matchy;
--
-- ALTER TABLE users
--     ADD UNIQUE KEY uniq_email (email),
--     ADD UNIQUE KEY uniq_google_id (google_id);
-- -- Holds 6-digit verification codes (hashed)
--
--
-- CREATE TABLE IF NOT EXISTS email_verifications (
--                                                    id             INT AUTO_INCREMENT PRIMARY KEY,
--                                                    email          VARCHAR(255) NOT NULL UNIQUE,
--     code_hash      VARCHAR(255) NOT NULL,  -- store a *hashed* code
--     token          CHAR(64)     NOT NULL,  -- server token you’ll give the browser after verify
--     expires_at     DATETIME     NOT NULL,  -- code valid window (e.g., 10 min)
--     verified_at    DATETIME     NULL,
--     send_count     INT          NOT NULL DEFAULT 1,
--     attempt_count  INT          NOT NULL DEFAULT 0,
--     last_sent_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
--     ) ENGINE=InnoDB;
--
--
--
-- CREATE TABLE IF NOT EXISTS magic_links (
--                                            id          INT AUTO_INCREMENT PRIMARY KEY,
--                                            user_id     INT NOT NULL,
--                                            token       CHAR(64) NOT NULL UNIQUE,
--     purpose     ENUM('login','welcome','set_password') NOT NULL DEFAULT 'set_password',
--     expires_at  DATETIME NOT NULL,
--     used_at     DATETIME NULL,
--     created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     CONSTRAINT fk_magic_links_user
--     FOREIGN KEY (user_id) REFERENCES users(id)
--     ON DELETE CASCADE
--     ) ENGINE=InnoDB;
--
--
--
-- -- One-time migration
-- CREATE TABLE IF NOT EXISTS pending_google_signups (
--                                                       id              INT AUTO_INCREMENT PRIMARY KEY,
--                                                       google_id       VARCHAR(64)  NOT NULL UNIQUE,
--     email           VARCHAR(255) NOT NULL UNIQUE,
--     first_name      VARCHAR(50)  NOT NULL,
--     last_name       VARCHAR(50)  NOT NULL,
--     avatar          VARCHAR(255) NULL,
--     email_verified  TINYINT(1)   NOT NULL DEFAULT 0,
--     token_hash      CHAR(64)     NOT NULL,         -- SHA-256 of opaque token
--     expires_at      DATETIME     NOT NULL,
--     created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
--     ) ENGINE=InnoDB;
--
--
-- CREATE TABLE IF NOT EXISTS login_attempts (
--                                               email           VARCHAR(255) PRIMARY KEY,
--     fail_count      INT NOT NULL DEFAULT 0,
--     locked_until    DATETIME NULL,
--     last_failed_at  TIMESTAMP NULL DEFAULT NULL
--     ) ENGINE=InnoDB;


ALTER TABLE magic_links
    MODIFY purpose ENUM('login','welcome','set_password','reset_password')
    NOT NULL DEFAULT 'set_password';


ALTER TABLE users
    ADD COLUMN IF NOT EXISTS phone      VARCHAR(30) NULL,ADD COLUMN IF NOT EXISTS notes       TEXT NULL;





-- matchy_matchy schema (add to your DB init)
CREATE TABLE IF NOT EXISTS orders (
                                      id              INT AUTO_INCREMENT PRIMARY KEY,
                                      public_id       VARCHAR(32) NOT NULL UNIQUE,              -- e.g., ORD-2025-001
    order_date      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    customer_name   VARCHAR(100) NOT NULL,
    customer_email  VARCHAR(255) NOT NULL,
    customer_phone  VARCHAR(30)  NULL,
    customer_address TEXT        NULL,

    subtotal        DECIMAL(10,2) NOT NULL DEFAULT 0.00,      -- exact money math
    shipping        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total           DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    payment_status  ENUM('Paid','Pending','Failed','Refunded','COD') NOT NULL DEFAULT 'Pending',
    order_status    ENUM('Pending','Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',

    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_date (order_date),
    KEY idx_status (order_status),
    KEY idx_payment (payment_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
                                           id           INT AUTO_INCREMENT PRIMARY KEY,
                                           order_id     INT NOT NULL,
                                           product_id   INT NULL,                       -- optional (if you later link to products table)
                                           product_name VARCHAR(255) NOT NULL,
    unit_price   DECIMAL(10,2) NOT NULL,
    quantity     INT NOT NULL,
    -- keep line_total explicit to avoid surprises if price later changes
    line_total   DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_items_order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    KEY idx_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;






-- Coupons: percentage = fraction (e.g., 0.10); fixed = NIS amount
CREATE TABLE IF NOT EXISTS coupons (
                                       id            INT AUTO_INCREMENT PRIMARY KEY,
                                       code          VARCHAR(32) NOT NULL UNIQUE,
    type          ENUM('percentage','fixed') NOT NULL,
    amount        DECIMAL(10,2) NOT NULL,     -- ex: 0.10 for 10% OR 20.00 fixed
    min_subtotal  DECIMAL(10,2) NOT NULL DEFAULT 0,
    starts_at     DATETIME NULL,
    ends_at       DATETIME NULL,
    active        TINYINT(1) NOT NULL DEFAULT 1,
    max_uses      INT NULL,
    used_count    INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Carts are per session (works for anonymous users; attach user_id if you have login)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE carts (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       user_id INT NOT NULL UNIQUE,  -- must match users.id type/sign
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                       CONSTRAINT fk_carts_user
                           FOREIGN KEY (user_id) REFERENCES users(id)
                               ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cart_items (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            cart_id INT NOT NULL,
                            product_id INT NOT NULL,
                            size VARCHAR(64) NOT NULL DEFAULT '',
                            quantity INT NOT NULL,
                            unit_price DECIMAL(10,2) NOT NULL,
                            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            UNIQUE KEY uniq_cart_product (cart_id, product_id, size),
                            CONSTRAINT fk_items_cart
                                FOREIGN KEY (cart_id) REFERENCES carts(id)
                                    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ORDERS: رأس الطلب
CREATE TABLE IF NOT EXISTS orders (
                                      id              INT AUTO_INCREMENT PRIMARY KEY,
                                      public_id       VARCHAR(32) NOT NULL UNIQUE,               -- مثل: ORD-2025-000123
    user_id         INT NULL,                                   -- ربط اختياري مع جدول users
    order_date      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    customer_name   VARCHAR(100) NOT NULL,
    customer_email  VARCHAR(255) NOT NULL,
    customer_phone  VARCHAR(30)  NULL,
    customer_address TEXT        NULL,

    subtotal        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total           DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    payment_status  ENUM('Paid','Pending','Failed','Refunded','COD') NOT NULL DEFAULT 'Pending',
    order_status    ENUM('Pending','Processing','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',

    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_date (order_date),
    KEY idx_status (order_status),
    KEY idx_payment (payment_status),
    KEY idx_public_id (public_id),
    CONSTRAINT fk_orders_user
    FOREIGN KEY (user_id) REFERENCES users(id)
                                                                 ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ORDER ITEMS: تفاصيل العناصر
CREATE TABLE IF NOT EXISTS order_items (
                                           id           INT AUTO_INCREMENT PRIMARY KEY,
                                           order_id     INT NOT NULL,
                                           product_id   INT NULL,                        -- اختياري (لو عندك جدول products)
                                           product_name VARCHAR(255) NOT NULL,           -- نخزن الاسم كما هو لحظة الشراء
    size         VARCHAR(64)  NOT NULL DEFAULT '',-- نخزن المقاس المختار
    unit_price   DECIMAL(10,2) NOT NULL,          -- السعر وقت الشراء
    quantity     INT NOT NULL,
    line_total   DECIMAL(10,2) NOT NULL,          -- unit_price * quantity

    CONSTRAINT fk_items_order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    KEY idx_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



ALTER TABLE product_reviews
    ADD COLUMN hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER comment,
  ADD COLUMN hidden_by INT UNSIGNED NULL AFTER hidden,
  ADD COLUMN hidden_at DATETIME NULL AFTER hidden_by,
  ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

CREATE INDEX idx_reviews_hidden  ON product_reviews(hidden);
CREATE INDEX idx_reviews_created ON product_reviews(created_at);
CREATE INDEX idx_reviews_product ON product_reviews(product_id);


ALTER TABLE product_reviews
    ADD COLUMN flagged TINYINT(1) NOT NULL DEFAULT 0 AFTER hidden,
  ADD COLUMN flag_reason VARCHAR(255) NULL AFTER flagged,
  ADD COLUMN flagged_by INT UNSIGNED NULL AFTER flag_reason,
  ADD COLUMN flagged_at DATETIME NULL AFTER flagged_by;

