Database
CREATE DATABASE IF NOT EXISTS matchy_matchy
   CHARACTER SET utf8 COLLATE utf8_general_ci;
USE matchy_matchy;

-- ========================
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





CREATE TABLE IF NOT EXISTS product_types (
                                             id INT AUTO_INCREMENT PRIMARY KEY,
                                             name VARCHAR(50) NOT NULL UNIQUE
    );

ALTER TABLE products
    ADD COLUMN type_id INT NULL,
  ADD CONSTRAINT fk_products_type
    FOREIGN KEY (type_id) REFERENCES product_types(id)
    ON DELETE SET NULL;

INSERT IGNORE INTO product_types (name) VALUES
('Blouse'), ('Pajama'), ('Pants'), ('Skirt'), ('Dress'), ('T-shirt'), ('Set');

-- ===== FABRICS: متعدد =====
CREATE TABLE IF NOT EXISTS fabric_options (
                                              id INT AUTO_INCREMENT PRIMARY KEY,
                                              name VARCHAR(50) NOT NULL UNIQUE
    );

CREATE TABLE IF NOT EXISTS product_fabrics (
                                               product_id INT NOT NULL,
                                               fabric_id  INT NOT NULL,
                                               PRIMARY KEY (product_id, fabric_id),
    CONSTRAINT fk_pf_p FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_pf_f FOREIGN KEY (fabric_id)  REFERENCES fabric_options(id) ON DELETE CASCADE
    );

-- أمثلة أقمشة
INSERT IGNORE INTO fabric_options (name) VALUES
('Cotton'), ('Organic Cotton'), ('Bamboo'), ('Modal'), ('Polyester');

-- ===== COLORS: متعدد =====
CREATE TABLE IF NOT EXISTS color_options (
                                             id INT AUTO_INCREMENT PRIMARY KEY,
                                             name VARCHAR(40) NOT NULL UNIQUE,
    hex  CHAR(7) NULL
    );

CREATE TABLE IF NOT EXISTS product_colors (
                                              product_id INT NOT NULL,
                                              color_id   INT NOT NULL,
                                              PRIMARY KEY (product_id, color_id),
    CONSTRAINT fk_pc_p FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_pc_c FOREIGN KEY (color_id)   REFERENCES color_options(id) ON DELETE CASCADE
    );

-- أمثلة ألوان
INSERT IGNORE INTO color_options (name, hex) VALUES
('White','#FFFFFF'),('Black','#000000'),('Pink','#FFC0CB'),
('Blue','#0000FF'),('Green','#008000'),('Ivory','#FFFFF0');
-- type
UPDATE products SET type_id = (SELECT id FROM product_types WHERE name='Set')
WHERE slug IN ('baby-boy-casual-outfit-set','toddler-disney-pajama-set-boys','family-matching-pajama-set');

UPDATE products SET type_id = (SELECT id FROM product_types WHERE name='Dress')
WHERE slug IN ('baby-girl-ruffle-dress','yellow-ruffle-party-dress','girls-summer-floral-dress');

UPDATE products SET type_id = (SELECT id FROM product_types WHERE name='T-shirt')
WHERE slug IN ('baby-boy-striped-pocket-tee-green');

-- fabrics
INSERT IGNORE INTO product_fabrics(product_id, fabric_id)
SELECT p.id, f.id
FROM products p JOIN fabric_options f ON f.name='Cotton'
WHERE p.slug IN ('baby-boy-casual-outfit-set','baby-boy-striped-pocket-tee-green',
                 'baby-girl-ruffle-dress','toddler-disney-pajama-set-boys','family-matching-pajama-set');

-- colors
INSERT IGNORE INTO product_colors(product_id, color_id)
SELECT p.id, c.id FROM products p JOIN color_options c ON c.name='Green'
WHERE p.slug='baby-boy-striped-pocket-tee-green';

INSERT IGNORE INTO product_colors(product_id, color_id)
SELECT p.id, c.id FROM products p JOIN color_options c ON c.name='Ivory'
WHERE p.slug='family-matching-pajama-set';

-- ========== Product Types ==========
UPDATE products SET type_id = (SELECT id FROM product_types WHERE name = 'Set')
WHERE slug IN ('baby-boy-casual-outfit-set', 'toddler-disney-pajama-set-boys', 'family-matching-pajama-set');

UPDATE products SET type_id = (SELECT id FROM product_types WHERE name = 'Dress')
WHERE slug IN ('baby-girl-ruffle-dress', 'yellow-ruffle-party-dress', 'girls-summer-floral-dress');

UPDATE products SET type_id = (SELECT id FROM product_types WHERE name = 'T-shirt')
WHERE slug IN ('baby-boy-striped-pocket-tee-green', 'baby-boy-graphic-tee-white');

UPDATE products SET type_id = (SELECT id FROM product_types WHERE name = 'Pants')
WHERE slug IN ('toddler-casual-jeans', 'baby-boy-soft-pants-navy');

UPDATE products SET type_id = (SELECT id FROM product_types WHERE name = 'Blouse')
WHERE slug IN ('toddler-girl-striped-blouse');

UPDATE products SET type_id = (SELECT id FROM product_types WHERE name = 'Skirt')
WHERE slug IN ('baby-girl-polka-skirt');

-- ========== Fabrics ==========
INSERT IGNORE INTO product_fabrics (product_id, fabric_id)
SELECT p.id, f.id FROM products p
                           JOIN fabric_options f ON f.name = 'Cotton'
WHERE p.slug IN (
                 'baby-boy-casual-outfit-set', 'baby-boy-striped-pocket-tee-green', 'baby-girl-ruffle-dress',
                 'toddler-disney-pajama-set-boys', 'family-matching-pajama-set', 'toddler-casual-jeans'
    );

INSERT IGNORE INTO product_fabrics (product_id, fabric_id)
SELECT p.id, f.id FROM products p
                           JOIN fabric_options f ON f.name = 'Bamboo'
WHERE p.slug IN (
                 'yellow-ruffle-party-dress', 'baby-boy-graphic-tee-white'
    );

INSERT IGNORE INTO product_fabrics (product_id, fabric_id)
SELECT p.id, f.id FROM products p
                           JOIN fabric_options f ON f.name = 'Modal'
WHERE p.slug IN ('toddler-girl-striped-blouse', 'baby-girl-polka-skirt');

-- ========== Colors ==========
INSERT IGNORE INTO product_colors (product_id, color_id)
SELECT p.id, c.id FROM products p
                           JOIN color_options c ON c.name = 'Green'
WHERE p.slug IN ('baby-boy-striped-pocket-tee-green');

INSERT IGNORE INTO product_colors (product_id, color_id)
SELECT p.id, c.id FROM products p
                           JOIN color_options c ON c.name = 'Pink'
WHERE p.slug IN ('baby-girl-ruffle-dress', 'baby-girl-polka-skirt');

INSERT IGNORE INTO product_colors (product_id, color_id)
SELECT p.id, c.id FROM products p
                           JOIN color_options c ON c.name = 'Yellow'
WHERE p.slug IN ('yellow-ruffle-party-dress');



INSERT IGNORE INTO product_colors (product_id, color_id)
SELECT p.id, c.id FROM products p
                           JOIN color_options c ON c.name = 'Ivory'
WHERE p.slug IN ('family-matching-pajama-set', 'baby-boy-soft-pants-navy');

INSERT IGNORE INTO product_colors (product_id, color_id)
SELECT p.id, c.id FROM products p
                           JOIN color_options c ON c.name = 'Blue'
WHERE p.slug IN ('toddler-disney-pajama-set-boys', 'toddler-casual-jeans');

INSERT IGNORE INTO product_colors (product_id, color_id)
SELECT p.id, c.id FROM products p
                           JOIN color_options c ON c.name = 'White'
WHERE p.slug IN ('baby-boy-graphic-tee-white', 'toddler-girl-striped-blouse');



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


CREATE OR REPLACE VIEW v_product_metrics AS
SELECT
    p.id AS product_id,
    r.rating_avg,
    r.rating_count,
    l.likes
FROM products p
         LEFT JOIN (
    SELECT product_id,
           ROUND(AVG(rating), 1) AS rating_avg,
           COUNT(*)              AS rating_count
    FROM product_reviews
    GROUP BY product_id
) r ON r.product_id = p.id
         LEFT JOIN (
    SELECT product_id,
           COUNT(*) AS likes
    FROM product_likes
    GROUP BY product_id
) l ON l.product_id = p.id;


ALTER TABLE product_reviews
    ADD UNIQUE KEY uq_pr_user (product_id, user_id),
    ADD UNIQUE KEY uq_pr_dev  (product_id, device_hash);






CREATE OR REPLACE VIEW v_product_metrics AS
SELECT
    p.id AS product_id,
    r.rating_avg,
    r.rating_count,
    l.likes
FROM products p
         LEFT JOIN (
    SELECT product_id,
           ROUND(AVG(rating), 1) AS rating_avg,
           COUNT(*)              AS rating_count
    FROM product_reviews
    GROUP BY product_id
) r ON r.product_id = p.id
         LEFT JOIN (
    SELECT product_id,
           COUNT(*) AS likes
    FROM product_likes
    GROUP BY product_id
) l ON l.product_id = p.id;
ALTER TABLE product_reviews
    ADD COLUMN device_hash VARCHAR(64) NULL AFTER user_id;


ALTER TABLE product_reviews
    ADD CONSTRAINT uq_pr_device UNIQUE (product_id, device_hash);

CREATE INDEX idx_pr_device ON product_reviews(device_hash);
CREATE INDEX idx_pr_user    ON product_reviews(user_id);

ALTER TABLE product_reviews
    ADD COLUMN device_hash VARCHAR(64) NULL AFTER user_id;

ALTER TABLE product_reviews
    ADD CONSTRAINT uq_pr_device UNIQUE (product_id, device_hash);

CREATE INDEX idx_pr_device ON product_reviews(device_hash);
CREATE INDEX idx_pr_user    ON product_reviews(user_id);
USE matchy_matchy;

START TRANSACTION;
ALTER TABLE product_reviews
    ADD UNIQUE KEY uq_pr_user (product_id, user_id),
    ADD UNIQUE KEY uq_pr_dev  (product_id, device_hash);
WITH d AS (
    SELECT id,
           ROW_NUMBER() OVER (PARTITION BY product_id, user_id ORDER BY created_at DESC, id DESC) AS rn
    FROM product_reviews
    WHERE user_id IS NOT NULL
)
DELETE pr FROM product_reviews pr
JOIN d ON pr.id = d.id
WHERE d.rn > 1;

WITH d AS (
    SELECT id,
           ROW_NUMBER() OVER (PARTITION BY product_id, device_hash ORDER BY created_at DESC, id DESC) AS rn
    FROM product_reviews
    WHERE device_hash IS NOT NULL
)
DELETE pr FROM product_reviews pr
JOIN d ON pr.id = d.id
WHERE d.rn > 1;



INSERT INTO products
(name, slug, sku, description, price, stock, image_main_url)
SELECT
    'Toddler Boy Construction Alphabet Long-Sleeve Graphic Tee - Blue',
    'toddler-boy-construction-alphabet-tee-blue',
    'TB-ALPHA-TEE-BLUE-001',
    CONCAT(
            'About This Item', '\n',
            'Learning the alphabet just got a whole lot louder - in a good way. ',
            'With big construction vehicles and bold letters, this tee brings the fun to preschool basics. ',
            'Whether it''s truck sounds or spelling practice, this soft cotton blend keeps up with whatever the day digs up. ',
            'A relaxed fit and long sleeves make it easy for layering or wear-it-on-repeat comfort.', '\n',
            'Style # TB-ALPHA-TEE-BLUE-001', '\n',
            'Features', '\n',
            'Long sleeves for all-season layering or solo wear', '\n',
            'Relaxed fit with straight silhouette for everyday movement', '\n',
            'Pullover style with a classic crew neck', '\n',
            'Features construction trucks paired with alphabet letters', '\n',
            'Hits at the hip for easy pairing with joggers or jeans', '\n',
            'Designed for casual playtime or preschool-ready outfits', '\n',
            'Graphic style with bold vehicle and typography motifs'
    ),
    59.90,
    120,
    '../images/Toddler Boy Construction Alphabet Long-Sleeve Graphic Tee - Blue.jpg'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM products WHERE slug = 'toddler-boy-construction-alphabet-tee-blue'
);
INSERT IGNORE INTO product_categories(product_id, category_id)
SELECT
    (SELECT id FROM products   WHERE slug='toddler-boy-construction-alphabet-tee-blue'),
    (SELECT id FROM categories WHERE slug='toddler-boy');

-- مقاسات
INSERT IGNORE INTO product_sizes(product_id,size_label)
SELECT id,'2T' FROM products WHERE slug='toddler-boy-construction-alphabet-tee-blue';
INSERT IGNORE INTO product_sizes(product_id,size_label)
SELECT id,'3T' FROM products WHERE slug='toddler-boy-construction-alphabet-tee-blue';
INSERT IGNORE INTO product_sizes(product_id,size_label)
SELECT id,'4T' FROM products WHERE slug='toddler-boy-construction-alphabet-tee-blue';

INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '../images/Toddler Boy Construction Alphabet Long-Sleeve Graphic Tee - Blue.jpg', 0, 'Front'
FROM products WHERE slug='toddler-boy-construction-alphabet-tee-blue'
    ON DUPLICATE KEY UPDATE image_url = VALUES(image_url);

INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '../images/Toddler Boy Construction Alphabet Long-Sleeve Graphic Tee - Blue2.jpg', 1, 'Detail'
FROM products WHERE slug='toddler-boy-construction-alphabet-tee-blue'
    ON DUPLICATE KEY UPDATE image_url = VALUES(image_url);






-- USE matchy_matchy;
-- START TRANSACTION;
--
-- -- ========================
-- -- Parents
-- -- ========================
-- INSERT INTO categories (name, slug, status)
-- SELECT 'Baby','baby','active' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby');
--
-- INSERT INTO categories (name, slug, status)
-- SELECT 'Toddler','toddler','active' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toddler');
--
-- INSERT INTO categories (name, slug, status)
-- SELECT 'Kids','kids','active' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='kids');
--
-- INSERT INTO categories (name, slug, status)
-- SELECT 'Toys','toys','active' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toys');
--
-- INSERT INTO categories (name, slug, status)
-- SELECT 'Deals','deals','active' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='deals');
--
-- INSERT INTO categories (name, slug, status)
-- SELECT 'Collections','collections','active' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='collections');
--
-- -- ========================
-- -- Children: Baby
-- -- ========================
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Baby Girl','baby-girl',(SELECT id FROM categories WHERE slug='baby'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby-girl');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Baby Boy','baby-boy',(SELECT id FROM categories WHERE slug='baby'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby-boy');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Baby Neutral','baby-neutral',(SELECT id FROM categories WHERE slug='baby'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby-neutral');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Baby Gear','baby-gear',(SELECT id FROM categories WHERE slug='baby'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby-gear');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Baby Gifts','baby-gifts',(SELECT id FROM categories WHERE slug='baby'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby-gifts');
--
-- -- ========================
-- -- Children: Toddler
-- -- ========================
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Toddler Girl','toddler-girl',(SELECT id FROM categories WHERE slug='toddler'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toddler-girl');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Toddler Boy','toddler-boy',(SELECT id FROM categories WHERE slug='toddler'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toddler-boy');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Toddler Gear','toddler-gear',(SELECT id FROM categories WHERE slug='toddler'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toddler-gear');
--
-- -- ========================
-- -- Children: Kids
-- -- ========================
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Girls','kids-girls',(SELECT id FROM categories WHERE slug='kids'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='kids-girls');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Boys','kids-boys',(SELECT id FROM categories WHERE slug='kids'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='kids-boys');
--
-- -- ========================
-- -- Children: Toys (3 فقط)
-- -- ========================
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Learning & Educational','toys-learning',(SELECT id FROM categories WHERE slug='toys'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toys-learning');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Outdoor Toys','toys-outdoor',(SELECT id FROM categories WHERE slug='toys'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toys-outdoor');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Puzzles & Games','toys-puzzles-games',(SELECT id FROM categories WHERE slug='toys'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toys-puzzles-games');
--
-- -- ========================
-- -- Children: Deals
-- -- ========================
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Flash Sale','flash-sale',(SELECT id FROM categories WHERE slug='deals'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='flash-sale');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Clearance','clearance',(SELECT id FROM categories WHERE slug='deals'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='clearance');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Bundle Offers','bundle-offers',(SELECT id FROM categories WHERE slug='deals'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='bundle-offers');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Weekly Specials','weekly-specials',(SELECT id FROM categories WHERE slug='deals'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='weekly-specials');
--
-- -- ========================
-- -- Children: Collections
-- -- ========================
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Family Matching','family-matching',(SELECT id FROM categories WHERE slug='collections'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='family-matching');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'New Arrivals','new-arrivals',(SELECT id FROM categories WHERE slug='collections'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='new-arrivals');
--
-- INSERT INTO categories (name, slug, parent_id, status)
-- SELECT 'Best Sellers','best-sellers',(SELECT id FROM categories WHERE slug='collections'),'active'
--     WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='best-sellers');
--
-- -- ============================================================================
-- -- ============================================================================
--
-- -- 1) Family Matching Pajama Set
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Family Matching Pajama Set','family-matching-pajama-set','PJ-FAM-001',
--        'Cozy matching PJs for the whole family.',189.90,64,'../images/familymatching1.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='family-matching-pajama-set');
--
-- -- 2) Kids Sleeveless Summer Jumpsuit
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Kids Sleeveless Summer Jumpsuit','kids-sleeveless-summer-jumpsuit','KJ-002',
--        'Light and comfy for hot days.',129.90,48,'../images/kidsjumpsuit.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='kids-sleeveless-summer-jumpsuit');
--
-- -- 3) Toddler Disney Pajama Set
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Toddler Disney Pajama Set','toddler-disney-pajama-set','TD-DISNEY-003',
--        'Cute Disney-themed sleep set for toddlers.',89.00,18,'../images/toddlerdineypjset.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='toddler-disney-pajama-set');
--
-- -- 4) Baby Boy Casual Outfit Set
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Baby Boy Casual Outfit Set','baby-boy-casual-outfit-set','BB-SET-004',
--        'Everyday comfy set for baby boys.',99.90,25,'../images/babyboyclothes.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='baby-boy-casual-outfit-set');
--
-- -- 5) Baby Girl Ruffle Dress
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Baby Girl Ruffle Dress','baby-girl-ruffle-dress','BG-DRESS-005',
--        'Sweet ruffle dress for baby girls.',79.90,145,'../images/babygirldress.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='baby-girl-ruffle-dress');
--
-- -- 6) Classic Baby Girl Dress – White Lace
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Classic Baby Girl Dress – White Lace','classic-baby-girl-dress-white-lace','BG-DRESS-006',
--        'Classic lace look for special days.',84.90,58,'../images/product1.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='classic-baby-girl-dress-white-lace');
--
-- -- 7) Yellow Ruffle Party Dress
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Yellow Ruffle Party Dress','yellow-ruffle-party-dress','YR-DRESS-007',
--        'Bright yellow party dress with ruffles.',89.90,92,'../images/yellowdress.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='yellow-ruffle-party-dress');
--
-- -- 8) Girls Summer Floral Dress
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Girls Summer Floral Dress','girls-summer-floral-dress','SF-DRESS-008',
--        'Light floral summer dress.',99.00,31,'../images/summerdess.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='girls-summer-floral-dress');
--
-- -- 9) Girls cute pink cardigan
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Girls cute pink cardigan','girls-cute-pink-cardigan','PD-Pink-009',
--        'Girls cute pink cardigan',119.90,22,'../images/similar3.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='girls-cute-pink-cardigan');
--
-- -- 10) Pink girl shorts
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Pink girl shorts','pink-girl-shorts','ACC-BOW-010',
--        '',34.90,12,'../images/similar4.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='pink-girl-shorts');
--
-- -- 11) Toddler Disney Pajama Set (Boys)
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Toddler Disney Pajama Set (Boys)','toddler-disney-pajama-set-boys','boy-set',
--        'Casual style set for boys.',150.00,100,'../images/disneysweatshirtjpg.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='toddler-disney-pajama-set-boys');
--
-- -- 12) Baby Girl Strawberry Footie Pajamas (صورتك)
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Baby Girl Strawberry Footie Pajamas','baby-girl-strawberry-footie','BG-FOOTIE-STRAW-001',
--        'Soft footie pajamas with strawberry embroidery.',59.90,120,'../images/babygirlpajama.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='baby-girl-strawberry-footie');
--
-- -- 13) Toddler Boy Sleeveless Puffer Vest (صورتك)
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Toddler Boy Sleeveless Puffer Vest','toddler-boy-sleeveless-puffer-vest','TB-VEST-001',
--        'Warm colorblock puffer vest with sherpa lining.',139.00,40,'../images/toddlerboySlevlessPufferVest.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='toddler-boy-sleeveless-puffer-vest');
--
-- -- 14) Toddler Girl Pink Dog Dress (صورتك)
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Toddler Girl Pink Dog Dress','toddler-girl-pink-dog-dress','TG-DRESS-PINKDOG-001',
--        'Soft jersey dress with cute dog print.',99.00,35,'../images/toddlergirlDress.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='toddler-girl-pink-dog-dress');
--
-- -- 15) Baby Boy Striped Pocket Tee (Green) (صورتك)
-- INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
-- SELECT 'Baby Boy Striped Pocket Tee (Green)','baby-boy-striped-pocket-tee-green','BB-TEE-STRIPE-GRN-001',
--        'Cotton striped tee with pocket, green.',49.00,80,'../images/babyboystripedsleevepocketteegreen.jpg'
--     WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='baby-boy-striped-pocket-tee-green');
--
-- -- ============================================================================
-- -- Links: product_categories
-- -- ============================================================================
--
-- -- Family Matching
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='family-matching-pajama-set'),
--        (SELECT id FROM categories WHERE slug='family-matching');
--
-- -- Kids (Girls)
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='kids-sleeveless-summer-jumpsuit'),
--        (SELECT id FROM categories WHERE slug='kids-girls');
--
-- -- Toddler (Girl + Boy)
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='toddler-disney-pajama-set'),
--        (SELECT id FROM categories WHERE slug='toddler-girl');
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='toddler-disney-pajama-set'),
--        (SELECT id FROM categories WHERE slug='toddler-boy');
--
-- -- Baby Boy
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='baby-boy-casual-outfit-set'),
--        (SELECT id FROM categories WHERE slug='baby-boy');
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='baby-boy-striped-pocket-tee-green'),
--        (SELECT id FROM categories WHERE slug='baby-boy');
--
-- -- Baby Girl
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='baby-girl-ruffle-dress'),
--        (SELECT id FROM categories WHERE slug='baby-girl');
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='classic-baby-girl-dress-white-lace'),
--        (SELECT id FROM categories WHERE slug='baby-girl');
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='baby-girl-strawberry-footie'),
--        (SELECT id FROM categories WHERE slug='baby-girl');
--
-- -- Kids Girls
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='yellow-ruffle-party-dress'),
--        (SELECT id FROM categories WHERE slug='kids-girls');
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='girls-summer-floral-dress'),
--        (SELECT id FROM categories WHERE slug='kids-girls');
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='girls-cute-pink-cardigan'),
--        (SELECT id FROM categories WHERE slug='kids-girls');
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='pink-girl-shorts'),
--        (SELECT id FROM categories WHERE slug='kids-girls');
--
-- -- Toddler Boy
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='toddler-disney-pajama-set-boys'),
--        (SELECT id FROM categories WHERE slug='toddler-boy');
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='toddler-boy-sleeveless-puffer-vest'),
--        (SELECT id FROM categories WHERE slug='toddler-boy');
--
-- -- Toddler Girl
-- INSERT IGNORE INTO product_categories(product_id,category_id)
-- SELECT (SELECT id FROM products WHERE slug='toddler-girl-pink-dog-dress'),
--        (SELECT id FROM categories WHERE slug='toddler-girl');
--
-- -- ============================================================================
-- -- Sizes
-- -- ============================================================================
--
-- -- Family Matching
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'Mom S' FROM products WHERE slug='family-matching-pajama-set';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'Mom M' FROM products WHERE slug='family-matching-pajama-set';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'Dad M' FROM products WHERE slug='family-matching-pajama-set';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='family-matching-pajama-set';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3-6M' FROM products WHERE slug='family-matching-pajama-set';
--
-- -- Kids / Toddler / Baby
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'2T' FROM products WHERE slug='kids-sleeveless-summer-jumpsuit';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3T' FROM products WHERE slug='kids-sleeveless-summer-jumpsuit';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'4T' FROM products WHERE slug='kids-sleeveless-summer-jumpsuit';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='kids-sleeveless-summer-jumpsuit';
--
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'2T' FROM products WHERE slug='toddler-disney-pajama-set';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3T' FROM products WHERE slug='toddler-disney-pajama-set';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'4T' FROM products WHERE slug='toddler-disney-pajama-set';
--
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'0-3M' FROM products WHERE slug='baby-boy-casual-outfit-set';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3-6M' FROM products WHERE slug='baby-boy-casual-outfit-set';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'6-9M' FROM products WHERE slug='baby-boy-casual-outfit-set';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'9-12M' FROM products WHERE slug='baby-boy-casual-outfit-set';
--
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'0-3M' FROM products WHERE slug='baby-girl-ruffle-dress';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3-6M' FROM products WHERE slug='baby-girl-ruffle-dress';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'6-9M' FROM products WHERE slug='baby-girl-ruffle-dress';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'9-12M' FROM products WHERE slug='baby-girl-ruffle-dress';
--
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'0-3M' FROM products WHERE slug='classic-baby-girl-dress-white-lace';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3-6M' FROM products WHERE slug='classic-baby-girl-dress-white-lace';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'6-9M' FROM products WHERE slug='classic-baby-girl-dress-white-lace';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'9-12M' FROM products WHERE slug='classic-baby-girl-dress-white-lace';
--
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='yellow-ruffle-party-dress';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'7-8Y' FROM products WHERE slug='yellow-ruffle-party-dress';
--
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='girls-summer-floral-dress';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'7-8Y' FROM products WHERE slug='girls-summer-floral-dress';
--
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='girls-cute-pink-cardigan';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'7-8Y' FROM products WHERE slug='girls-cute-pink-cardigan';
--
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'One Size' FROM products WHERE slug='pink-girl-shorts';
--
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'2T' FROM products WHERE slug='toddler-disney-pajama-set-boys';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3T' FROM products WHERE slug='toddler-disney-pajama-set-boys';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'4T' FROM products WHERE slug='toddler-disney-pajama-set-boys';
--
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'Newborn' FROM products WHERE slug='baby-girl-strawberry-footie';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'0-3M' FROM products WHERE slug='baby-girl-strawberry-footie';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3-6M' FROM products WHERE slug='baby-girl-strawberry-footie';
--
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'2T' FROM products WHERE slug='toddler-boy-sleeveless-puffer-vest';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3T' FROM products WHERE slug='toddler-boy-sleeveless-puffer-vest';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'4T' FROM products WHERE slug='toddler-boy-sleeveless-puffer-vest';
--
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'2T' FROM products WHERE slug='toddler-girl-pink-dog-dress';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3T' FROM products WHERE slug='toddler-girl-pink-dog-dress';
-- INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'4T' FROM products WHERE slug='toddler-girl-pink-dog-dress';
--
-- COMMIT;
--
--
-- INSERT INTO product_reviews (product_id, user_id, rating, comment)
-- VALUES (14, 5, 5, 'Lovely quality!');
--
-- INSERT INTO product_likes (product_id, user_id)
-- VALUES (14, 5)
--     ON DUPLICATE KEY UPDATE product_id = product_id;
--
-- INSERT INTO product_likes (product_id, device_hash)
-- VALUES (14, 'sha256:...fingerprint...')
--     ON DUPLICATE KEY UPDATE product_id = product_id;
-- -- تقييمات
-- INSERT INTO product_reviews (product_id, user_id, rating, comment)
-- VALUES
--     (5,  1, 4, 'Nice quality'),
--     (5,  2, 5, 'Loved it'),
--     (16, 1, 3, 'Okay');
--
-- -- لايكات
-- INSERT INTO product_likes (product_id, user_id)
-- VALUES
--     (5, 1),
--     (5, 2),
--     (16,1)
--     ON DUPLICATE KEY UPDATE product_id = product_id;
-- USE matchy_matchy;
-- START TRANSACTION;
--
-- -- Parents
-- UPDATE categories SET image_url = '../images/categories/baby.jpg'
-- WHERE slug='baby';
--
-- UPDATE categories SET image_url = '../images/categories/toddler.jpg'
-- WHERE slug='toddler';
--
-- UPDATE categories SET image_url = '../images/categories/kids.jpg'
-- WHERE slug='kids';
--
-- UPDATE categories SET image_url = '../images/categories/deals.jpg'
-- WHERE slug='deals';
--
-- -- Child: Baby Gear
-- UPDATE categories SET image_url = '../images/categories/BabyGear.jpg'
-- WHERE slug='baby-gear';


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



ALTER TABLE products
    ADD FULLTEXT ft_products_name_desc (name, description),
  ADD FULLTEXT ft_products_sku (sku);



CREATE TABLE IF NOT EXISTS product_embeddings (
                                                  product_id INT PRIMARY KEY,
                                                  model VARCHAR(64) NOT NULL,
    embedding_json MEDIUMTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_prod_emb_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    );



ALTER TABLE products
    ADD FULLTEXT ft_products_name_desc_sku (name, description, sku);

