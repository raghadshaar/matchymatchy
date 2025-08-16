USE matchy_matchy;

-- فئة الصفحة
INSERT INTO categories (name, slug, status, image_url)
SELECT 'Baby Girl Pajamas','baby-girl-pajamas','active','../images/Modal Cloud Print Footie Pajamas.jpg'
    WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug='baby-girl-pajamas');

-- منتجين مثال
INSERT INTO products (name, slug, sku, description, price, currency, stock, status, image_main_url)
VALUES
    ('Modal Cloud Print Footie Pajamas','modal-cloud-footie','PJG-001','Soft modal footie pajamas with cloud print.',59.00,'ILS',120,'auto','../images/Modal Cloud Print Footie Pajamas.jpg'),
    ('Butterfly Print Two-Piece Set','butterfly-two-piece','PJG-002','Two-piece set with cute butterfly print.',59.00,'ILS',80,'auto','../images/Butterfly Print Two-Piece Set.jpg')
    ON DUPLICATE KEY UPDATE name=VALUES(name);

-- ربطهم بالفئة
INSERT IGNORE INTO product_categories (product_id, category_id)
SELECT p.id, c.id FROM products p, categories c
WHERE p.slug='modal-cloud-footie' AND c.slug='baby-girl-pajamas';

INSERT IGNORE INTO product_categories (product_id, category_id)
SELECT p.id, c.id FROM products p, categories c
WHERE p.slug='butterfly-two-piece' AND c.slug='baby-girl-pajamas';
