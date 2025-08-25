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
-- -- (اختياري) موجودة لو احتجتيها لاحقًا
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
-- -- Products (seed[] + صورك الإضافية) — الأسعار أرقام DECIMAL
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
-- -- Kids / Toddler / Baby (حسب كل منتج)
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
--     ON DUPLICATE KEY UPDATE product_id = product_id;  -- يمنع التكرار
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
-- -- Child: Baby Gear  (انتبهي لحساسية حالة الأحرف في اسم الملف)
-- UPDATE categories SET image_url = '../images/categories/BabyGear.jpg'
-- WHERE slug='baby-gear';
--
-- -- Child: Family Matching (ملفّك اسمه "famillymatching.jpg" بحرف l زيادة)
-- -- يا إمّا تعملي Rename للملف إلى "familymatching.jpg" وتستخدمي السطر الأول،
-- -- أو تتركي الاسم كما هو وتستخدمي السطر الثاني.
-- -- الاختيار 1 (مُستحسن): بعد ما تعملي rename للملف لاسم صحيح
-- -- UPDATE categories SET image_url = '../images/categories/familymatching.jpg'
-- -- WHERE slug='family-matching';
--
-- UPDATE categories SET image_url = '../images/categories/famillymatching.jpg'
-- WHERE slug='family-matching';
--
-- COMMIT;

--
-- START TRANSACTION;
--
-- /* ====== Parents ====== */
-- UPDATE categories SET image_url = '../images/categories/baby.jpg'               WHERE slug='baby';
-- UPDATE categories SET image_url = '../images/categories/toddler.jpg'            WHERE slug='toddler';
-- UPDATE categories SET image_url = '../images/categories/kids.jpg'               WHERE slug='kids';
-- UPDATE categories SET image_url = '../images/categories/toysjpg.jpg'            WHERE slug='toys';         -- انتِ مسمّياه toysjpg.jpg
-- UPDATE categories SET image_url = '../images/categories/deals.jpg'              WHERE slug='deals';
-- UPDATE categories SET image_url = '../images/categories/collectionsjpg.jpg'     WHERE slug='collections';  -- انتِ مسمّياه collectionsjpg.jpg
--
-- /* ====== Baby children ====== */
-- UPDATE categories SET image_url = '../images/categories/babygirl.jpg'           WHERE slug='baby-girl';
-- UPDATE categories SET image_url = '../images/categories/babyboy.jpg'            WHERE slug='baby-boy';
-- UPDATE categories SET image_url = '../images/categories/BabyGear.jpg'           WHERE slug='baby-gear';    -- لاحظي الحروف الكبيرة
--
-- /* ====== Toddler children ====== */
-- UPDATE categories SET image_url = '../images/categories/toddlergirl.jpg'        WHERE slug='toddler-girl';
--
-- /* ====== Kids children ====== */
-- UPDATE categories SET image_url = '../images/categories/kidsboy.jpg'            WHERE slug='kids-boys';
--
-- /* ====== Collections children ====== */
-- UPDATE categories SET image_url = '../images/categories/familymatching.jpg'     WHERE slug='family-matching';
--
-- UPDATE categories SET image_url = '../images/categories/sales.jpg'              WHERE slug='flash-sale';
--
-- COMMIT;




USE matchy_matchy;
START TRANSACTION;

-- ========================
-- Products
-- ========================

-- 16) Boys Striped Long-Sleeve Tee (Grey)
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Boys Striped LS Tee - Grey','boys-striped-ls-tee-grey','BOY-TEE-GRY-016',
       'About This Item: This classic striped long-sleeve tee is perfect for layering or wearing solo. Crafted in soft cotton, it keeps him comfy all day.
       Features: Relaxed fit, soft knit cotton, crew neck, striped pattern, long sleeves.',
       69.90,60,'../uploads/products/Boys StripedLong-SleeveTeeGrey.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='boys-striped-ls-tee-grey');

-- 17) Construction Wooden Activity Toy Set
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Construction Activity Toy Set','construction-activity-toy','TOY-BUILD-017',
       'About This Item: A wooden construction set that sparks creativity and problem-solving. Durable and safe pieces for endless play.
       Features: Eco-friendly wood, 50+ pieces, educational design, safe rounded edges.',
       159.90,35,'../uploads/products/Construction Wooden Activity Toy Set1.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='construction-activity-toy');

-- 18) Kids Rainbow Heart Pajamas – Pink
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Kids Rainbow Heart Pajamas – Pink','kids-rainbow-heart-pjs-pink','PJS-RAIN-018',
       'About This Item: A dreamy pajama set with rainbow heart graphics. Mix-and-match tops and bottoms keep bedtime fun and comfy.
       Features: 100% cotton, snug fit, short + long sleeve tops, 2 coordinating bottoms.',
       129.00,72,'../uploads/products/Kid-4pc-Rainbow-Heart-PJs1.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='kids-rainbow-heart-pjs-pink');

-- 19) Girls Glow-in-the-Dark Disney Lilo & Stitch Tee (Purple)
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Girls Glow Stitch Tee - Purple','girls-glow-stitch-tee','TEE-STITCH-019',
       'About This Item: A magical tee featuring Disney’s Stitch in glow-in-the-dark print. Fun for day and extra special at night.
       Features: Glow print, short sleeves, crew neck, soft cotton blend.',
       89.90,55,'../uploads/products/Girls Glow-In-The-Dark Halloween Disney© Lilo & Stitch Mummy Short-Sleeve Graphic TeePurple.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='girls-glow-stitch-tee');

-- 20) Kids Baggy Butterfly Embroidered Jeans (Breeze Wash)
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Kids Butterfly Baggy Jeans','kids-butterfly-baggy-jeans','JEANS-BFLY-020',
       'About This Item: Trendy baggy-fit jeans decorated with butterfly embroidery. A stylish must-have for any playful day.
       Features: 100% cotton denim, breeze wash, loose fit, embroidered butterflies.',
       149.90,40,'../uploads/products/KidBaggyButterflyEmbroideredJeansBreezeWash.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='kids-butterfly-baggy-jeans');

-- ========================
-- Categories
-- ========================

-- Boys Tee → Kids Boys
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT p.id, c.id FROM products p, categories c
WHERE p.slug='boys-striped-ls-tee-grey' AND c.slug='kids-boys';

-- Toy → Toys Learning
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT p.id, c.id FROM products p, categories c
WHERE p.slug='construction-activity-toy' AND c.slug='toys-learning';

-- Pajamas → Kids Girls
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT p.id, c.id FROM products p, categories c
WHERE p.slug='kids-rainbow-heart-pjs-pink' AND c.slug='kids-girls';

-- Glow Tee → Kids Girls
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT p.id, c.id FROM products p, categories c
WHERE p.slug='girls-glow-stitch-tee' AND c.slug='kids-girls';

-- Jeans → Kids Girls
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT p.id, c.id FROM products p, categories c
WHERE p.slug='kids-butterfly-baggy-jeans' AND c.slug='kids-girls';

-- ========================
-- Sizes
-- ========================

-- Boys Tee
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='boys-striped-ls-tee-grey';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'7-8Y' FROM products WHERE slug='boys-striped-ls-tee-grey';

-- Pajamas
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'4-5Y' FROM products WHERE slug='kids-rainbow-heart-pjs-pink';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='kids-rainbow-heart-pjs-pink';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'7-8Y' FROM products WHERE slug='kids-rainbow-heart-pjs-pink';

-- Glow Tee
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='girls-glow-stitch-tee';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'7-8Y' FROM products WHERE slug='girls-glow-stitch-tee';

-- Jeans
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'7-8Y' FROM products WHERE slug='kids-butterfly-baggy-jeans';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'9-10Y' FROM products WHERE slug='kids-butterfly-baggy-jeans';

-- Toy
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'One Size' FROM products WHERE slug='construction-activity-toy';

-- ========================
-- Fabrics
-- ========================

INSERT IGNORE INTO product_fabrics(product_id,fabric_id)
SELECT p.id, f.id FROM products p JOIN fabric_options f ON f.name='Cotton'
WHERE p.slug IN ('boys-striped-ls-tee-grey','kids-rainbow-heart-pjs-pink','girls-glow-stitch-tee','kids-butterfly-baggy-jeans');

-- ========================
-- Colors
-- ========================

INSERT IGNORE INTO product_colors(product_id,color_id)
SELECT p.id, c.id FROM products p JOIN color_options c ON c.name='Blue'
WHERE p.slug='boys-striped-ls-tee-grey';

INSERT IGNORE INTO product_colors(product_id,color_id)
SELECT p.id, c.id FROM products p JOIN color_options c ON c.name='Pink'
WHERE p.slug='kids-rainbow-heart-pjs-pink';

INSERT IGNORE INTO product_colors(product_id,color_id)
SELECT p.id, c.id FROM products p JOIN color_options c ON c.name='Blue'
WHERE p.slug='girls-glow-stitch-tee';

INSERT IGNORE INTO product_colors(product_id,color_id)
SELECT p.id, c.id FROM products p JOIN color_options c ON c.name='Blue'
WHERE p.slug='kids-butterfly-baggy-jeans';

-- ========================
-- Images
-- ========================

-- Boys Tee
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Boys StripedLong-SleeveTeeGrey.jpg', 0, 'Front'
FROM products WHERE slug='boys-striped-ls-tee-grey';
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Boys Striped Long-SleeveTeeGrey2.jpg', 1, 'Detail'
FROM products WHERE slug='boys-striped-ls-tee-grey';

-- Toy
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Construction Wooden Activity Toy Set1.jpg', 0, 'Front'
FROM products WHERE slug='construction-activity-toy';

-- Pajamas
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Kid-4pc-Rainbow-Heart-PJs1.jpg', 0, 'Front'
FROM products WHERE slug='kids-rainbow-heart-pjs-pink';
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Kid-4pc-Rainbow-Heart-PJs2.jpg', 1, 'Back'
FROM products WHERE slug='kids-rainbow-heart-pjs-pink';

-- Glow Tee
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Girls Glow-In-The-Dark Halloween Disney© Lilo & Stitch Mummy Short-Sleeve Graphic TeePurple.jpg', 0, 'Front'
FROM products WHERE slug='girls-glow-stitch-tee';
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Girls Glow-In-The-Dark Halloween Disney© Lilo & Stitch Mummy Short-Sleeve Graphic TeePurple2.jpg', 1, 'Detail'
FROM products WHERE slug='girls-glow-stitch-tee';

-- Jeans
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/KidBaggyButterflyEmbroideredJeansBreezeWash.jpg', 0, 'Front'
FROM products WHERE slug='kids-butterfly-baggy-jeans';
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/KidBaggyButterflyEmbroideredJeansBreezeWash2.jpg', 1, 'Back'
FROM products WHERE slug='kids-butterfly-baggy-jeans';

-- Sync main image with gallery first
UPDATE products p
    JOIN product_images pi ON pi.product_id=p.id AND pi.sort_order=0
    SET p.image_main_url=pi.image_url
WHERE p.slug IN ('boys-striped-ls-tee-grey','construction-activity-toy',
    'kids-rainbow-heart-pjs-pink','girls-glow-stitch-tee',
    'kids-butterfly-baggy-jeans');

COMMIT;






USE matchy_matchy;
START TRANSACTION;

-- 21) Bandana Buddies Baby Activity Toy - Llama
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Bandana Buddies Llama Activity Toy','llama-activity-toy','TOY-LLAMA-021',
       'About This Item: A cuddly llama activity toy with textures, teether, and fun sounds. Keeps baby engaged at home or on the go.
       Features: Soft plush design, clip for stroller, teethers & crinkles, baby-safe materials.',
       89.90,50,'../uploads/products/Bandana Buddies Baby Activity Toy - Llama1.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='llama-activity-toy');

-- Category → Toys ▸ Learning & Educational
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT p.id, c.id FROM products p, categories c
WHERE p.slug='llama-activity-toy' AND c.slug='toys-learning';

-- Sizes → One Size
INSERT IGNORE INTO product_sizes(product_id,size_label)
SELECT id,'One Size' FROM products WHERE slug='llama-activity-toy';

-- Fabrics → Cotton (plush)
INSERT IGNORE INTO product_fabrics(product_id,fabric_id)
SELECT p.id, f.id FROM products p JOIN fabric_options f ON f.name='Cotton'
WHERE p.slug='llama-activity-toy';

-- Colors → White
INSERT IGNORE INTO product_colors(product_id,color_id)
SELECT p.id, c.id FROM products p JOIN color_options c ON c.name='White'
WHERE p.slug='llama-activity-toy';

-- Images
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Bandana Buddies Baby Activity Toy - Llama1.jpg', 0, 'Front'
FROM products WHERE slug='llama-activity-toy';

INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Bandana Buddies Baby Activity Toy - Llama2.jpg', 1, 'In Use'
FROM products WHERE slug='llama-activity-toy';

-- Sync main image with gallery first
UPDATE products p
    JOIN product_images pi ON pi.product_id=p.id AND pi.sort_order=0
    SET p.image_main_url=pi.image_url
WHERE p.slug='llama-activity-toy';

COMMIT;




















USE matchy_matchy;
START TRANSACTION;

-- 22) Baby Cotton Long-Sleeve Graphic Bodysuit (Orange)
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Baby Cotton LS Graphic Bodysuit - Orange','baby-graphic-bodysuit-orange','BODYSUIT-022',
       'About This Item: Soft cotton bodysuit with a playful Halloween-themed graphic print “Here For The Treats”. Perfect for layering and cozy comfort.
       Features: 100% cotton, long sleeves, expandable shoulders, snaps at bottom for easy changes.',
       49.90,100,'../uploads/products/Baby CottonLong-SleeveGraphicBodysuitOrange.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='baby-graphic-bodysuit-orange');

-- Category → Baby Boy
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT p.id, c.id FROM products p, categories c
WHERE p.slug='baby-graphic-bodysuit-orange' AND c.slug='baby-boy';

-- Sizes
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'0-3M' FROM products WHERE slug='baby-graphic-bodysuit-orange';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3-6M' FROM products WHERE slug='baby-graphic-bodysuit-orange';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'6-9M' FROM products WHERE slug='baby-graphic-bodysuit-orange';

-- Images
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Baby CottonLong-SleeveGraphicBodysuitOrange.jpg', 0, 'Front'
FROM products WHERE slug='baby-graphic-bodysuit-orange';
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Baby CottonLong-SleeveGraphicBodysuitOrange2.jpg', 1, 'Detail'
FROM products WHERE slug='baby-graphic-bodysuit-orange';

-- 23) Girls Wildflower Short-Sleeve Graphic Tee (Grey)
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Girls Wildflower Graphic Tee - Grey','girls-wildflower-tee-grey','TEE-WFLOWER-023',
       'About This Item: A short-sleeve tee with “Forever Inspired” wildflower print. Comfortable cotton fabric for all-day wear.
       Features: 100% cotton, short sleeves, crew neck, floral graphic.',
       59.90,80,'../uploads/products/GirlsWildflowerShort-SleeveGraphicTeeGrey.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='girls-wildflower-tee-grey');

-- Category → Kids Girls
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT p.id, c.id FROM products p, categories c
WHERE p.slug='girls-wildflower-tee-grey' AND c.slug='girls';

-- Sizes
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'5-6Y' FROM products WHERE slug='girls-wildflower-tee-grey';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'7-8Y' FROM products WHERE slug='girls-wildflower-tee-grey';

-- Images
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/GirlsWildflowerShort-SleeveGraphicTeeGrey.jpg', 0, 'Front'
FROM products WHERE slug='girls-wildflower-tee-grey';
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/GirlsWildflowerShort-SleeveGraphicTeeGrey2.jpg', 1, 'Detail'
FROM products WHERE slug='girls-wildflower-tee-grey';

-- 24) Baby 4-Pack Side-Snap Tees (White)
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Baby 4-Pack Side-Snap Tees - White','baby-4pack-sidesnap-tees','TEE-SNAP-024',
       'About This Item: Essential 4-pack side-snap tees made of pure cotton. Easy to put on, gentle on baby skin.
       Features: 100% cotton, side-snap design, long sleeves, machine washable.',
       119.90,60,'../uploads/products/Baby4-Pack Side-SnapTeesWhite.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='baby-4pack-sidesnap-tees');

-- Category → Baby Neutral
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT p.id, c.id FROM products p, categories c
WHERE p.slug='baby-4pack-sidesnap-tees' AND c.slug='baby-neutral';

-- Sizes
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'0-3M' FROM products WHERE slug='baby-4pack-sidesnap-tees';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'3-6M' FROM products WHERE slug='baby-4pack-sidesnap-tees';
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'6-9M' FROM products WHERE slug='baby-4pack-sidesnap-tees';

-- Images
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Baby4-Pack Side-SnapTeesWhite.jpg', 0, 'Front'
FROM products WHERE slug='baby-4pack-sidesnap-tees';
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/Baby4-Pack Side-SnapTeesWhite2.jpg', 1, 'Detail'
FROM products WHERE slug='baby-4pack-sidesnap-tees';

-- 25) Discoverosity Montessori-Inspired Activity Center
INSERT INTO products (name, slug, sku, description, price, stock, image_main_url)
SELECT 'Discoverosity Montessori Activity Center','discoverosity-activity-center','TOY-CENTER-025',
       'About This Item: Montessori-inspired 3-stage activity center and play table. Designed for motor skills and exploration.
       Features: 3-in-1 design (sit, spin, play), removable toys, converts to play table.',
       399.00,20,'../uploads/products/DiscoverosityMontessori-Inspired3-StageActivityCenter&PlayTable.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE slug='discoverosity-activity-center');

-- Category → Toys Learning
INSERT IGNORE INTO product_categories(product_id,category_id)
SELECT p.id, c.id FROM products p, categories c
WHERE p.slug='discoverosity-activity-center' AND c.slug='toys-learning';

-- Sizes
INSERT IGNORE INTO product_sizes(product_id,size_label) SELECT id,'One Size' FROM products WHERE slug='discoverosity-activity-center';

-- Images
INSERT INTO product_images (product_id, image_url, sort_order, alt_text)
SELECT id, '/matchymatchy/uploads/products/DiscoverosityMontessori-Inspired3-StageActivityCenter&PlayTable.jpg', 0, 'Front'
FROM products WHERE slug='discoverosity-activity-center';

-- Sync main images with gallery
UPDATE products p
    JOIN product_images pi ON pi.product_id=p.id AND pi.sort_order=0
    SET p.image_main_url=pi.image_url
WHERE p.slug IN ('baby-graphic-bodysuit-orange','girls-wildflower-tee-grey',
    'baby-4pack-sidesnap-tees','discoverosity-activity-center');

COMMIT;
