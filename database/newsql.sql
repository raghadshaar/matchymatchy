-- Users (as you wrote)
INSERT INTO users
(first_name, last_name, email, password, avatar, provider, google_id, email_verified_at, created_at)
VALUES
    ('Samir', 'Hassan', 'samir@email.com', '123456', 'https://ui-avatars.com/api/?name=Samir+Hassan&background=008080&color=fff', NULL, NULL, NULL, NOW()),
    ('Layla', 'Nasser', 'layla@email.com', '123456', 'https://ui-avatars.com/api/?name=Layla+Nasser&background=008080&color=fff', NULL, NULL, NULL, NOW()),
    ('Ahmad', 'Saleh', 'ahmad@email.com', '123456', 'https://ui-avatars.com/api/?name=Ahmad+Saleh&background=008080&color=fff', NULL, NULL, NULL, NOW()),
    ('Nour', 'Khalil', 'nour@email.com', '123456', 'https://ui-avatars.com/api/?name=Nour+Khalil&background=008080&color=fff', NULL, NULL, NULL, NOW()),
    ('Omar', 'Zein', 'omar@email.com', '123456', 'https://ui-avatars.com/api/?name=Omar+Zein&background=008080&color=fff', NULL, NULL, NULL, NOW());

-- Reviews (make sure product_id 5 exists)
INSERT INTO product_reviews (product_id, user_id, rating, comment, created_at)
VALUES
    (5, 1, 5, 'My son loved it. The design is perfect and sizing is accurate.', '2024-08-06 12:00:00'),
    (5, 2, 4, 'Good quality, but delivery took longer than expected.', '2024-08-07 15:30:00'),
    (5, 3, 5, 'Excellent material and fits my daughter perfectly. Will order again!', '2024-08-08 09:45:00');
ALTER TABLE product_reviews
    ADD UNIQUE KEY uq_pr_user (product_id, user_id);

