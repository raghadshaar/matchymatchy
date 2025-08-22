-- Database
CREATE DATABASE IF NOT EXISTS matchy_matchy
  CHARACTER SET utf8 COLLATE utf8_general_ci;
USE matchy_matchy;

-- ========================
-- Categories (هرمية)
-- ========================
CREATE TABLE IF NOT EXISTS categories (
                                          id          INT AUTO_INCREMENT PRIMARY KEY,
                                          name        VARCHAR(100)  NOT NULL,
    slug        VARCHAR(120)  NOT NULL UNIQUE,
    parent_id   INT NULL,
    description TEXT,
    image_url   VARCHAR(255),
    status      ENUM('active','hidden') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cat_parent
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE INDEX idx_categories_parent ON categories(parent_id);

-- ========================
-- Products
-- ========================
CREATE TABLE IF NOT EXISTS products (
                                        id          INT AUTO_INCREMENT PRIMARY KEY,
                                        name        VARCHAR(160)  NOT NULL,
    slug        VARCHAR(180)  NOT NULL UNIQUE,
    sku         VARCHAR(60)   UNIQUE,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL CHECK (price >= 0),
    currency    CHAR(3)       NOT NULL DEFAULT 'ILS',
    stock       INT           NOT NULL DEFAULT 0 CHECK (stock >= 0),
    status      ENUM('auto','in_stock','low_stock','out_of_stock','draft','archived')
    NOT NULL DEFAULT 'auto',
    image_main_url VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ربط متعدد-لمتعدد بين المنتجات والفئات
CREATE TABLE IF NOT EXISTS product_categories (
                                                  product_id  INT NOT NULL,
                                                  category_id INT NOT NULL,
                                                  PRIMARY KEY (product_id, category_id),
    CONSTRAINT fk_pc_product  FOREIGN KEY (product_id)  REFERENCES products(id)   ON DELETE CASCADE,
    CONSTRAINT fk_pc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- صور إضافية للمنتج
CREATE TABLE IF NOT EXISTS product_images (
                                              id         INT AUTO_INCREMENT PRIMARY KEY,
                                              product_id INT NOT NULL,
                                              image_url  VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    alt_text   VARCHAR(255),
    CONSTRAINT fk_pi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
CREATE INDEX idx_pi_product_sort ON product_images(product_id, sort_order);

-- مقاسات المنتج
CREATE TABLE IF NOT EXISTS product_sizes (
                                             id         INT AUTO_INCREMENT PRIMARY KEY,
                                             product_id INT NOT NULL,
                                             size_label VARCHAR(30) NOT NULL,
    CONSTRAINT uq_product_size UNIQUE (product_id, size_label),
    CONSTRAINT fk_ps_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- View لحالة المنتج
CREATE OR REPLACE VIEW v_products_effective AS
SELECT
    p.*,
    CASE
        WHEN p.status <> 'auto' THEN p.status
        WHEN p.stock <= 0 THEN 'out_of_stock'
        WHEN p.stock < 20 THEN 'low_stock'
        ELSE 'in_stock'
        END AS status_effective
FROM products p;



-- تقييمات المستخدمين (1-5 نجوم + تعليق اختياري)
CREATE TABLE IF NOT EXISTS product_reviews (
                                               id         INT AUTO_INCREMENT PRIMARY KEY,
                                               product_id INT NOT NULL,
                                               user_id    INT NULL,              -- لو عندك جدول users؛ وإلا خليه NULL
                                               rating     TINYINT NOT NULL,      -- 1..5 (ملاحظة: MariaDB قد يتجاهل CHECK)
                                               comment    TEXT NULL,
                                               created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                               CONSTRAINT fk_pr_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    );
CREATE INDEX idx_pr_product ON product_reviews(product_id);

-- لايكات (من مستخدم مسجل أو جهاز مجهول)
CREATE TABLE IF NOT EXISTS product_likes (
                                             id          INT AUTO_INCREMENT PRIMARY KEY,
                                             product_id  INT NOT NULL,
                                             user_id     INT NULL,               -- لو مسجل
                                             device_hash VARCHAR(64) NULL,       -- fingerprint/كوكي للزوار
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pl_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT uq_like_user   UNIQUE (product_id, user_id),
    CONSTRAINT uq_like_device UNIQUE (product_id, device_hash)
    );
CREATE INDEX idx_pl_product ON product_likes(product_id);





