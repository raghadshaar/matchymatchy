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

COMMIT;