
--ALTER TABLE product_reviews
  --  ADD UNIQUE KEY uq_pr_user (product_id, user_id),
 --   ADD UNIQUE KEY uq_pr_dev  (product_id, device_hash);
-- مستخدم
WITH d AS (
    SELECT id,
           ROW_NUMBER() OVER (PARTITION BY product_id, user_id ORDER BY created_at DESC, id DESC) AS rn
    FROM product_reviews
    WHERE user_id IS NOT NULL
)
DELETE pr FROM product_reviews pr
JOIN d ON pr.id = d.id
WHERE d.rn > 1;

-- ضيف/جهاز
WITH d AS (
    SELECT id,
           ROW_NUMBER() OVER (PARTITION BY product_id, device_hash ORDER BY created_at DESC, id DESC) AS rn
    FROM product_reviews
    WHERE device_hash IS NOT NULL
)
DELETE pr FROM product_reviews pr
JOIN d ON pr.id = d.id
WHERE d.rn > 1;
