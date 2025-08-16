
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
