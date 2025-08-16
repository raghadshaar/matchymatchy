USE matchy_matchy;
START TRANSACTION;

-- ========================
-- Parents
-- ========================
INSERT INTO categories (name, slug, status)
SELECT 'Baby','baby','active' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby');

INSERT INTO categories (name, slug, status)
SELECT 'Toddler','toddler','active' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toddler');

INSERT INTO categories (name, slug, status)
SELECT 'Kids','kids','active' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='kids');

INSERT INTO categories (name, slug, status)
SELECT 'Toys','toys','active' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toys');

INSERT INTO categories (name, slug, status)
SELECT 'Deals','deals','active' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='deals');

INSERT INTO categories (name, slug, status)
SELECT 'Collections','collections','active' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='collections');

-- ========================
-- Children: Baby
-- ========================
INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Baby Girl','baby-girl',(SELECT id FROM categories WHERE slug='baby'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby-girl');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Baby Boy','baby-boy',(SELECT id FROM categories WHERE slug='baby'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby-boy');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Baby Neutral','baby-neutral',(SELECT id FROM categories WHERE slug='baby'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby-neutral');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Baby Gear','baby-gear',(SELECT id FROM categories WHERE slug='baby'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby-gear');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Baby Gifts','baby-gifts',(SELECT id FROM categories WHERE slug='baby'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby-gifts');

-- ========================
-- Children: Toddler
-- ========================
INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Toddler Girl','toddler-girl',(SELECT id FROM categories WHERE slug='toddler'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toddler-girl');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Toddler Boy','toddler-boy',(SELECT id FROM categories WHERE slug='toddler'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toddler-boy');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Toddler Gear','toddler-gear',(SELECT id FROM categories WHERE slug='toddler'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toddler-gear');

-- ========================
-- Children: Kids
-- ========================
INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Girls','kids-girls',(SELECT id FROM categories WHERE slug='kids'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='kids-girls');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Boys','kids-boys',(SELECT id FROM categories WHERE slug='kids'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='kids-boys');

-- ========================
-- Children: Toys (3 فقط)
-- ========================
INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Learning & Educational','toys-learning',(SELECT id FROM categories WHERE slug='toys'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toys-learning');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Outdoor Toys','toys-outdoor',(SELECT id FROM categories WHERE slug='toys'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toys-outdoor');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Puzzles & Games','toys-puzzles-games',(SELECT id FROM categories WHERE slug='toys'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='toys-puzzles-games');

-- ========================
-- Children: Deals
-- ========================
INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Flash Sale','flash-sale',(SELECT id FROM categories WHERE slug='deals'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='flash-sale');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Clearance','clearance',(SELECT id FROM categories WHERE slug='deals'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='clearance');

-- (اختياري) موجودة لو احتجتيها لاحقًا
INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Bundle Offers','bundle-offers',(SELECT id FROM categories WHERE slug='deals'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='bundle-offers');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Weekly Specials','weekly-specials',(SELECT id FROM categories WHERE slug='deals'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='weekly-specials');

-- ========================
-- Children: Collections
-- ========================
INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Family Matching','family-matching',(SELECT id FROM categories WHERE slug='collections'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='family-matching');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'New Arrivals','new-arrivals',(SELECT id FROM categories WHERE slug='collections'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='new-arrivals');

INSERT INTO categories (name, slug, parent_id, status)
SELECT 'Best Sellers','best-sellers',(SELECT id FROM categories WHERE slug='collections'),'active'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='best-sellers');

-- ============================================================================
-- Products (seed[] + صورك الإضافية) — الأسعار أرقام DECIMAL
-- ============================================================================

-- 1) Family Matching Pajama Set
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Family Matching Pajama Set','family-matching-pajama-set','PJ-FAM-001',
       'Cozy matching PJs for the whole family.',189.90,64,'../images/familymatching1.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='family-matching-pajama-set');

-- 2) Kids Sleeveless Summer Jumpsuit
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Kids Sleeveless Summer Jumpsuit','kids-sleeveless-summer-jumpsuit','KJ-002',
       'Light and comfy for hot days.',129.90,48,'../images/kidsjumpsuit.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='kids-sleeveless-summer-jumpsuit');

-- 3) Toddler Disney Pajama Set
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Toddler Disney Pajama Set','toddler-disney-pajama-set','TD-DISNEY-003',
       'Cute Disney-themed sleep set for toddlers.',89.00,18,'../images/toddlerdineypjset.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='toddler-disney-pajama-set');

-- 4) Baby Boy Casual Outfit Set
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Baby Boy Casual Outfit Set','baby-boy-casual-outfit-set','BB-SET-004',
       'Everyday comfy set for baby boys.',99.90,25,'../images/babyboyclothes.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='baby-boy-casual-outfit-set');

-- 5) Baby Girl Ruffle Dress
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Baby Girl Ruffle Dress','baby-girl-ruffle-dress','BG-DRESS-005',
       'Sweet ruffle dress for baby girls.',79.90,145,'../images/babygirldress.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='baby-girl-ruffle-dress');

-- 6) Classic Baby Girl Dress – White Lace
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Classic Baby Girl Dress – White Lace','classic-baby-girl-dress-white-lace','BG-DRESS-006',
       'Classic lace look for special days.',84.90,58,'../images/product1.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='classic-baby-girl-dress-white-lace');

-- 7) Yellow Ruffle Party Dress
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Yellow Ruffle Party Dress','yellow-ruffle-party-dress','YR-DRESS-007',
       'Bright yellow party dress with ruffles.',89.90,92,'../images/yellowdress.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='yellow-ruffle-party-dress');

-- 8) Girls Summer Floral Dress
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Girls Summer Floral Dress','girls-summer-floral-dress','SF-DRESS-008',
       'Light floral summer dress.',99.00,31,'../images/summerdess.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='girls-summer-floral-dress');

-- 9) Girls cute pink cardigan
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Girls cute pink cardigan','girls-cute-pink-cardigan','PD-Pink-009',
       'Girls cute pink cardigan',119.90,22,'../images/similar3.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='girls-cute-pink-cardigan');

-- 10) Pink girl shorts
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Pink girl shorts','pink-girl-shorts','ACC-BOW-010',
       '',34.90,12,'../images/similar4.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='pink-girl-shorts');

-- 11) Toddler Disney Pajama Set (Boys)
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Toddler Disney Pajama Set (Boys)','toddler-disney-pajama-set-boys','boy-set',
       'Casual style set for boys.',150.00,100,'../images/disneysweatshirtjpg.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='toddler-disney-pajama-set-boys');

-- 12) Baby Girl Strawberry Footie Pajamas (صورتك)
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Baby Girl Strawberry Footie Pajamas','baby-girl-strawberry-footie','BG-FOOTIE-STRAW-001',
       'Soft footie pajamas with strawberry embroidery.',59.90,120,'../images/babygirlpajama.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='baby-girl-strawberry-footie');

-- 13) Toddler Boy Sleeveless Puffer Vest (صورتك)
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Toddler Boy Sleeveless Puffer Vest','toddler-boy-sleeveless-puffer-vest','TB-VEST-001',
       'Warm colorblock puffer vest with sherpa lining.',139.00,40,'../images/toddlerboySlevlessPufferVest.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='toddler-boy-sleeveless-puffer-vest');

-- 14) Toddler Girl Pink Dog Dress (صورتك)
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Toddler Girl Pink Dog Dress','toddler-girl-pink-dog-dress','TG-DRESS-PINKDOG-001',
       'Soft jersey dress with cute dog print.',99.00,35,'../images/toddlergirlDress.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='toddler-girl-pink-dog-dress');

-- 15) Baby Boy Striped Pocket Tee (Green) (صورتك)
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Baby Boy Striped Pocket Tee (Green)','baby-boy-striped-pocket-tee-green','BB-TEE-STRIPE-GRN-001',
       'Cotton striped tee with pocket, green.',49.00,80,'../images/babyboystripedsleevepocketteegreen.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='baby-boy-striped-pocket-tee-green');

-- ============================================================================
-- Links: product_categories
-- ============================================================================

-- Family Matching
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='family-matching-pajama-set'),
       (SELECT id FROM categories WHERE slug='family-matching');

-- Kids (Girls)
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='kids-sleeveless-summer-jumpsuit'),
       (SELECT id FROM categories WHERE slug='kids-girls');

-- Toddler (Girl + Boy)
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='toddler-disney-pajama-set'),
       (SELECT id FROM categories WHERE slug='toddler-girl');
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='toddler-disney-pajama-set'),
       (SELECT id FROM categories WHERE slug='toddler-boy');

-- Baby Boy
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='baby-boy-casual-outfit-set'),
       (SELECT id FROM categories WHERE slug='baby-boy');
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='baby-boy-striped-pocket-tee-green'),
       (SELECT id FROM categories WHERE slug='baby-boy');

-- Baby Girl
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='baby-girl-ruffle-dress'),
       (SELECT id FROM categories WHERE slug='baby-girl');
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='classic-baby-girl-dress-white-lace'),
       (SELECT id FROM categories WHERE slug='baby-girl');
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='baby-girl-strawberry-footie'),
       (SELECT id FROM categories WHERE slug='baby-girl');

-- Kids Girls
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='yellow-ruffle-party-dress'),
       (SELECT id FROM categories WHERE slug='kids-girls');
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='girls-summer-floral-dress'),
       (SELECT id FROM categories WHERE slug='kids-girls');
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='girls-cute-pink-cardigan'),
       (SELECT id FROM categories WHERE slug='kids-girls');
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='pink-girl-shorts'),
       (SELECT id FROM categories WHERE slug='kids-girls');

-- Toddler Boy
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='toddler-disney-pajama-set-boys'),
       (SELECT id FROM categories WHERE slug='toddler-boy');
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='toddler-boy-sleeveless-puffer-vest'),
       (SELECT id FROM categories WHERE slug='toddler-boy');

-- Toddler Girl
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT (SELECT id FROM products WHERE slug='toddler-girl-pink-dog-dress'),
       (SELECT id FROM categories WHERE slug='toddler-girl');

-- ============================================================================
-- Sizes
-- ============================================================================

-- Family Matching
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'Mom S' FROM products WHERE slug='family-matching-pajama-set';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'Mom M' FROM products WHERE slug='family-matching-pajama-set';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'Dad M' FROM products WHERE slug='family-matching-pajama-set';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='family-matching-pajama-set';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3-6M' FROM products WHERE slug='family-matching-pajama-set';

-- Kids / Toddler / Baby (حسب كل منتج)
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'2T' FROM products WHERE slug='kids-sleeveless-summer-jumpsuit';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3T' FROM products WHERE slug='kids-sleeveless-summer-jumpsuit';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'4T' FROM products WHERE slug='kids-sleeveless-summer-jumpsuit';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='kids-sleeveless-summer-jumpsuit';

INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'2T' FROM products WHERE slug='toddler-disney-pajama-set';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3T' FROM products WHERE slug='toddler-disney-pajama-set';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'4T' FROM products WHERE slug='toddler-disney-pajama-set';

INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'0-3M' FROM products WHERE slug='baby-boy-casual-outfit-set';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3-6M' FROM products WHERE slug='baby-boy-casual-outfit-set';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'6-9M' FROM products WHERE slug='baby-boy-casual-outfit-set';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'9-12M' FROM products WHERE slug='baby-boy-casual-outfit-set';

INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'0-3M' FROM products WHERE slug='baby-girl-ruffle-dress';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3-6M' FROM products WHERE slug='baby-girl-ruffle-dress';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'6-9M' FROM products WHERE slug='baby-girl-ruffle-dress';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'9-12M' FROM products WHERE slug='baby-girl-ruffle-dress';

INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'0-3M' FROM products WHERE slug='classic-baby-girl-dress-white-lace';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3-6M' FROM products WHERE slug='classic-baby-girl-dress-white-lace';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'6-9M' FROM products WHERE slug='classic-baby-girl-dress-white-lace';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'9-12M' FROM products WHERE slug='classic-baby-girl-dress-white-lace';

INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='yellow-ruffle-party-dress';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'7-8Y' FROM products WHERE slug='yellow-ruffle-party-dress';

INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='girls-summer-floral-dress';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'7-8Y' FROM products WHERE slug='girls-summer-floral-dress';

INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='girls-cute-pink-cardigan';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'7-8Y' FROM products WHERE slug='girls-cute-pink-cardigan';

INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'One Size' FROM products WHERE slug='pink-girl-shorts';

INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'2T' FROM products WHERE slug='toddler-disney-pajama-set-boys';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3T' FROM products WHERE slug='toddler-disney-pajama-set-boys';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'4T' FROM products WHERE slug='toddler-disney-pajama-set-boys';

INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'Newborn' FROM products WHERE slug='baby-girl-strawberry-footie';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'0-3M' FROM products WHERE slug='baby-girl-strawberry-footie';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3-6M' FROM products WHERE slug='baby-girl-strawberry-footie';

INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'2T' FROM products WHERE slug='toddler-boy-sleeveless-puffer-vest';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3T' FROM products WHERE slug='toddler-boy-sleeveless-puffer-vest';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'4T' FROM products WHERE slug='toddler-boy-sleeveless-puffer-vest';

INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'2T' FROM products WHERE slug='toddler-girl-pink-dog-dress';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3T' FROM products WHERE slug='toddler-girl-pink-dog-dress';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'4T' FROM products WHERE slug='toddler-girl-pink-dog-dress';

COMMIT;


INSERT INTO product_reviews (product_id, user_id, rating, comment)
VALUES (14, 5, 5, 'Lovely quality!');

INSERT INTO product_likes (product_id, user_id)
VALUES (14, 5)
    ON DUPLICATE KEY UPDATE product_id = product_id;  -- يمنع التكرار

INSERT INTO product_likes (product_id, device_hash)
VALUES (14, 'sha256:...fingerprint...')
    ON DUPLICATE KEY UPDATE product_id = product_id;
-- تقييمات
INSERT INTO product_reviews (product_id, user_id, rating, comment)
VALUES
    (5,  1, 4, 'Nice quality'),
    (5,  2, 5, 'Loved it'),
    (16, 1, 3, 'Okay');

-- لايكات
INSERT INTO product_likes (product_id, user_id)
VALUES
    (5, 1),
    (5, 2),
    (16,1)
    ON DUPLICATE KEY UPDATE product_id = product_id;
